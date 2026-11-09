/** Structure d'un questionnaire : sections et questions (brouillon uniquement). */
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getQuestionnaireSectionsService = async (id, showToast = false) => {
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
        console.error('Erreur dans getQuestionnaireSectionsService:', error);
        throw error;
    }
}

const createSectionService = async (section, questionnaireUuid, showToast = false) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api/questionnaires/${questionnaireUuid}/questionnaire_sections`,
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
        console.error('Erreur dans createSectionService:', error);
        throw error;
    }
}

const updateSectionService = async (id, section, questionnaireUuid, showToast = false) => {
    try {
        const response = await apiCall(
            api.patch,
            [`/api/questionnaires/${questionnaireUuid}/questionnaire_sections/${id}`,
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
        console.error('Erreur dans updateSectionService:', error);
        throw error;
    }
}

const deleteSectionService = async (id, questionnaireUuid, showToast = false) => {
    try {
        const response = await apiCall(
            api.delete,
            [`/api/questionnaires/${questionnaireUuid}/questionnaire_sections/${id}`],
            'Section de questionnaire supprimée avec succès',
            'Erreur lors de la suppression de la section de questionnaire',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans deleteSectionService:', error);
        throw error;
    }
}

const createQuestionService = async (sectionUuid, question, showToast = false) => {
    try {
        return await apiCall(
          api.post,
          [`/api/questionnaire_sections/${sectionUuid}/questionnaire_questions`,
              { ...question, section: `/api/questionnaire_sections/${sectionUuid}` },
              {
                  headers: {
                      'Content-Type': 'application/ld+json'
                  }
              }],
          'Question créée avec succès',
          'Erreur lors de la création de la question',
          showToast
        );
    } catch (error) {
        console.error('Erreur dans createQuestionService:', error);
        throw error;
    }
}

const updateQuestionService = async (id, question, showToast = false) => {
    try {
        return await apiCall(
          api.patch,
          [`/api/questionnaire_questions/${id}`,
              { ...question },
              {
                  headers: {
                      'Content-Type': 'application/merge-patch+json'
                  }
              }],
          'Question mise à jour avec succès',
          'Erreur lors de la mise à jour de la question',
          showToast
        );
    } catch (error) {
        console.error('Erreur dans updateQuestionService:', error);
        throw error;
    }
}

const deleteQuestionService = async (id, showToast = false) => {
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
        console.error('Erreur dans deleteQuestionService:', error);
        throw error;
    }
}

export {
    getQuestionnaireSectionsService,
    createSectionService,
    updateSectionService,
    deleteSectionService,
    createQuestionService,
    updateQuestionService,
    deleteQuestionService
};
