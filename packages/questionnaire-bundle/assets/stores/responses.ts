import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Response, Participant, SurveyAnalytics } from '@/types/survey';
import {
  getQuestionnaireInvitationsService,
  getAnalyticsService,
  exportExcelService,
  sendRemindersService,
  inviteParticipantsService,
} from '@/requests/questionnaire_service';

/**
 * Suivi des participants d'un questionnaire : uniquement des données du serveur (invitations, relances, statistiques).
 * Une « réponse » correspond à une invitation (identifiant de l'invitation ; le jeton n'est jamais exposé).
 */
export const useResponseStore = defineStore('responses', () => {
  const responses = ref<Response[]>([]);
  const participants = ref<Participant[]>([]);
  const detailedAnalytics = ref<any>(null);

  const responsesBySurvey = computed(() => (surveyId: string) =>
    responses.value.filter(r => r.surveyId === surveyId)
  );

  const completedResponses = computed(() => (surveyId: string) =>
    responses.value.filter(r => r.surveyId === surveyId && r.completed)
  );

  const completionRate = computed(() => (surveyId: string) => {
    const total = responsesBySurvey.value(surveyId).length;
    return total > 0 ? (completedResponses.value(surveyId).length / total) * 100 : 0;
  });

  /** Indicateurs calculés à partir des invitations chargées (fetchSurveyResponses). */
  function getSurveyAnalytics(surveyId: string): SurveyAnalytics {
    const surveyResponses = responsesBySurvey.value(surveyId);
    const completed = surveyResponses.filter(r => r.completed);

    const responsesByDate: Record<string, number> = {};
    completed.forEach(r => {
      if (r.submittedAt) {
        const date = r.submittedAt.toISOString().split('T')[0];
        responsesByDate[date] = (responsesByDate[date] || 0) + 1;
      }
    });

    const durations = completed
      .filter(r => r.submittedAt && r.startedAt)
      .map(r => r.submittedAt!.getTime() - r.startedAt.getTime())
      .filter(d => d > 0);

    return {
      surveyId,
      totalInvited: surveyResponses.length,
      totalResponses: completed.length,
      completionRate: completionRate.value(surveyId),
      averageTimeSpent: durations.length ? durations.reduce((a, b) => a + b, 0) / durations.length : 0,
      responsesByDate,
      questionAnalytics: {},
    };
  }

  async function fetchSurveyResponses(surveyUuid: string) {
    const data = await getQuestionnaireInvitationsService(surveyUuid);
    const members = data?.member || data?.['hydra:member'] || (Array.isArray(data) ? data : []);

    const mappedResponses: Response[] = [];
    const mappedParticipants: Participant[] = [];

    members.forEach((invitation: any) => {
      const id = String(invitation.id);
      const answers: Record<string, any> = {};
      (invitation.questionnaireReponses || []).forEach((ans: any) => {
        if (ans.question?.id !== undefined) {
          answers[ans.question.id] = ans.value;
        }
      });

      const createdAt = invitation.createdAt ? new Date(invitation.createdAt) : new Date();
      const startedAt = invitation.startedAt ? new Date(invitation.startedAt) : undefined;
      const submittedAt = invitation.submittedAt ? new Date(invitation.submittedAt) : undefined;
      const history = (invitation.remindersHistory || []).map((h: any) => ({ ...h, sentAt: new Date(h.sentAt) }));

      mappedResponses.push({
        id,
        surveyId: surveyUuid,
        participantId: id,
        completed: invitation.status === 'submitted',
        submittedAt,
        startedAt: startedAt ?? createdAt,
        lastActivity: submittedAt || startedAt || createdAt,
        answers,
        remindedAt: invitation.remindedAt ? new Date(invitation.remindedAt) : undefined,
        remindersCount: invitation.remindersCount ?? history.length,
        remindersHistory: history,
        metadata: { status: invitation.status, started: !!startedAt },
      });

      mappedParticipants.push({
        id,
        email: invitation.email || undefined,
        name: invitation.email || 'Participant',
        invitedAt: createdAt,
        respondedAt: submittedAt,
      });
    });

    responses.value = [...responses.value.filter(r => r.surveyId !== surveyUuid), ...mappedResponses];
    participants.value = [
      ...participants.value.filter(p => !mappedParticipants.some(mp => mp.id === p.id)),
      ...mappedParticipants,
    ];
  }

  /** Invite de nouveaux participants (e-mails envoyés par le serveur), puis recharge la liste. */
  async function inviteParticipants(surveyUuid: string, emails: string[]) {
    const result = await inviteParticipantsService(surveyUuid, emails);
    await fetchSurveyResponses(surveyUuid);
    return result;
  }

  /** Relance (e-mails envoyés par le serveur) ; l'historique renvoyé par le serveur fait foi. */
  async function sendReminders(surveyUuid: string, invitationIds: string[], subject?: string, message?: string) {
    const res = await sendRemindersService(surveyUuid, invitationIds.map(id => Number(id)), subject, message);
    (res?.invitations || []).forEach((inv: any) => {
      const resp = responses.value.find(r => r.id === String(inv.id));
      if (resp) {
        resp.remindedAt = inv.remindedAt ? new Date(inv.remindedAt) : new Date();
        resp.remindersCount = inv.remindersCount;
        resp.remindersHistory = (inv.remindersHistory || []).map((h: any) => ({ ...h, sentAt: new Date(h.sentAt) }));
      }
    });
    return res;
  }

  async function fetchDetailedAnalytics(surveyUuid: string) {
    detailedAnalytics.value = await getAnalyticsService(surveyUuid);
    return detailedAnalytics.value;
  }

  async function triggerExcelExport(surveyUuid: string) {
    await exportExcelService(surveyUuid);
  }

  return {
    responses,
    participants,
    detailedAnalytics,
    responsesBySurvey,
    completedResponses,
    completionRate,
    getSurveyAnalytics,
    fetchSurveyResponses,
    inviteParticipants,
    sendReminders,
    fetchDetailedAnalytics,
    triggerExcelExport,
  };
});
