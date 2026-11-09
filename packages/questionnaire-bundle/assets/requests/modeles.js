/** Bibliothèque de modèles et comparaison des campagnes d'un même modèle. */
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

/** Modèles visibles : ceux des départements gérés et ceux de l'établissement. */
const getModelesService = async () =>
    apiCall(api.get, [`/api/questionnaires`, { params: { estModele: true, pagination: false, order: { title: 'asc' } } }], '', 'Impossible de charger les modèles', false);

/** Enregistre une copie du questionnaire comme modèle ({ title?, departement?: id|null }). */
const enregistrerModeleService = async (uuid, donnees = {}) =>
    apiCall(api.post, [`/api/questionnaires/${uuid}/modele`, donnees], 'Modèle enregistré', 'Impossible d\'enregistrer le modèle', true);

/** Crée un questionnaire (brouillon de l'année en cours) depuis un modèle ({ title?, departement }). */
const instancierModeleService = async (uuid, donnees) =>
    apiCall(api.post, [`/api/questionnaires/${uuid}/instancier`, donnees], 'Questionnaire créé depuis le modèle', 'Impossible d\'utiliser ce modèle', true);

/** Campagnes du même modèle comparées : { modele, campagnes: [...], questions: [{ label, type, valeurs: [...] }] }. */
const getComparaisonService = async (uuid) =>
    apiCall(api.get, [`/api/questionnaires/${uuid}/comparaison`], '', 'Impossible de charger la comparaison', false);

export { getModelesService, enregistrerModeleService, instancierModeleService, getComparaisonService };
