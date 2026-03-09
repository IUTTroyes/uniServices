import api from '@helpers/axios';

/**
 * Service d'authentification
 * Gère la récupération des informations utilisateur depuis le serveur
 * Les tokens sont stockés dans des cookies HTTP-only (non accessibles en JS)
 */

// Cache pour les informations utilisateur
let cachedUserInfo = null;

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
    try {
        await api.post('/api/logout');
    } catch (error) {
        console.error('Erreur lors de la déconnexion:', error);
    } finally {
        cachedUserInfo = null;
        window.location.replace('/auth/login');
    }
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
    clearUserCache
};

