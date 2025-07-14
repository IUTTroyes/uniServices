import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Response, Participant, SurveyInvitation, SurveyAnalytics } from '@/types/survey';
import { v4 as uuidv4 } from 'uuid';
import { faker } from '@faker-js/faker';

export const useResponseStore = defineStore('responses', () => {
  const responses = ref<Response[]>([]);
  const participants = ref<Participant[]>([]);
  const invitations = ref<SurveyInvitation[]>([]);
  const currentResponse = ref<Response | null>(null);

  // Getters
  const totalResponses = computed(() => responses.value.length);
  
  const responsesBySurvey = computed(() => (surveyId: string) => 
    responses.value.filter(r => r.surveyId === surveyId)
  );

  const completedResponses = computed(() => (surveyId: string) => 
    responses.value.filter(r => r.surveyId === surveyId && r.completed)
  );

  const completionRate = computed(() => (surveyId: string) => {
    const total = responsesBySurvey.value(surveyId).length;
    const completed = completedResponses.value(surveyId).length;
    return total > 0 ? (completed / total) * 100 : 0;
  });

  // Response Management
  function createResponse(surveyId: string, participantId?: string): Response {
    const response: Response = {
      id: uuidv4(),
      surveyId,
      participantId,
      answers: {},
      completed: false,
      startedAt: new Date(),
      lastActivity: new Date()
    };

    responses.value.push(response);
    currentResponse.value = response;
    
    return response;
  }

  function updateResponse(responseId: string, updates: Partial<Response>) {
    const response = responses.value.find(r => r.id === responseId);
    if (response) {
      Object.assign(response, updates, {
        lastActivity: new Date()
      });
      saveToLocalStorage();
    }
  }

  function submitResponse(responseId: string) {
    const response = responses.value.find(r => r.id === responseId);
    if (response) {
      response.completed = true;
      response.submittedAt = new Date();
      response.lastActivity = new Date();
      saveToLocalStorage();
    }
  }

  function saveAnswer(responseId: string, questionId: string, answer: any) {
    const response = responses.value.find(r => r.id === responseId);
    if (response) {
      response.answers[questionId] = answer;
      response.lastActivity = new Date();
      saveToLocalStorage();
    }
  }

  // Participant Management
  function createParticipant(email?: string, name?: string, group?: string): Participant {
    const participant: Participant = {
      id: uuidv4(),
      email,
      name,
      group,
      inviteToken: uuidv4(),
      invitedAt: new Date()
    };

    participants.value.push(participant);
    return participant;
  }

  function importParticipants(csvData: string, surveyId: string): Participant[] {
    const lines = csvData.split('\n').filter(line => line.trim());
    const headers = lines[0].split(',').map(h => h.trim());
    const imported: Participant[] = [];

    for (let i = 1; i < lines.length; i++) {
      const values = lines[i].split(',').map(v => v.trim());
      const participant: Participant = {
        id: uuidv4(),
        inviteToken: uuidv4(),
        invitedAt: new Date()
      };

      headers.forEach((header, index) => {
        const value = values[index];
        if (header.toLowerCase() === 'email') {
          participant.email = value;
        } else if (header.toLowerCase() === 'name') {
          participant.name = value;
        } else if (header.toLowerCase() === 'group') {
          participant.group = value;
        } else {
          if (!participant.customFields) participant.customFields = {};
          participant.customFields[header] = value;
        }
      });

      participants.value.push(participant);
      imported.push(participant);
    }

    return imported;
  }

  // Analytics
  function getSurveyAnalytics(surveyId: string): SurveyAnalytics {
    const surveyResponses = responsesBySurvey.value(surveyId);
    const completedCount = completedResponses.value(surveyId).length;
    const surveyParticipants = participants.value.filter(p => 
      surveyResponses.some(r => r.participantId === p.id)
    );

    const responsesByDate: Record<string, number> = {};
    surveyResponses.forEach(response => {
      if (response.submittedAt) {
        const date = response.submittedAt.toISOString().split('T')[0];
        responsesByDate[date] = (responsesByDate[date] || 0) + 1;
      }
    });

    const totalTimeSpent = surveyResponses
      .filter(r => r.completed && r.submittedAt)
      .reduce((sum, r) => {
        const duration = r.submittedAt!.getTime() - r.startedAt.getTime();
        return sum + duration;
      }, 0);

    return {
      surveyId,
      totalInvited: surveyParticipants.length,
      totalResponses: surveyResponses.length,
      completionRate: completionRate.value(surveyId),
      averageTimeSpent: completedCount > 0 ? totalTimeSpent / completedCount : 0,
      responsesByDate,
      questionAnalytics: {}
    };
  }

  // Demo Data Generation
  function generateDemoData(surveyId: string, count: number = 50) {
    const demoParticipants: Participant[] = [];
    const demoResponses: Response[] = [];
    
    // Clear existing demo data for this survey
    participants.value = participants.value.filter(p => 
      !responses.value.some(r => r.surveyId === surveyId && r.participantId === p.id)
    );
    responses.value = responses.value.filter(r => r.surveyId !== surveyId);

    for (let i = 0; i < count; i++) {
      const participant: Participant = {
        id: uuidv4(),
        email: faker.internet.email(),
        name: faker.person.fullName(),
        group: faker.helpers.arrayElement(['Groupe A', 'Groupe B', 'Groupe C']),
        inviteToken: uuidv4(),
        invitedAt: faker.date.recent({ days: 30 }),
        respondedAt: faker.datatype.boolean(0.7) ? faker.date.recent({ days: 20 }) : undefined
      };

      demoParticipants.push(participant);

      if (participant.respondedAt) {
        const response: Response = {
          id: uuidv4(),
          surveyId,
          participantId: participant.id,
          answers: generateDemoAnswers(),
          completed: faker.datatype.boolean(0.8),
          startedAt: participant.respondedAt,
          lastActivity: faker.date.recent({ days: 1 }),
          submittedAt: faker.datatype.boolean(0.8) ? faker.date.recent({ days: 1 }) : undefined
        };

        demoResponses.push(response);
      }
    }

    participants.value.push(...demoParticipants);
    responses.value.push(...demoResponses);
    saveToLocalStorage();
  }

  function generateDemoAnswers(): Record<string, any> {
    // Generate more realistic demo answers
    return {
      // Satisfaction générale
      'satisfaction_generale': faker.helpers.arrayElement([
        'Très satisfait', 'Satisfait', 'Neutre', 'Insatisfait', 'Très insatisfait'
      ]),
      
      // Services utilisés (choix multiples)
      'services_utilises': faker.helpers.arrayElements([
        'Support client', 'Documentation', 'Formation', 'Consultation', 'Maintenance'
      ], { min: 1, max: 3 }),
      
      // Note sur 10
      'note_service': faker.number.int({ min: 1, max: 10 }),
      
      // Commentaires
      'commentaires': faker.helpers.arrayElement([
        'Service excellent, très professionnel',
        'Quelques améliorations possibles mais globalement satisfait',
        'Réponse rapide et efficace à mes questions',
        'Interface intuitive et facile à utiliser',
        'Support technique très réactif',
        'Documentation claire et complète',
        'Tarifs compétitifs pour la qualité offerte',
        'Équipe à l\'écoute de nos besoins',
        ''
      ]),
      
      // Recommandation
      'recommandation': faker.helpers.arrayElement(['Oui', 'Non', 'Peut-être']),
      
      // Fréquence d'utilisation
      'frequence': faker.helpers.arrayElement([
        'Quotidienne', 'Hebdomadaire', 'Mensuelle', 'Occasionnelle'
      ]),
      
      // Âge
      'age': faker.number.int({ min: 18, max: 75 }),
      
      // Secteur d'activité
      'secteur': faker.helpers.arrayElement([
        'Technologie', 'Santé', 'Éducation', 'Finance', 'Commerce', 'Industrie', 'Services'
      ])
    };
  }

  // Persistence
  function saveToLocalStorage() {
    localStorage.setItem('survey_responses', JSON.stringify(responses.value));
    localStorage.setItem('survey_participants', JSON.stringify(participants.value));
    localStorage.setItem('survey_invitations', JSON.stringify(invitations.value));
  }

  function loadFromLocalStorage() {
    try {
      const savedResponses = localStorage.getItem('survey_responses');
      if (savedResponses) {
        responses.value = JSON.parse(savedResponses).map((r: any) => ({
          ...r,
          startedAt: new Date(r.startedAt),
          lastActivity: new Date(r.lastActivity),
          submittedAt: r.submittedAt ? new Date(r.submittedAt) : undefined
        }));
      }

      const savedParticipants = localStorage.getItem('survey_participants');
      if (savedParticipants) {
        participants.value = JSON.parse(savedParticipants).map((p: any) => ({
          ...p,
          invitedAt: p.invitedAt ? new Date(p.invitedAt) : undefined,
          respondedAt: p.respondedAt ? new Date(p.respondedAt) : undefined
        }));
      }

      const savedInvitations = localStorage.getItem('survey_invitations');
      if (savedInvitations) {
        invitations.value = JSON.parse(savedInvitations).map((i: any) => ({
          ...i,
          sentAt: i.sentAt ? new Date(i.sentAt) : undefined
        }));
      }
    } catch (e) {
      console.error('Failed to load response data from localStorage:', e);
    }
  }

  return {
    // State
    responses,
    participants,
    invitations,
    currentResponse,

    // Getters
    totalResponses,
    responsesBySurvey,
    completedResponses,
    completionRate,

    // Actions
    createResponse,
    updateResponse,
    submitResponse,
    saveAnswer,
    createParticipant,
    importParticipants,
    getSurveyAnalytics,
    generateDemoData,
    saveToLocalStorage,
    loadFromLocalStorage
  };
});