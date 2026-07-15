import api from '@helpers/axios'
import apiCall from '@helpers/apiCall'

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllQuestionnaires = async (page = 1, filters = {}, showToast = false) => {
    try {
        return await apiCall(
          api.get,
          [`/api/questionnaires`, { params: { page, ...filters } }],
          'Questionnaires récupérés avec succès',
          'Erreur lors de la récupération des questionnaires',
          showToast
        );
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

const getPreviewQuestionnaire = async (id, showToast = false) => {
    try {
        return await apiCall(
          api.get,
          [`/api/questionnaires/${id}/preview`],
          'Questionnaire récupéré avec succès',
          'Erreur lors de la récupération du questionnaire',
          showToast
        );
    } catch (error) {
        console.error('Erreur dans getPreviewQuestionnaire:', error);
        throw error;
    }
}

const getPreviewQuestionnaireSection = async (id, keySection, showToast = false) => {
    try {
        return await apiCall(
          api.get,
          [`/api/questionnaires/${id}/preview/sections/${keySection}`],
          'Section récupérée avec succès',
          'Erreur lors de la récupération de la section',
          showToast
        );
    } catch (error) {
        console.error('Erreur dans getPreviewQuestionnaireSection:', error);
        throw error;
    }
}

const afficheQuestionnaire = async (id, showToast = false) => {
    //calcule et renvoi l'intégralité du questionnaire prêt à être affiché (apercu ou mode réponse).
    // todo: A voir si pas judicieux de faire par section ?
    try {
        const response = await apiCall(
          api.get,
          [`/api/questionnaires/${id}/affiche`],
          'Questionnaire généré et récupéré avec succès',
          'Erreur lors de la récupération du questionnaire',
          showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans afficheQuestionnaire:', error);
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
        console.error('Erreur dans getQuestionnaireSections:', error);
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

const updateSectionQuestionnaire = async (id, section, questionnaireUuid, showToast = false) => {
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
        console.error('Erreur dans createQuestionInSection:', error);
        throw error;
    }
}

const updateQuestionInSection = async (id, question, showToast = false) => {
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
        console.error('Erreur dans updateQuestionInSection:', error);
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
        console.error('Erreur dans deleteQuestionInSection:', error);
        throw error;
    }
}

const publishQuestionnaire = async (uuid, data, showToast = false) => {
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
        console.error('Erreur dans publishQuestionnaire:', error);
        throw error;
    }
}

const getStudentInvitations = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/student/invitations`],
            'Invitations de l\'étudiant récupérées avec succès',
            'Erreur lors de la récupération des invitations',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getStudentInvitations:', error);
        throw error;
    }
}

const getMiniSemestres = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/mini/structure_semestres`],
            'Semestres récupérés avec succès',
            'Erreur lors de la récupération des semestres',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getMiniSemestres:', error);
        throw error;
    }
}

const getAllStatuses = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/statuts`],
            'Statuts récupérés avec succès',
            'Erreur lors de la récupération des statuts',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAllStatuses:', error);
        throw error;
    }
}

const getAllPersonnels = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/personnels`, { params: { pagination: false } }],
            'Personnels récupérés avec succès',
            'Erreur lors de la récupération des personnels',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAllPersonnels:', error);
        throw error;
    }
}

const getStudentSemestres = async (semestreId, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/manage-groupes/etudiant_scolarite_semestres`, { params: { semestre: semestreId, pagination: false } }],
            'Étudiants du semestre récupérés avec succès',
            'Erreur lors de la récupération des étudiants',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getStudentSemestres:', error);
        throw error;
    }
}

const getInvitationByToken = async (token, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/invitations/${token}`],
            'Invitation récupérée avec succès',
            'Erreur lors de la récupération de l\'invitation',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getInvitationByToken:', error);
        throw error;
    }
}

const getInvitationSection = async (token, sectionId, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/invitations/${token}/sections/${sectionId}`],
            'Section récupérée avec succès',
            'Erreur lors de la récupération de la section',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getInvitationSection:', error);
        throw error;
    }
}

const saveInvitationAnswers = async (token, data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/invitations/${token}/answers`, data, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            }],
            'Réponses sauvegardées avec succès',
            'Erreur lors de la sauvegarde des réponses',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans saveInvitationAnswers:', error);
        throw error;
    }
}

const submitInvitation = async (token, showToast = false) => {
    try {
        return await apiCall(
            api.post,
            [`/api/invitations/${token}/submit`, {}, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            }],
            'Questionnaire soumis avec succès',
            'Erreur lors de la soumission du questionnaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans submitInvitation:', error);
        throw error;
    }
}

const getQuestionnaireInvitations = async (surveyUuid, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/questionnaire_invitations?questionnaire=/api/questionnaires/${surveyUuid}`],
            'Invitations récupérées avec succès',
            'Erreur lors de la récupération des invitations',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getQuestionnaireInvitations:', error);
        throw error;
    }
}

const getQuestionnaireAnalytics = async (surveyUuid, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/questionnaires/${surveyUuid}/analytics`],
            'Statistiques du questionnaire récupérées avec succès',
            'Erreur lors de la récupération des statistiques du questionnaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getQuestionnaireAnalytics:', error);
        throw error;
    }
}

const exportQuestionnaireExcel = async (surveyUuid) => {
    try {
        const response = await api.get(`/api/questionnaires/${surveyUuid}/export-excel`, {
            responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Statistiques_${surveyUuid}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Erreur lors de l\'export Excel:', error);
        throw error;
    }
}

export {
    getMiniSemestres,
    getAllStatuses,
    getAllPersonnels,
    getStudentSemestres,
    getStudentInvitations,
    publishQuestionnaire,
    getPreviewQuestionnaire,
    getPreviewQuestionnaireSection,

    afficheQuestionnaire,

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
    deleteQuestionInSection,

    getInvitationByToken,
    getInvitationSection,
    saveInvitationAnswers,
    submitInvitation,
    getQuestionnaireInvitations,
    getQuestionnaireAnalytics,
    exportQuestionnaireExcel
};
