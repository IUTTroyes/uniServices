<script setup lang="ts">
import { ref, computed } from 'vue';
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
  XMarkIcon,
  PencilIcon,
  TrashIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import { formatDate, formatRelativeTime } from '@/utils/date';

const router = useRouter();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();
const uiStore = useUIStore();

const showTemplates = ref(false);

const templates = [
  {
    id: 'satisfaction',
    title: 'Satisfaction client',
    description: 'Évaluez la satisfaction de vos clients',
    questions: 8,
    icon: HeartIcon
  },
  {
    id: 'employee',
    title: 'Enquête employés',
    description: 'Recueillez les avis de vos collaborateurs',
    questions: 12,
    icon: BuildingOfficeIcon
  },
  {
    id: 'event',
    title: 'Feedback événement',
    description: 'Évaluez votre événement ou formation',
    questions: 10,
    icon: UserGroupIcon
  },
  {
    id: 'product',
    title: 'Étude produit',
    description: 'Testez votre produit ou service',
    questions: 15,
    icon: ShoppingBagIcon
  },
  {
    id: 'education',
    title: 'Évaluation formation',
    description: 'Évaluez l\'efficacité de vos formations',
    questions: 9,
    icon: AcademicCapIcon
  },
  {
    id: 'market',
    title: 'Étude de marché',
    description: 'Analysez votre marché et concurrence',
    questions: 18,
    icon: ChartBarIcon
  }
];

const recentSurveys = computed(() => {
  if (!surveyStore.surveys) return [];
  return [...surveyStore.surveys]
    .sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime())
    .slice(0, 5);
});

const averageCompletionRate = computed(() => {
  if (!surveyStore.publishedSurveys || surveyStore.publishedSurveys.length === 0) return 0;
  const rates = surveyStore.publishedSurveys.map(survey =>
    responseStore.completionRate(survey.uuid)
  );
  return Math.round(rates.reduce((sum, rate) => sum + rate, 0) / rates.length);
});

const recentActivity = computed(() => {
  const activities: any[] = [];
  if (!surveyStore.surveys || !responseStore.responses) return [];

  // Add survey creation activities
  surveyStore.surveys.forEach(survey => {
    activities.push({
      id: `survey-${survey.uuid}`,
      type: 'survey_created',
      message: `Questionnaire "${survey.title}" créé`,
      timestamp: survey.createdAt
    });

    if (survey.status === 'published') {
      activities.push({
        id: `publish-${survey.uuid}`,
        type: 'survey_published',
        message: `Questionnaire "${survey.title}" publié`,
        timestamp: survey.updatedAt
      });
    }
  });

  // Add response activities
  responseStore.responses.forEach(response => {
    if (response.completed) {
      const survey = surveyStore.surveys.find(s => s.uuid === response.surveyId);
      activities.push({
        id: `response-${response.id}`,
        type: 'response_received',
        message: `Nouvelle réponse pour "${survey?.title || 'Questionnaire'}"`,
        timestamp: response.submittedAt || response.lastActivity
      });
    }
  });

  const getTimestamp = (date: any) => new Date(date).getTime();

  return activities
    .sort((a, b) => getTimestamp(b.timestamp) - getTimestamp(a.timestamp))
    .slice(0, 10);
});

function getActivityColor(type: string): string {
  const colors = {
    survey_created: 'bg-blue-500',
    survey_published: 'bg-green-500',
    response_received: 'bg-purple-500'
  };
  return colors[type as keyof typeof colors] || 'bg-gray-500';
}

function getActivityIcon(type: string) {
  const icons = {
    survey_created: PlusIcon,
    survey_published: RocketLaunchIcon,
    response_received: ChatBubbleLeftRightIcon
  };
  return icons[type as keyof typeof icons] || DocumentTextIcon;
}

function duplicateSurvey(survey: any) {
  const duplicate = surveyStore.duplicateSurvey(survey.uuid);
  if (duplicate) {
    uiStore.addNotification('success', 'Questionnaire dupliqué', 'Copie créée avec succès.');
    router.push({ name: 'questionnaire_builder', params: { id: duplicate.uuid } });
  }
}

function deleteSurvey(survey: any) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer "${survey.title}" ?`)) {
    surveyStore.deleteSurvey(survey.uuid);
    uiStore.addNotification('success', 'Questionnaire supprimé', 'Le questionnaire a été supprimé.');
  }
}

function createFromTemplate(template: any) {
  const survey = surveyStore.createSurvey(template.title, template.description);
  showTemplates.value = false;
  uiStore.addNotification(
    'success',
    'Questionnaire créé',
    `Le questionnaire "${template.title}" a été créé à partir du modèle.`
  );
  router.push({ name: 'questionnaire_builder', params: { id: survey.uuid } });
}

function getSurveyStats(surveyUuid: string) {
  const responsesCount = responseStore.completedResponses(surveyUuid).length;
  const analytics = responseStore.getSurveyAnalytics(surveyUuid);
  const invited = analytics.totalInvited || 120;
  const responded = analytics.totalResponses || (surveyUuid.startsWith('active-') ? 85 : 0);
  const rate = Math.round(analytics.completionRate) || (surveyUuid.startsWith('active-') ? Math.round((responded / invited) * 105) : 0);
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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="card p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Questionnaires</p>
          <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ surveyStore.surveyCount }}</p>
        </div>
        <div class="p-3 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
          <DocumentTextIcon class="w-6 h-6" />
        </div>
      </div>

      <div class="card p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Publiés</p>
          <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ surveyStore.publishedSurveys.length }}</p>
        </div>
        <div class="p-3 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400">
          <RocketLaunchIcon class="w-6 h-6" />
        </div>
      </div>

      <div class="card p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Réponses totales</p>
          <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ responseStore.totalResponses }}</p>
        </div>
        <div class="p-3 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400">
          <ChatBubbleLeftRightIcon class="w-6 h-6" />
        </div>
      </div>

      <div class="card p-5 flex items-center justify-between border border-gray-200 dark:border-gray-700">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Taux moyen</p>
          <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ averageCompletionRate }}%</p>
        </div>
        <div class="p-3 rounded-xl bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400">
          <ArrowTrendingUpIcon class="w-6 h-6" />
        </div>
      </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="card flex items-center space-x-4">
        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center">
          <PlusIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
        </div>
        <div class="flex-1">
          <h3 class="font-bold text-gray-900 dark:text-white">Créer un questionnaire</h3>
          <p class="text-xs text-gray-500">Commencer par en créer un nouveau de zéro</p>
        </div>
        <router-link :to="{name: 'questionnaire_builder', params: {id:'new'}}" class="btn-primary px-3 py-1.5 rounded-lg text-sm">
          Créer
        </router-link>
      </div>

      <div class="card flex items-center space-x-4">
        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/40 rounded-lg flex items-center justify-center">
          <DocumentDuplicateIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
        </div>
        <div class="flex-1">
          <h3 class="font-bold text-gray-900 dark:text-white">Modèles pré-définis</h3>
          <p class="text-xs text-gray-500">Créer depuis des modèles existants</p>
        </div>
        <button @click="showTemplates = true" class="btn-secondary bg-green-100 hover:bg-green-200 dark:bg-green-950 text-green-700 dark:text-green-300 px-3 py-1.5 rounded-lg text-sm font-semibold">
          Parcourir
        </button>
      </div>

      <div class="card flex items-center space-x-4">
        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center">
          <ListBulletIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
        </div>
        <div class="flex-1">
          <h3 class="font-bold text-gray-900 dark:text-white">Gestion Détaillée</h3>
          <p class="text-xs text-gray-500">Consulter et filtrer la liste complète</p>
        </div>
        <router-link :to="{name: 'questionnaire_enquetes-liste'}" class="btn-secondary bg-purple-100 hover:bg-purple-200 dark:bg-purple-950 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-lg text-sm font-semibold">
          Voir
        </router-link>
      </div>
    </div>

    <!-- Dynamic Content Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left: My Surveys List (2/3 width) -->
      <div class="lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <DocumentTextIcon class="w-5 h-5 text-blue-500" />
            Mes questionnaires récents
          </h3>
          <router-link :to="{name: 'questionnaire_enquetes-liste'}" class="text-primary-600 dark:text-primary-400 hover:underline text-sm font-semibold">
            Voir tout
          </router-link>
        </div>

        <div class="space-y-4">
          <div 
            v-for="survey in recentSurveys" 
            :key="survey.uuid"
            class="card flex flex-col p-4 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow"
          >
            <!-- Top Row: Title & Actions -->
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0 pr-4">
                <h4 class="font-bold text-gray-900 dark:text-white text-base truncate">{{ survey.title }}</h4>
                <div class="flex items-center space-x-3 mt-1 flex-wrap gap-y-1">
                  <span 
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold',
                      survey.status === 'published' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                    ]"
                  >
                    {{ survey.status === 'published' ? 'Publié' : 'Brouillon' }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    Modifié le {{ formatDate(survey.updatedAt) }}
                  </span>
                </div>
              </div>

              <!-- Actions Pill -->
              <div class="flex items-center space-x-2 shrink-0">
                <router-link 
                  :to="{ name: 'questionnaire_builder', params: { id: survey.uuid } }"
                  class="p-2 text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/30 hover:bg-orange-100 dark:hover:bg-orange-900/50 border border-orange-200 dark:border-orange-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm"
                >
                  <PencilIcon class="w-4 h-4" />
                </router-link>

                <router-link 
                  v-if="survey.status === 'published'"
                  :to="{ name: 'questionnaire_responses', params: { id: survey.uuid } }"
                  class="p-2 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 border border-emerald-200 dark:border-emerald-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm"
                >
                  <ChatBubbleLeftRightIcon class="w-4 h-4" />
                </router-link>

                <router-link 
                  v-if="survey.status === 'published'"
                  :to="{ name: 'questionnaire_analytics', params: { id: survey.uuid } }"
                  class="p-2 text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 border border-purple-200 dark:border-purple-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm"
                >
                  <ChartBarIcon class="w-4 h-4" />
                </router-link>

                <!-- Options Menu -->
                <Menu as="div" class="relative inline-block text-left">
                  <MenuButton class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <EllipsisVerticalIcon class="w-4 h-4" />
                  </MenuButton>
                  <MenuItems class="survey-menu">
                    <MenuItem v-slot="{ active }">
                      <button 
                        @click="duplicateSurvey(survey)"
                        :class="[active ? 'bg-gray-100 dark:bg-gray-700' : '', 'menu-item w-full']"
                      >
                        <DocumentDuplicateIcon class="w-4 h-4" />
                        <span>Dupliquer</span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button 
                        @click="deleteSurvey(survey)"
                        :class="[active ? 'bg-red-50 dark:bg-red-950/30 text-red-600' : '', 'menu-item w-full text-red-600 dark:text-red-400']"
                      >
                        <TrashIcon class="w-4 h-4" />
                        <span>Supprimer</span>
                      </button>
                    </MenuItem>
                  </MenuItems>
                </Menu>
              </div>
            </div>

            <!-- Bottom Row: Progress Bar for active surveys -->
            <div v-if="survey.status === 'published'" class="mt-4 space-y-1.5 border-t border-gray-100 dark:border-gray-700/60 pt-3">
              <div class="flex justify-between text-xs">
                <span class="text-gray-550 dark:text-gray-400">
                  Participation : <strong>{{ getSurveyStats(survey.uuid).responded }}</strong> / {{ getSurveyStats(survey.uuid).invited }}
                </span>
                <span class="font-bold text-gray-900 dark:text-white">{{ getSurveyStats(survey.uuid).rate }}%</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div 
                  class="bg-gradient-to-r from-primary-500 to-primary-600 h-1.5 rounded-full transition-all duration-500"
                  :style="{ width: `${getSurveyStats(survey.uuid).rate}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="recentSurveys.length === 0" class="text-center py-12 card bg-white dark:bg-gray-800">
          <DocumentTextIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p class="text-gray-600 dark:text-gray-400">Aucun questionnaire créé pour le moment</p>
          <router-link :to="{ name: 'questionnaire_builder', params: { id: 'new' } }" class="btn-primary mt-4 inline-block px-4 py-2 rounded-xl">
            Créer votre premier questionnaire
          </router-link>
        </div>
      </div>

      <!-- Right: Summary Configuration & Recent Activity (1/3 width) -->
      <div class="space-y-6">
        <!-- Published Feedback Stats Manager -->
        <div class="card border border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Synthèse étudiants</h3>
          <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
            Configurez les synthèses de résultats publiées et visibles par les étudiants.
          </p>
          <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-850 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Dernière publication :</p>
            <p class="font-bold text-gray-900 dark:text-white mt-0.5">Synthèse Évaluation Semestre 1</p>
            <p class="text-xs text-gray-500 mt-0.5">Publié le 14/02/2026</p>
            <button class="mt-3 text-xs bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 px-3 py-1.5 rounded-lg font-semibold hover:bg-primary-200 transition-colors cursor-pointer border-0">
              Mettre à jour la publication
            </button>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="space-y-4">
          <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <ClockIcon class="w-5 h-5 text-purple-500" />
            Activité récente
          </h3>

          <div class="card space-y-4 max-h-[350px] overflow-y-auto">
            <div 
              v-for="activity in recentActivity" 
              :key="activity.id"
              class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-100 dark:border-gray-800"
            >
              <div 
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs shrink-0',
                  getActivityColor(activity.type)
                ]"
              >
                <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900 dark:text-white font-medium leading-tight">
                  {{ activity.message }}
                </p>
                <p class="text-2xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ formatRelativeTime(activity.timestamp) }}
                </p>
              </div>
            </div>

            <div v-if="recentActivity.length === 0" class="text-center py-8">
              <ClockIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p class="text-gray-600 dark:text-gray-400">Aucune activité récente</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Templates Modal -->
    <div v-if="showTemplates" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 animate-fade-in" @click="showTemplates = false">
      <div 
        class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-4xl mx-4 max-h-[85vh] overflow-y-auto shadow-2xl border border-gray-200 dark:border-gray-700"
        @click.stop
      >
        <div class="flex items-center justify-between mb-6 border-b border-gray-100 dark:border-gray-700 pb-3">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <DocumentDuplicateIcon class="w-6 h-6 text-primary-500" />
            Modèles de questionnaires
          </h2>
          <button 
            @click="showTemplates = false" 
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 transition-colors border-0"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="template in templates" 
            :key="template.id"
            class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-primary-400 hover:shadow-md transition-all duration-200 cursor-pointer flex flex-col justify-between bg-white dark:bg-gray-800"
            @click="createFromTemplate(template)"
          >
            <div>
              <div class="flex items-center space-x-3 mb-3">
                <component :is="template.icon" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                <h3 class="font-bold text-gray-900 dark:text-white">{{ template.title }}</h3>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-normal">
                {{ template.description }}
              </p>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-3 mt-2">
              <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold bg-gray-100 dark:bg-gray-900 px-2 py-0.5 rounded">
                {{ template.questions }} questions
              </span>
              <span class="text-primary-600 dark:text-primary-400 text-xs font-bold hover:underline">Utiliser</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "../../assets/tailwind.css";

.card {
  @apply bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200;
}

.btn-primary {
  @apply bg-primary-600 hover:bg-primary-700 text-white font-semibold transition-all duration-200 cursor-pointer shadow-md hover:shadow-lg active:scale-98;
}

.btn-secondary {
  @apply transition-all duration-200 cursor-pointer active:scale-98;
}

.survey-menu {
  @apply absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50;
}

.menu-item {
  @apply flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-0 bg-transparent text-left;
}

.text-2xs {
  font-size: 0.65rem;
}
</style>
