import api from '@helpers/axios';

const base = '/api/unifolio/portfolios';
const modeles = '/api/unifolio/admin/modeles';

export const portfolioService = {
  liste() {
    return api.get(base);
  },
  lire(uuid) {
    return api.get(`${base}/${uuid}`);
  },
  donnees(uuid) {
    return api.get(`${base}/${uuid}/donnees`);
  },
  renommer(uuid, titre) {
    return api.patch(`${base}/${uuid}`, { titre });
  },
  ajouterPage(uuid, titre) {
    return api.post(`${base}/${uuid}/pages`, { titre });
  },
  renommerPage(uuid, page, titre) {
    return api.patch(`${base}/${uuid}/pages/${page}`, { titre });
  },
  supprimerPage(uuid, page) {
    return api.delete(`${base}/${uuid}/pages/${page}`);
  },
  ordonnerPages(uuid, uuids) {
    return api.put(`${base}/${uuid}/pages/ordre`, { uuids });
  },
  enregistrerBlocs(uuid, page, blocs, version) {
    return api.put(`${base}/${uuid}/pages/${page}/blocs`, { blocs, version });
  },
};

export const cvService = {
  lire() {
    return api.get('/api/unifolio/cv');
  },
  enregistrer(document) {
    return api.put('/api/unifolio/cv', document);
  },
};

export const modeleService = {
  liste() {
    return api.get(modeles);
  },
  creer(donnees) {
    return api.post(modeles, donnees);
  },
  lire(uuid) {
    return api.get(`${modeles}/${uuid}`);
  },
  modifier(uuid, donnees) {
    return api.patch(`${modeles}/${uuid}`, donnees);
  },
  definirStructure(uuid, pages) {
    return api.put(`${modeles}/${uuid}/structure`, { pages });
  },
  dupliquer(uuid, donnees = {}) {
    return api.post(`${modeles}/${uuid}/dupliquer`, donnees);
  },
  supprimer(uuid) {
    return api.delete(`${modeles}/${uuid}`);
  },
};

/** Liste de messages d'une erreur 422 (une seule requête peut signaler plusieurs anomalies). */
export const messagesErreur = (e, defaut = 'Action impossible.') => {
  const liste = e?.response?.data?.erreurs;
  if (Array.isArray(liste) && liste.length) return liste;

  return [e?.response?.data?.message || e?.response?.data?.detail || defaut];
};
