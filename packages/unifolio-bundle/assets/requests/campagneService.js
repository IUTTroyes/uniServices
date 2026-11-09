import api from '@helpers/axios';

const base = '/api/unifolio/admin/campagnes';

export const campagneService = {
  liste() {
    return api.get(base);
  },
  options() {
    return api.get(`${base}/options`);
  },
  creer(donnees) {
    return api.post(base, donnees);
  },
  lire(uuid) {
    return api.get(`${base}/${uuid}`);
  },
  modifier(uuid, donnees) {
    return api.patch(`${base}/${uuid}`, donnees);
  },
  supprimer(uuid) {
    return api.delete(`${base}/${uuid}`);
  },
  cloturer(uuid) {
    return api.post(`${base}/${uuid}/cloturer`);
  },
  affectations(uuid) {
    return api.get(`${base}/${uuid}/affectations`);
  },
  /** { evaluateur, etudiants?: [ids], groupe?: id } ; sans étudiants ni groupe = toute la campagne */
  affecter(uuid, donnees) {
    return api.post(`${base}/${uuid}/affectations`, donnees);
  },
  desaffecter(uuid, donnees) {
    return api.delete(`${base}/${uuid}/affectations`, { data: donnees });
  },
};

export default campagneService;
