<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { 
  ClockIcon,
  DocumentTextIcon,
  PlusIcon,
  ListBulletIcon,
  ArrowTrendingUpIcon,
  ChatBubbleLeftRightIcon,
  RocketLaunchIcon,
  DocumentDuplicateIcon,
  HeartIcon,
  BuildingOfficeIcon,
  UserGroupIcon,
  ShoppingBagIcon,
  AcademicCapIcon,
  ChartBarIcon,
  PencilIcon,
  TrashIcon,
  EllipsisVerticalIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import Dialog from 'primevue/dialog';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import { formatDate, formatRelativeTime } from '@/utils/date';
import { Kpi, QuickActionCard, EmptyState, Card } from '@components';
import DuplicateSurveyModal from '@/components/Questionnaire/DuplicateSurveyModal.vue';
import type { Survey } from '@types';

const router = useRouter();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();
const uiStore = useUIStore();

const showTemplates = ref(false);
const showDuplicateModal = ref(false);
const selectedSurveyToDuplicate = ref<Survey | null>(null);

onMounted(async () => {
  if (!surveyStore.surveys || surveyStore.surveys.length === 0) {
    await surveyStore.loadQuestionnaires();
  }
});

const templates = [
  {
    id: 'satisfaction',
    title: 'Satisfaction client',
    description: 'Évaluez la satisfaction globale de vos usagers et bénéficiaires.',
    questions: 8,
    icon: HeartIcon
  },
  {
    id: 'employee',
    title: 'Enquête collaborateurs',
    description: 'Recueillez les avis et feedbacks de vos équipes et enseignants.',
    questions: 12,
    icon: BuildingOfficeIcon
  },
  {
    id: 'event',
    title: 'Feedback événement',
    description: 'Évaluez le déroulement et l\'organisation d\'un événement ou formation.',
    questions: 10,
    icon: UserGroupIcon
  },
  {
    id: 'product',
    title: 'Étude d\'usage',
    description: 'Testez l\'adoption d\'un outil, service ou équipement pédagogique.',
    questions: 15,
    icon: ShoppingBagIcon
  },
  {
    id: 'education',
    title: 'Évaluation des enseignements',
    description: 'Évaluez l\'efficacité pédagogique, le contenu et l\'organisation des cours.',
    questions: 9,
    icon: AcademicCapIcon
  },
  {
    id: 'market',
    title: 'Besoins & Attentes',
    description: 'Analysez les attentes et opportunités d\'évolution des formations.',
    questions: 18,
    icon: ChartBarIcon
  }
];

const recentSurveys = computed<Survey[]>(() => {
  if (!surveyStore.surveys || surveyStore.surveys.length === 0) return [];
  return [...surveyStore.surveys]
    .sort((a, b) => {
      const getValidTime = (d: any) => {
        if (!d) return 0;
        const time = new Date(d).getTime();
        return isNaN(time) ? 0 : time;
      };
      const dateA = Math.max(getValidTime(a.updatedAt), getValidTime(a.createdAt));
      const dateB = Math.max(getValidTime(b.updatedAt), getValidTime(b.createdAt));
      return dateB - dateA;
    })
    .slice(0, 5);
});

const totalResponsesCount = computed(() => {
  if (!surveyStore.surveys) return 0;
  const storeTotal = responseStore.totalResponses;
  const computedTotal = surveyStore.surveys.reduce((sum, s) => sum + (s.totalResponses || 0), 0);
  return Math.max(storeTotal, computedTotal);
});

const averageCompletionRate = computed(() => {
  if (!surveyStore.publishedSurveys || surveyStore.publishedSurveys.length === 0) return 0;
  const rates = surveyStore.publishedSurveys.map(survey => getSurveyStats(survey.uuid).rate);
  return Math.round(rates.reduce((sum, rate) => sum + rate, 0) / rates.length);
});

const lastPublishedSurvey = computed<Survey | null>(() => {
  if (!surveyStore.publishedSurveys || surveyStore.publishedSurveys.length === 0) return null;
  return [...surveyStore.publishedSurveys].sort((a, b) => {
    const dateA = new Date(a.publishedAt || a.updatedAt || a.createdAt || 0).getTime();
    const dateB = new Date(b.publishedAt || b.updatedAt || b.createdAt || 0).getTime();
    return dateB - dateA;
  })[0];
});

interface ActivityItem {
  id: string;
  type: 'survey_created' | 'survey_published' | 'survey_closed' | 'response_received';
  message: string;
  timestamp: Date | string | number;
}

const recentActivity = computed<ActivityItem[]>(() => {
  const activities: ActivityItem[] = [];
  if (!surveyStore.surveys || surveyStore.surveys.length === 0) return [];

  // Add survey activities
  surveyStore.surveys.forEach(survey => {
    const createdDate = survey.createdAt || survey.updatedAt || new Date();
    activities.push({
      id: `survey-create-${survey.uuid || survey.id || Math.random()}`,
      type: 'survey_created',
      message: `Questionnaire "${survey.title || 'Sans titre'}" créé`,
      timestamp: createdDate
    });

    if (survey.status === 'published') {
      activities.push({
        id: `survey-publish-${survey.uuid || survey.id}`,
        type: 'survey_published',
        message: `Questionnaire "${survey.title || 'Sans titre'}" publié`,
        timestamp: survey.publishedAt || survey.updatedAt || survey.createdAt || new Date()
      });
    } else if (survey.status === 'closed') {
      activities.push({
        id: `survey-close-${survey.uuid || survey.id}`,
        type: 'survey_closed',
        message: `Questionnaire "${survey.title || 'Sans titre'}" clôturé`,
        timestamp: survey.closingDate || survey.updatedAt || survey.createdAt || new Date()
      });
    }
  });

  // Add response activities
  if (responseStore.responses && responseStore.responses.length > 0) {
    responseStore.responses.forEach(response => {
      if (response.completed) {
        const survey = surveyStore.surveys.find(s => s.uuid === response.surveyId || s.id === response.surveyId);
        activities.push({
          id: `response-${response.id}`,
          type: 'response_received',
          message: `Nouvelle réponse pour "${survey?.title || 'Questionnaire'}"`,
          timestamp: response.submittedAt || response.lastActivity || new Date()
        });
      }
    });
  }

  const getTimestamp = (date: any) => {
    if (!date) return 0;
    const t = new Date(date).getTime();
    return isNaN(t) ? 0 : t;
  };

  return activities
    .filter(a => getTimestamp(a.timestamp) > 0)
    .sort((a, b) => getTimestamp(b.timestamp) - getTimestamp(a.timestamp))
    .slice(0, 10);
});

function getActivityColor(type: string): string {
  const colors: Record<string, string> = {
    survey_created: 'bg-blue-600 dark:bg-blue-500',
    survey_published: 'bg-emerald-600 dark:bg-emerald-500',
    survey_closed: 'bg-amber-600 dark:bg-amber-500',
    response_received: 'bg-purple-600 dark:bg-purple-500'
  };
  return colors[type] || 'bg-gray-600';
}

function getActivityIcon(type: string) {
  const icons: Record<string, any> = {
    survey_created: PlusIcon,
    survey_published: RocketLaunchIcon,
    survey_closed: CheckCircleIcon,
    response_received: ChatBubbleLeftRightIcon
  };
  return icons[type] || DocumentTextIcon;
}

function openDuplicateModal(survey: Survey) {
  selectedSurveyToDuplicate.value = survey;
  showDuplicateModal.value = true;
}

async function confirmDuplicateSurvey(payload: { newTitle: string }) {
  if (!selectedSurveyToDuplicate.value) return;

  try {
    const duplicate = await surveyStore.duplicateSurvey(
      selectedSurveyToDuplicate.value.uuid,
      payload.newTitle
    );
    showDuplicateModal.value = false;
    selectedSurveyToDuplicate.value = null;
    if (duplicate) {
      uiStore.addNotification('success', 'Questionnaire dupliqué', `"${duplicate.title}" et l'ensemble de ses règles ont été dupliqués.`);
    }
  } catch (error) {
    console.error('Failed to duplicate survey:', error);
    uiStore.addNotification('danger', 'Erreur', 'Une erreur est survenue lors de la duplication.');
  }
}

function deleteSurvey(survey: Survey) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer définitivement "${survey.title}" ?`)) {
    surveyStore.deleteSurvey(survey.uuid);
    uiStore.addNotification('success', 'Questionnaire supprimé', 'Le questionnaire a été supprimé.');
  }
}

async function createFromTemplate(template: any) {
  const survey = await surveyStore.createSurvey(template.title, template.description);
  showTemplates.value = false;
  uiStore.addNotification(
    'success',
    'Questionnaire créé',
    `Le questionnaire "${template.title}" a été créé à partir du modèle.`
  );
  router.push({ name: 'questionnaire_builder', params: { id: survey.uuid } });
}

function getSurveyStats(surveyUuid: string) {
  const survey = surveyStore.surveys?.find(s => s.uuid === surveyUuid);
  const invited = survey?.totalInvited ?? responseStore.responsesBySurvey(surveyUuid).length;
  const responded = survey?.totalResponses ?? responseStore.completedResponses(surveyUuid).length;
  const rate = invited > 0 ? Math.round((responded / invited) * 100) : 0;
  return { 
    responded: Math.min(invited, responded), 
    invited, 
    rate: Math.min(100, rate) 
  };
}
</script>

<template>
  <div class="space-y-8">
    <!-- Quick Stats Banner -->
    <section aria-label="Statistiques clés" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <Kpi label="Questionnaires" :value="surveyStore.surveyCount" :icon="DocumentTextIcon" color="blue" />
      <Kpi label="Publiés" :value="surveyStore.publishedSurveys.length" :icon="RocketLaunchIcon" color="green" />
      <Kpi label="Réponses totales" :value="totalResponsesCount" :icon="ChatBubbleLeftRightIcon" color="teal" />
      <Kpi label="Taux moyen" :value="averageCompletionRate + '%'" :icon="ArrowTrendingUpIcon" color="orange" />
    </section>

    <!-- Quick Actions Row -->
    <section aria-label="Actions rapides" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <QuickActionCard
        title="Créer un questionnaire"
        description="Commencer par en créer un nouveau de zéro"
        :icon="PlusIcon"
        color="blue"
        button-label="Créer"
        :to="{ name: 'questionnaire_builder', params: { id: 'new' } }"
      />

      <QuickActionCard
        title="Modèles pré-définis"
        description="Créer depuis des modèles existants"
        :icon="DocumentDuplicateIcon"
        color="green"
        button-label="Parcourir"
        @action="showTemplates = true"
      />

      <QuickActionCard
        title="Gestion Détaillée"
        description="Consulter et filtrer la liste complète"
        :icon="ListBulletIcon"
        color="purple"
        button-label="Voir la liste"
        :to="{ name: 'questionnaire_enquetes-liste' }"
      />
    </section>

    <!-- Dynamic Content Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left: My Surveys List (2/3 width) -->
      <section aria-labelledby="recent-surveys-title" class="lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between mb-2">
          <h2 id="recent-surveys-title" class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <DocumentTextIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" />
            Mes questionnaires récents
          </h2>
          <router-link 
            :to="{name: 'questionnaire_enquetes-liste'}" 
            class="text-primary-700 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 hover:underline text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-1"
          >
            Voir tout
          </router-link>
        </div>

        <div v-if="recentSurveys.length > 0" class="space-y-4">
          <article 
            v-for="survey in recentSurveys" 
            :key="survey.uuid"
            class="card flex flex-col p-4.5 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-md transition-shadow"
          >
            <!-- Top Row: Title & Actions -->
            <div class="flex items-center justify-between gap-4">
              <div class="flex-1 min-w-0">
                <h3 class="font-bold text-gray-900 dark:text-white text-base truncate">{{ survey.title }}</h3>
                <div class="flex items-center space-x-3 mt-1.5 flex-wrap gap-y-1">
                  <!-- Status Badge WCAG Compliant -->
                  <span 
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                      survey.status === 'published' 
                        ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200 border-emerald-300 dark:border-emerald-800' 
                        : 'bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-200 border-amber-300 dark:border-amber-800'
                    ]"
                  >
                    {{ survey.status === 'published' ? 'Publié' : 'Brouillon' }}
                  </span>
                  <span class="text-xs text-gray-600 dark:text-gray-300 font-medium">
                    Modifié le {{ formatDate(survey.updatedAt || survey.createdAt) }}
                  </span>
                </div>
              </div>

              <!-- Actions Pill -->
              <div class="flex items-center space-x-2 shrink-0">
                <router-link 
                  :to="{ name: 'questionnaire_builder', params: { id: survey.uuid } }"
                  :aria-label="`Modifier le questionnaire ${survey.title}`"
                  v-tooltip.top="'Modifier le questionnaire'"
                  class="p-2 text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-950/40 hover:bg-orange-100 dark:hover:bg-orange-900/60 border border-orange-300 dark:border-orange-800 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                >
                  <PencilIcon class="w-4 h-4" aria-hidden="true" />
                </router-link>

                <router-link 
                  v-if="survey.status === 'published'"
                  :to="{ name: 'questionnaire_responses', params: { id: survey.uuid } }"
                  :aria-label="`Consulter les réponses pour ${survey.title}`"
                  v-tooltip.top="'Consulter les réponses'"
                  class="p-2 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-300 dark:border-emerald-800 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
                  <ChatBubbleLeftRightIcon class="w-4 h-4" aria-hidden="true" />
                </router-link>

                <router-link 
                  v-if="survey.status === 'published'"
                  :to="{ name: 'questionnaire_analytics', params: { id: survey.uuid } }"
                  :aria-label="`Voir les statistiques et analyses pour ${survey.title}`"
                  v-tooltip.top="'Statistiques et analyses'"
                  class="p-2 text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/40 hover:bg-purple-100 dark:hover:bg-purple-900/60 border border-purple-300 dark:border-purple-800 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                  <ChartBarIcon class="w-4 h-4" aria-hidden="true" />
                </router-link>

                <!-- Options Menu Dropdown -->
                <Menu as="div" class="relative inline-block text-left">
                  <MenuButton 
                    :aria-label="`Options supplémentaires pour ${survey.title}`"
                    v-tooltip.top="'Options'"
                    class="p-2 text-gray-800 dark:text-gray-100 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    <EllipsisVerticalIcon class="w-4 h-4" aria-hidden="true" />
                  </MenuButton>
                  <MenuItems class="survey-menu">
                    <MenuItem v-slot="{ active }">
                      <button 
                        @click="openDuplicateModal(survey)"
                        :class="[active ? 'bg-gray-100 dark:bg-gray-700' : '', 'menu-item w-full']"
                        role="menuitem"
                      >
                        <DocumentDuplicateIcon class="w-4 h-4 text-gray-700 dark:text-gray-300" aria-hidden="true" />
                        <span>Dupliquer</span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button 
                        @click="deleteSurvey(survey)"
                        :class="[active ? 'bg-red-50 dark:bg-red-950/40' : '', 'menu-item w-full text-red-700 dark:text-red-400 font-medium']"
                        role="menuitem"
                      >
                        <TrashIcon class="w-4 h-4 text-red-700 dark:text-red-400" aria-hidden="true" />
                        <span>Supprimer</span>
                      </button>
                    </MenuItem>
                  </MenuItems>
                </Menu>
              </div>
            </div>

            <!-- Bottom Row: Progress Bar for active surveys -->
            <div 
              v-if="survey.status === 'published'" 
              class="mt-4 space-y-1.5 border-t border-gray-100 dark:border-gray-700/60 pt-3"
            >
              <div class="flex justify-between text-xs">
                <span class="text-gray-700 dark:text-gray-300 font-medium">
                  Participation : <strong class="text-gray-900 dark:text-white">{{ getSurveyStats(survey.uuid).responded }}</strong> / {{ getSurveyStats(survey.uuid).invited }}
                </span>
                <span class="font-bold text-gray-900 dark:text-white">{{ getSurveyStats(survey.uuid).rate }}%</span>
              </div>
              <div 
                class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden"
                role="progressbar"
                :aria-valuenow="getSurveyStats(survey.uuid).rate"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-label="`Taux de participation : ${getSurveyStats(survey.uuid).rate}%`"
              >
                <div 
                  class="bg-gradient-to-r from-primary-600 to-primary-500 h-2 rounded-full transition-all duration-500"
                  :style="{ width: `${getSurveyStats(survey.uuid).rate}%` }"
                ></div>
              </div>
            </div>
          </article>
        </div>

        <!-- Empty State Component -->
        <EmptyState 
          v-else
          title="Aucun questionnaire créé pour le moment"
          description="Créez votre première enquête ou utilisez un modèle pour débuter rapidement."
          icon="pi pi-file-edit"
          color="blue"
          action-label="Créer votre premier questionnaire"
          action-icon="pi pi-plus"
          @action="router.push({ name: 'questionnaire_builder', params: { id: 'new' } })"
        />
      </section>

      <!-- Right: Summary Configuration & Recent Activity (1/3 width) -->
      <aside aria-label="Informations et activités" class="space-y-6">
        <!-- Published Feedback Stats Manager Card -->
        <Card title="Synthèse étudiants" subtitle="Configuration des synthèses de résultats" icon="pi pi-chart-pie" color="blue">
          <p class="text-gray-700 dark:text-gray-300 text-sm mb-4 leading-relaxed">
            Configurez les synthèses de résultats publiées et visibles par les étudiants.
          </p>
          <div v-if="lastPublishedSurvey" class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-center space-y-1">
            <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold uppercase tracking-wider">Dernière publication</p>
            <p class="font-bold text-gray-900 dark:text-white text-sm">{{ lastPublishedSurvey.title }}</p>
            <p class="text-xs text-gray-600 dark:text-gray-300">
              Publié le {{ formatDate(lastPublishedSurvey.publishedAt || lastPublishedSurvey.updatedAt) }}
            </p>
            <div class="pt-2">
              <router-link 
                :to="{ name: 'questionnaire_analytics', params: { id: lastPublishedSurvey.uuid } }" 
                class="inline-flex items-center gap-1.5 text-xs bg-primary-100 dark:bg-primary-950 text-primary-800 dark:text-primary-200 hover:bg-primary-200 dark:hover:bg-primary-900 border border-primary-300 dark:border-primary-800 px-3 py-1.5 rounded-lg font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <ChartBarIcon class="w-3.5 h-3.5" aria-hidden="true" />
                Voir les résultats
              </router-link>
            </div>
          </div>
          <div v-else class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">Aucun questionnaire publié pour le moment.</p>
          </div>
        </Card>

        <!-- Recent Activity Card -->
        <section aria-labelledby="recent-activity-title" class="space-y-4">
          <h2 id="recent-activity-title" class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <ClockIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" aria-hidden="true" />
            Activité récente
          </h2>

          <div class="card p-4 space-y-3 max-h-[380px] overflow-y-auto border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-2xl">
            <div 
              v-for="activity in recentActivity" 
              :key="activity.id"
              class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-750/50 hover:bg-gray-100 dark:hover:bg-gray-700/60 rounded-xl border border-gray-150 dark:border-gray-700/80 transition-colors"
            >
              <div 
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs shrink-0 shadow-sm',
                  getActivityColor(activity.type)
                ]"
                aria-hidden="true"
              >
                <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900 dark:text-white font-medium leading-snug">
                  {{ activity.message }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 font-medium">
                  {{ formatRelativeTime(activity.timestamp) }}
                </p>
              </div>
            </div>

            <EmptyState 
              v-if="recentActivity.length === 0" 
              title="Aucune activité récente"
              description="Les événements apparaîtront ici."
              compact
            />
          </div>
        </section>
      </aside>
    </div>

    <!-- Templates Modal via PrimeVue Dialog for full A11Y -->
    <Dialog 
      v-model:visible="showTemplates" 
      modal 
      header="Modèles de questionnaires pré-définis" 
      class="w-full max-w-4xl"
      :dismissableMask="true"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-2">
        <button 
          v-for="template in templates" 
          :key="template.id"
          type="button"
          :aria-label="`Créer un questionnaire basé sur le modèle ${template.title}`"
          class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-primary-500 hover:shadow-md transition-all duration-200 text-left flex flex-col justify-between bg-white dark:bg-gray-800 hover:bg-primary-50/20 dark:hover:bg-primary-950/20 cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500"
          @click="createFromTemplate(template)"
        >
          <div>
            <div class="flex items-center space-x-3 mb-3">
              <component :is="template.icon" class="w-6 h-6 text-primary-600 dark:text-primary-400 shrink-0" aria-hidden="true" />
              <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ template.title }}</h3>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 leading-normal">
              {{ template.description }}
            </p>
          </div>
          <div class="flex items-center justify-between border-t border-gray-150 dark:border-gray-700 pt-3 mt-2">
            <span class="text-xs text-gray-700 dark:text-gray-300 font-semibold bg-gray-100 dark:bg-gray-750 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600">
              {{ template.questions }} questions
            </span>
            <span class="text-primary-700 dark:text-primary-400 text-xs font-bold flex items-center gap-1 group-hover:underline">
              Utiliser ce modèle →
            </span>
          </div>
        </button>
      </div>
    </Dialog>

    <!-- Duplicate Survey Modal -->
    <DuplicateSurveyModal
      v-if="showDuplicateModal && selectedSurveyToDuplicate"
      :survey="selectedSurveyToDuplicate"
      @close="showDuplicateModal = false; selectedSurveyToDuplicate = null"
      @confirm="confirmDuplicateSurvey"
    />
  </div>
</template>

<style scoped>
@reference "../../assets/tailwind.css";

.card {
  @apply transition-all duration-200;
}

.survey-menu {
  @apply absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 py-1;
}

.menu-item {
  @apply flex items-center space-x-2 px-3 py-2 text-sm text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-0 bg-transparent text-left cursor-pointer;
}
</style>

