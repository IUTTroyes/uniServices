import api from '@helpers/axios';

const base = '/api/unifolio/admin';

export const adminService = {
  grilles() {
    return api.get(`${base}/grilles`);
  },
  grille(uuid) {
    return api.get(`${base}/grilles/${uuid}`);
  },
  creerGrille(donnees) {
    return api.post(`${base}/grilles`, donnees);
  },
  modifierGrille(uuid, donnees) {
    return api.patch(`${base}/grilles/${uuid}`, donnees);
  },
  supprimerGrille(uuid) {
    return api.delete(`${base}/grilles/${uuid}`);
  },
  nouvelleVersion(uuid) {
    return api.post(`${base}/grilles/${uuid}/brouillon`);
  },
  enregistrerBrouillon(uuid, contenu) {
    return api.put(`${base}/grilles/${uuid}/brouillon`, contenu);
  },
  abandonnerBrouillon(uuid) {
    return api.delete(`${base}/grilles/${uuid}/brouillon`);
  },
  publier(uuid) {
    return api.post(`${base}/grilles/${uuid}/publier`);
  },
  dupliquer(uuid, donnees) {
    return api.post(`${base}/grilles/${uuid}/dupliquer`, donnees);
  },
  formations() {
    return api.get(`${base}/formations`);
  },
  parametrerFormation(id, donnees) {
    return api.put(`${base}/formations/${id}`, donnees);
  },
};

export default adminService;
