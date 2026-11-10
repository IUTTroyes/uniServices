import api from '@helpers/axios';

const base = '/api/unifolio/auto-evaluations';

export const autoEvaluationService = {
  bilan(scolariteId = null) {
    return api.get(`${base}/bilan`, { params: scolariteId ? { scolarite: scolariteId } : {} });
  },
  /** cible : { rattachement } pour une trace, { scolarite, type, id } pour un bilan */
  ouvrir(cible) {
    return api.post(base, cible);
  },
  lire(uuid) {
    return api.get(`${base}/${uuid}`);
  },
  enregistrer(uuid, donnees) {
    return api.put(`${base}/${uuid}`, donnees);
  },
  soumettre(uuid) {
    return api.post(`${base}/${uuid}/soumettre`);
  },
  reprendre(uuid) {
    return api.post(`${base}/${uuid}/reprendre`);
  },
  supprimer(uuid) {
    return api.delete(`${base}/${uuid}`);
  },
};

/** Score (0-1) → pourcentage arrondi, ou null. */
export const pourcentage = (score) => (score === null || score === undefined ? null : Math.round(score * 100));

export default autoEvaluationService;
