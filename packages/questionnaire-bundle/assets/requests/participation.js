/** Participation : invitations, relances, réponses par jeton, « mes questionnaires » et « mes synthèses ». */
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getQuestionnaireInvitationsService = async (surveyUuid, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/questionnaire_invitations`, { params: { questionnaire: `/api/questionnaires/${surveyUuid}`, pagination: false } }],
            'Invitations récupérées avec succès',
            'Erreur lors de la récupération des invitations',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getQuestionnaireInvitationsService:', error);
        throw error;
    }
}

/** Invite de nouveaux participants à un questionnaire publié : { emails: [...] } → { invitationsCount, emailsCount, ignores }. */
const inviteParticipantsService = async (surveyUuid, emails, showToast = false) => {
    try {
        return await apiCall(
            api.post,
            [`/api/questionnaires/${surveyUuid}/invitations`, { emails }],
            'Invitations envoyées',
            'Erreur lors de l\'envoi des invitations',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans inviteParticipantsService:', error);
        throw error;
    }
}

const sendRemindersService = async (surveyUuid, invitations, subject, message, showToast = false) => {
    try {
        return await apiCall(
            api.post,
            [`/api/questionnaires/${surveyUuid}/reminders`, { invitations, subject, message }],
            'Relances envoyées avec succès',
            'Erreur lors de l\'envoi des relances',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans sendRemindersService:', error);
        throw error;
    }
}

/** Questionnaires auxquels la personne connectée (étudiant ou personnel) est invitée. */
const getMesInvitationsService = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/questionnaire/mes-invitations`],
            'Invitations récupérées avec succès',
            'Erreur lors de la récupération de vos questionnaires',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getMesInvitationsService:', error);
        throw error;
    }
}

const getInvitationService = async (token, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/invitations/${token}`],
            'Invitation récupérée avec succès',
            'Erreur lors de la récupération de l\'invitation',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getInvitationService:', error);
        throw error;
    }
}

const getInvitationSectionService = async (token, sectionId, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/invitations/${token}/sections/${sectionId}`],
            'Section récupérée avec succès',
            'Erreur lors de la récupération de la section',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getInvitationSectionService:', error);
        throw error;
    }
}

const saveInvitationAnswersService = async (token, data, showToast = false) => {
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
        console.error('Erreur dans saveInvitationAnswersService:', error);
        throw error;
    }
}

const submitInvitationService = async (token, showToast = false) => {
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
        console.error('Erreur dans submitInvitationService:', error);
        throw error;
    }
}

export {
    getQuestionnaireInvitationsService,
    inviteParticipantsService,
    sendRemindersService,
    getMesInvitationsService,
    getInvitationService,
    getInvitationSectionService,
    saveInvitationAnswersService,
    submitInvitationService
};
