/** Questionnaires : liste, fiche, création, modification, duplication, publication, clôture, réglages du département. */
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getQuestionnairesService = async (page = 1, filters = {}, showToast = false) => {
    try {
        return await apiCall(
          api.get,
          [`/api/questionnaires`, { params: { page, ...filters } }],
          'Questionnaires récupérés avec succès',
          'Erreur lors de la récupération des questionnaires',
          showToast
        );
    } catch (error) {
        console.error('Erreur dans getQuestionnairesService:', error);
        throw error;
    }
}

const getQuestionnaireService = async (id, showToast = false) => {
    try {
        const response = await apiCall(
          api.get,
          [`/api/questionnaires/${id}`],
          'Questionnaire récupéré avec succès',
          'Erreur lors de la récupération du questionnaire',
          showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getQuestionnaireService:', error);
        throw error;
    }
}

const createQuestionnaireService = async (questionnaire, showToast = false) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api/questionnaires`,
                {...questionnaire},
                {
                    headers: {
                        'Content-Type': 'application/ld+json'
                    }
            }],
            'Questionnaire créé avec succès',
            'Erreur lors de la création du questionnaire',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans createQuestionnaireService:', error);
        throw error;
    }
}

const updateQuestionnaireService = async (uuid, questionnaire, showToast = false) => {
    try {
        const response = await apiCall(
            api.patch,
            [`/api/questionnaires/${uuid}`,
                {...questionnaire},
                {
                    headers: {
                        'Content-Type': 'application/merge-patch+json'
                    }
            }],
            'Questionnaire mis à jour avec succès',
            'Erreur lors de la mise à jour du questionnaire',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans updateQuestionnaireService:', error);
        throw error;
    }
}

const deleteQuestionnaireService = async (id, showToast = false) => {
    try {
        const response = await apiCall(
            api.delete,
            [`/api/questionnaires/${id}`],
            'Questionnaire supprimé avec succès',
            'Erreur lors de la suppression du questionnaire',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans deleteQuestionnaireService:', error);
        throw error;
    }
}

const duplicateQuestionnaireService = async (id, newTitle = null, showToast = false) => {
    try {
        return await apiCall(
          api.post,
          [`/api/questionnaires/${id}/duplicate`, { newTitle }],
          'Questionnaire dupliqué avec succès',
          'Erreur lors de la duplication du questionnaire',
          showToast
        );
    } catch (error) {
        console.error('Erreur dans duplicateQuestionnaireService:', error);
        throw error;
    }
}

const getPreviewQuestionnaireService = async (id, showToast = false) => {
    try {
        return await apiCall(
          api.get,
          [`/api/questionnaires/${id}/preview`],
          'Questionnaire récupéré avec succès',
          'Erreur lors de la récupération du questionnaire',
          showToast
        );
    } catch (error) {
        console.error('Erreur dans getPreviewQuestionnaireService:', error);
        throw error;
    }
}

const getPreviewSectionService = async (id, keySection, showToast = false) => {
    try {
        return await apiCall(
          api.get,
          [`/api/questionnaires/${id}/preview/sections/${keySection}`],
          'Section récupérée avec succès',
          'Erreur lors de la récupération de la section',
          showToast
        );
    } catch (error) {
        console.error('Erreur dans getPreviewSectionService:', error);
        throw error;
    }
}

const publishQuestionnaireService = async (uuid, data, showToast = false) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api/questionnaires/${uuid}/publish`,
                data,
                {
                    headers: {
                        'Content-Type': 'application/ld+json'
                    }
                }],
            'Questionnaire publié avec succès',
            'Erreur lors de la publication du questionnaire',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans publishQuestionnaireService:', error);
        throw error;
    }
}

/** Clôture manuelle d'un questionnaire publié (plus aucune réponse acceptée ; bilan envoyé ensuite). */
const cloturerQuestionnaireService = async (surveyUuid, showToast = false) => {
    try {
        return await apiCall(api.post, [`/api/questionnaires/${surveyUuid}/cloture`, {}], 'Questionnaire clôturé', 'Impossible de clôturer le questionnaire', showToast);
    } catch (error) {
        console.error('Erreur dans cloturerQuestionnaireService:', error);
        throw error;
    }
}

/** Réglages des questionnaires d'un département (politique et valeurs par défaut). */
const getReglagesService = async (departementId, showToast = false) => {
    try {
        return await apiCall(api.get, [`/api/questionnaire/reglages/${departementId}`], 'Réglages récupérés', 'Erreur lors de la récupération des réglages', showToast);
    } catch (error) {
        console.error('Erreur dans getReglagesService:', error);
        throw error;
    }
}

/** Mise à jour partielle des réglages : { anonymat?, modification_reponses?, duree_jours?, cloture_auto?, responsable_qualite?, seuil_min_reponses? } */
const updateReglagesService = async (departementId, reglages, showToast = false) => {
    try {
        return await apiCall(api.put, [`/api/questionnaire/reglages/${departementId}`, reglages], 'Réglage enregistré', 'Impossible d\'enregistrer le réglage', showToast);
    } catch (error) {
        console.error('Erreur dans updateReglagesService:', error);
        throw error;
    }
}

export {
    getQuestionnairesService,
    getQuestionnaireService,
    createQuestionnaireService,
    updateQuestionnaireService,
    deleteQuestionnaireService,
    duplicateQuestionnaireService,
    getPreviewQuestionnaireService,
    getPreviewSectionService,
    publishQuestionnaireService,
    cloturerQuestionnaireService,
    getReglagesService,
    updateReglagesService
};
