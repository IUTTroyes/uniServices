import api from '@helpers/axios';

const base = '/api/unifolio/evaluations';

export const evaluationService = {
  file() {
    return api.get(`${base}/file`);
  },
  etudiant(campagne, etudiant) {
    return api.get(`${base}/etudiants/${campagne}/${etudiant}`);
  },
  trace(uuid) {
    return api.get(`${base}/traces/${uuid}`);
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
  rendre(uuid, visibleEtudiant = true) {
    return api.post(`${base}/${uuid}/rendre`, { visibleEtudiant });
  },
  demanderRevision(uuid, motif) {
    return api.post(`${base}/${uuid}/revision`, { motif });
  },
  rouvrir(uuid) {
    return api.post(`${base}/${uuid}/rouvrir`);
  },
  supprimer(uuid) {
    return api.delete(`${base}/${uuid}`);
  },
};

const lotUrl = (portee) => (portee.type === 'trace'
  ? `${base}/lot/trace/${portee.uuid}`
  : `${base}/lot/bilans/${portee.campagne}/${portee.etudiant}`);

/**
 * Évaluation en lot : tous les points d'une trace (`{ type: 'trace', uuid }`) ou de l'année d'un étudiant
 * (`{ type: 'bilans', campagne, etudiant }`) en une seule opération.
 */
export const lotService = {
  charger(portee) {
    return api.get(lotUrl(portee));
  },
  /** items : [{ cle, appreciation?, reponses? }] */
  enregistrer(portee, items) {
    return api.put(lotUrl(portee), { items });
  },
  rendre(portee, visibleEtudiant = true) {
    return api.post(`${lotUrl(portee)}/rendre`, { visibleEtudiant });
  },
  rouvrir(portee, cles = null) {
    return api.post(`${lotUrl(portee)}/rouvrir`, cles ? { cles } : {});
  },
  demanderRevision(portee, motif) {
    return api.post(`${lotUrl(portee)}/revision`, { motif });
  },
};

export const commentaireService = {
  lister(traceUuid) {
    return api.get(`/api/unifolio/traces/${traceUuid}/commentaires`);
  },
  ajouter(traceUuid, { contenu, rattachement = null, parent = null }) {
    return api.post(`/api/unifolio/traces/${traceUuid}/commentaires`, { contenu, rattachement, parent });
  },
};

export default evaluationService;
