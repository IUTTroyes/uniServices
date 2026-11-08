/**
 * Message lisible à partir d'une erreur d'appel à l'API (axios) :
 * violations de validation (422, une par ligne), puis message d'erreur du serveur, sinon le message par défaut.
 */
export const messageErreurApi = (error, defaut = 'Une erreur est survenue.') => {
  const data = error?.response?.data;
  if (!data || typeof data !== 'object') {
    return defaut;
  }
  if (Array.isArray(data.violations) && data.violations.length > 0) {
    return [...new Set(data.violations.map(v => v.message).filter(Boolean))].join('\n');
  }
  return data.detail || data['hydra:description'] || data.error || data.message || defaut;
};
