/** Diffusion ciblée des résultats : destinataires par section, publication, retours des enseignants, « mes résultats ». */
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

/** État de la diffusion (pré-remplie au premier appel) : { etape, validee, finRelecture, publieLe, sections: [...] }. */
const getDiffusionService = async (uuid) =>
    apiCall(api.get, [`/api/questionnaires/${uuid}/diffusion`], '', 'Impossible de charger la diffusion des résultats', false);

/** Destinataires par section ({ [idSection]: [idPersonnel, …] }), validation éventuelle. */
const saveDiffusionService = async (uuid, sections, valider = false) =>
    apiCall(api.put, [`/api/questionnaires/${uuid}/diffusion`, { sections, valider }],
        valider ? 'Diffusion validée' : 'Diffusion enregistrée', 'Impossible d\'enregistrer la diffusion', true);

/** Ajoute les destinataires suggérés manquants (prévisionnel, emploi du temps). */
const preremplirDiffusionService = async (uuid) =>
    apiCall(api.post, [`/api/questionnaires/${uuid}/diffusion/preremplir`, {}], 'Suggestions ajoutées', 'Impossible de recalculer les suggestions', true);

/** Publication des résultats aux destinataires (questionnaire clôturé, diffusion validée). */
const publierResultatsService = async (uuid) =>
    apiCall(api.post, [`/api/questionnaires/${uuid}/resultats/publier`, {}], 'Résultats publiés', 'Impossible de publier les résultats', true);

/** Retours des enseignants (équipe : tous ; destinataire : les siens). */
const getRetoursService = async (uuid) =>
    apiCall(api.get, [`/api/questionnaires/${uuid}/retours`], '', 'Impossible de charger les retours', false);

/** Commentaire et plan d'action d'un destinataire sur une de ses sections. */
const saveRetourService = async (uuid, section, commentaire, planAction) =>
    apiCall(api.put, [`/api/questionnaires/${uuid}/retours`, { section, commentaire, planAction }], 'Retour enregistré', 'Impossible d\'enregistrer le retour', true);

/** Résultats publiés qui concernent la personne connectée. */
const getMesResultatsService = async () =>
    apiCall(api.get, [`/api/questionnaire/mes-resultats`], '', 'Impossible de charger vos résultats', false);

export {
    getDiffusionService,
    saveDiffusionService,
    preremplirDiffusionService,
    publierResultatsService,
    getRetoursService,
    saveRetourService,
    getMesResultatsService,
};
