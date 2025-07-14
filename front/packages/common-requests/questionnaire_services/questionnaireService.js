import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllQuestionnaires = async ( showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/questionnaires`],
            'Questionnaires récupérés avec succès',
            'Erreur lors de la récupération des questionnaires',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAllQuestionnaires:', error);
        throw error;
    }
}

const getQuestionnaire = async (id, showToast = false) => {
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
        console.error('Erreur dans getQuestionnaire:', error);
        throw error;
    }
}

const getQuestionnaireSections = async (id, showToast = false) => {
    try {
        const response = await apiCall(
          api.get,
          [`/api/questionnaires/${id}/questionnaire_sections`],
          'Questionnaire récupéré avec succès',
          'Erreur lors de la récupération du questionnaire',
          showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getQuestionnaire:', error);
        throw error;
    }
}

const createQuestionnaire = async (questionnaire, showToast = false) => {
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
        console.error('Erreur dans createQuestionnaire:', error);
        throw error;
    }
}

const deleteQuestionnaire = async (id, showToast = false) => {
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
        console.error('Erreur dans deleteQuestionnaire:', error);
        throw error;
    }
}

const createSectionQuestionnaire = async (section, questionnaireUuid, showToast = false) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api/questionnaire_sections`,
                {...section, questionnaire: `/api/questionnaires/${questionnaireUuid}`},
                {
                    headers: {
                        'Content-Type': 'application/ld+json'
                    }
            }],
            'Section de questionnaire créée avec succès',
            'Erreur lors de la création de la section de questionnaire',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans createSectionQuestionnaire:', error);
        throw error;
    }
}

const updateQuestionnaire = async (uuid, questionnaire, showToast = false) => {
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
        console.error('Erreur dans updateQuestionnaire:', error);
        throw error;
    }
}

const updateSectionQuestionnaire = async (id, section, showToast = false) => {
    try {
        const response = await apiCall(
            api.patch,
            [`/api/questionnaire_sections/${id}`,
                {...section},
                {
                    headers: {
                        'Content-Type': 'application/merge-patch+json'
                    }
            }],
            'Section de questionnaire mise à jour avec succès',
            'Erreur lors de la mise à jour de la section de questionnaire',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans updateSectionQuestionnaire:', error);
        throw error;
    }
}

const deleteSectionQuestionnaire = async (id, showToast = false) => {
    try {
        const response = await apiCall(
            api.delete,
            [`/api/questionnaire_sections/${id}`],
            'Section de questionnaire supprimée avec succès',
            'Erreur lors de la suppression de la section de questionnaire',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans deleteSectionQuestionnaire:', error);
        throw error;
    }
}

const createQuestionInSection = async (sectionUuid, question, showToast = false) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api/questionnaire_questions`,
                {...question, section: `/api/questionnaire_sections/${sectionUuid}`},
                {
                    headers: {
                        'Content-Type': 'application/ld+json'
                    }
            }],
            'Question créée avec succès',
            'Erreur lors de la création de la question',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans createQuestion:', error);
        throw error;
    }
}

const updateQuestionInSection = async (id, question, showToast = false) => {
    try {
        const response = await apiCall(
            api.patch,
            [`/api/questionnaire_questions/${id}`,
                {...question},
                {
                    headers: {
                        'Content-Type': 'application/merge-patch+json'
                    }
            }],
            'Question mise à jour avec succès',
            'Erreur lors de la mise à jour de la question',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans updateQuestion:', error);
        throw error;
    }
}

const deleteQuestionInSection = async (id, showToast = false) => {
    try {
        const response = await apiCall(
            api.delete,
            [`/api/questionnaire_questions/${id}`],
            'Question supprimée avec succès',
            'Erreur lors de la suppression de la question',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans deleteQuestion:', error);
        throw error;
    }
}



export {
    getAllQuestionnaires,
    getQuestionnaire,
    getQuestionnaireSections,

    createQuestionnaire,
    deleteQuestionnaire,
    updateQuestionnaire,

    createSectionQuestionnaire,
    updateSectionQuestionnaire,
    deleteSectionQuestionnaire,

    createQuestionInSection,
    updateQuestionInSection,
    deleteQuestionInSection
};
