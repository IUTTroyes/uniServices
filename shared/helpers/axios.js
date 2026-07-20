import axios from 'axios';

const baseURL = import.meta.env.VITE_BASE_URL || '';

const api = axios.create({
    baseURL,
    withCredentials: true, // Important: permet l'envoi des cookies avec chaque requête
});

// Variable pour éviter les rafraîchissements multiples simultanés
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error) => {
    failedQueue.forEach(prom => {
        if (error) {
            prom.reject(error);
        } else {
            prom.resolve();
        }
    });
    failedQueue = [];
};

// Fonction pour rafraîchir le token
const refreshToken = async () => {
    try {
        await axios.post(`${baseURL}/api/token/refresh`, {}, {
            withCredentials: true,
        });
        return true;
    } catch (error) {
        return false;
    }
};

// Fonction de déconnexion
const logout = () => {
    // Éviter les boucles de déconnexion si on est déjà sur la page de login
    if (window.location.pathname === '/auth/login' || window.location.pathname === '/auth/login/') return;

    // Appeler l'endpoint de logout pour supprimer les cookies côté serveur
    axios.post(`${baseURL}/api/logout`, {}, { withCredentials: true })
        .catch(() => {}) // Ignorer les erreurs au logout
        .finally(() => {
            // Utiliser une redirection propre vers l'application auth
            window.location.href = window.location.origin + '/app/auth/login';
        });
};

const getErrorDebugMessage = (error) => {
    const response = error?.response;
    const data = response?.data;

    const apiMessage = data?.['hydra:description']
        || data?.detail
        || data?.message
        || data?.error
        || null;

    const statusPart = response?.status ? `HTTP ${response.status}` : 'HTTP inconnu';
    const methodPart = error?.config?.method ? String(error.config.method).toUpperCase() : 'METHOD inconnue';
    const urlPart = error?.config?.url || 'URL inconnue';
    const requestPart = `[${methodPart}] ${urlPart}`;

    return apiMessage
        ? `${statusPart} - ${requestPart}\n${apiMessage}`
        : `${statusPart} - ${requestPart}\n${error?.message || 'Erreur serveur sans détail fourni.'}`;
};

const redirectToServerErrorPage = (error) => {
    const currentPath = window.location.pathname;
    if (currentPath === '/app/500' || currentPath === '/app/500/') return;

    const debugMessage = getErrorDebugMessage(error);
    try {
        window.sessionStorage.setItem('lastServerErrorDebugMessage', debugMessage);
    } catch (_) {
        // Ne pas bloquer la redirection si le stockage est indisponible
    }

    window.location.href = window.location.origin + '/app/500';
};

api.interceptors.request.use(
    config => {
        // Réinitialiser le minuteur d'inactivité à chaque requête API sortante
        import('@helpers/authService').then(auth => {
            if (auth && typeof auth.resetInactivityTimer === 'function') {
                auth.resetInactivityTimer();
            }
        }).catch(() => {});

        // Les cookies sont envoyés automatiquement grâce à withCredentials: true
        // Plus besoin de gérer le token manuellement dans le header
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

api.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;

        if (!originalRequest) {
            return Promise.reject(error);
        }

        // Skip refresh pour les endpoints publics
        const publicEndpoints = ['/api/login', '/api/change_password', '/api/token/refresh', '/api/logout'];
        if (originalRequest.url && publicEndpoints.some(endpoint => originalRequest.url.includes(endpoint))) {
            return Promise.reject(error);
        }

        // Si erreur 401 et pas déjà en train de retry
        if (error.response && error.response.status === 401 && !originalRequest._retry) {
            if (isRefreshing) {
                // Si un refresh est déjà en cours, mettre la requête en file d'attente
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject });
                }).then(() => {
                    return api(originalRequest);
                }).catch(err => {
                    return Promise.reject(err);
                });
            }

            originalRequest._retry = true;
            isRefreshing = true;

            try {
                const refreshed = await refreshToken();

                if (refreshed) {
                    processQueue(null);
                    isRefreshing = false;
                    // Réessayer la requête originale avec l'instance api
                    return api(originalRequest);
                } else {
                    processQueue(new Error('Refresh failed'));
                    isRefreshing = false;
                    logout();
                    return Promise.reject(error);
                }
            } catch (refreshError) {
                processQueue(refreshError);
                isRefreshing = false;
                logout();
                return Promise.reject(refreshError);
            }
        }

        // Si l'erreur est un 401 et que c'est déjà un retry, ou si le refresh a échoué
        if (error.response && error.response.status === 401 && (originalRequest._retry || originalRequest.url.includes('/api/token/refresh'))) {
            logout();
        }

        if (error.response && error.response.status >= 500) {
            redirectToServerErrorPage(error);
        }

        return Promise.reject(error);
    }
);

export { logout };
export default api;
