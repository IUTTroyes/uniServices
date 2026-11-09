import api from '@helpers/axios';

const base = '/api/unifolio/traces';

export const apiBaseUrl = () => import.meta.env.VITE_BASE_URL || '';

/** URL absolue d'un fichier de preuve (servi par l'API, cookie d'authentification). */
export const urlFichier = (chemin, telecharger = false) =>
  chemin ? `${apiBaseUrl()}${chemin}${telecharger ? '?telecharger=1' : ''}` : null;

export const traceService = {
  lister(filtres = {}) {
    const params = Object.fromEntries(Object.entries(filtres).filter(([, v]) => v !== null && v !== '' && v !== undefined));
    return api.get(base, { params });
  },
  lire(uuid) {
    return api.get(`${base}/${uuid}`);
  },
  creer(donnees) {
    return api.post(base, donnees);
  },
  modifier(uuid, donnees) {
    return api.patch(`${base}/${uuid}`, donnees);
  },
  supprimer(uuid) {
    return api.delete(`${base}/${uuid}`);
  },
  deposer(uuid) {
    return api.post(`${base}/${uuid}/deposer`);
  },
  remettreEnBrouillon(uuid) {
    return api.post(`${base}/${uuid}/brouillon`);
  },
  definirRattachements(uuid, rattachements) {
    return api.put(`${base}/${uuid}/rattachements`, { rattachements });
  },
  /** Le type est déduit du format réel par le serveur si `type` est null. */
  ajouterFichier(uuid, fichier, { type = null, legende = null, onProgress = null } = {}) {
    const form = new FormData();
    form.append('fichier', fichier);
    if (type) form.append('type', type);
    if (legende) form.append('legende', legende);
    return api.post(`${base}/${uuid}/medias`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: onProgress ? (e) => onProgress(e.total ? Math.round((e.loaded * 100) / e.total) : 0) : undefined,
    });
  },
  ajouterMedia(uuid, donnees) {
    return api.post(`${base}/${uuid}/medias`, donnees);
  },
  modifierMedia(uuid, id, donnees) {
    return api.patch(`${base}/${uuid}/medias/${id}`, donnees);
  },
  supprimerMedia(uuid, id) {
    return api.delete(`${base}/${uuid}/medias/${id}`);
  },
  ordonnerMedias(uuid, ids) {
    return api.put(`${base}/${uuid}/medias/ordre`, { ids });
  },
  scolarites() {
    return api.get('/api/unifolio/scolarites');
  },
  referentiel(scolariteId = null) {
    return api.get('/api/unifolio/referentiel', { params: scolariteId ? { scolarite: scolariteId } : {} });
  },
};

/** Message d'erreur lisible depuis une réponse 422 de l'API UniFolio. */
export const messageErreur = (e, defaut = 'Une erreur est survenue.') =>
  e?.response?.data?.erreurs?.join(' ') || e?.response?.data?.message || e?.response?.data?.detail || defaut;

export default traceService;
