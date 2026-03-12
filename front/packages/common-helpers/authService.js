import api from '@helpers/axios';

/**
 * Service d'authentification
 * Gère la récupération des informations utilisateur depuis le serveur
 * Les tokens sont stockés dans des cookies HTTP-only (non accessibles en JS)
 */

// Cache pour les informations utilisateur
let cachedUserInfo = null;
let inactivityTimeout = null;
const INACTIVITY_LIMIT = 45 * 60 * 1000; // 45 minutes en millisecondes

/**
 * Réinitialise le minuteur d'inactivité
 */
export const resetInactivityTimer = () => {
    if (inactivityTimeout) {
        clearTimeout(inactivityTimeout);
    }
    inactivityTimeout = setTimeout(() => {
        console.log('Déconnexion automatique pour inactivité');
        logout();
    }, INACTIVITY_LIMIT);
};

/**
 * Initialise la surveillance de l'activité utilisateur
 */
export const setupInactivityTimer = () => {
    // Événements considérés comme une activité
    const events = ['mousedown', 'keydown', 'touchstart', 'scroll'];
    
    events.forEach(event => {
        window.addEventListener(event, resetInactivityTimer);
    });

    // Premier lancement
    resetInactivityTimer();
};

/**
 * Récupère les informations de l'utilisateur authentifié depuis le serveur
 * @returns {Promise<{userId: number, type: string, username: string} | null>}
 */
export const getAuthenticatedUser = async () => {
    if (cachedUserInfo) {
        return cachedUserInfo;
    }

    try {
        const response = await api.get('/api/auth/me');
        cachedUserInfo = response.data;
        return cachedUserInfo;
    } catch (error) {
        // L'intercepteur Axios gérera le refresh ou la redirection vers le login en cas de 401
        // On s'assure de nettoyer le cache et de retourner null
        cachedUserInfo = null;

        if (error.response && error.response.status === 401) {
            // Si c'est un 401, on s'assure que l'utilisateur est déconnecté 
            // au cas où l'intercepteur n'aurait pas réussi (ex: refresh échoué)
            logout();
        }

        console.error('Erreur lors de la récupération des informations utilisateur:', error);
        return null;
    }
};

/**
 * Vérifie si l'utilisateur est authentifié
 * @returns {Promise<boolean>}
 */
export const isAuthenticated = async () => {
    const user = await getAuthenticatedUser();
    return user !== null && user.authenticated === true;
};

/**
 * Déconnecte l'utilisateur
 */
export const logout = async () => {
    // Importation dynamique pour éviter les dépendances circulaires
    const { logout: axiosLogout } = await import('@helpers/axios');
    axiosLogout();
};

/**
 * Réinitialise le cache des informations utilisateur
 * Utile après un changement de session ou un refresh token
 */
export const clearUserCache = () => {
    cachedUserInfo = null;
};

export default {
    getAuthenticatedUser,
    isAuthenticated,
    logout,
    clearUserCache,
    setupInactivityTimer,
    resetInactivityTimer
};

