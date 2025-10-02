<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Tableau de bord
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Gérez vos questionnaires et analysez les résultats
        </p>
      </div>
      <Button asChild v-slot="slotProps" severity="primary">
        <router-link to="/administration/qualite/enquetes/builder/new" :class="slotProps.class">
          <PlusIcon class="w-5 h-5" />
          Nouveau questionnaire
        </router-link>
      </Button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="card h-full">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Questionnaires</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ surveyStore.surveyCount }}
            </p>
          </div>
          <DocumentTextIcon class="w-8 h-8 text-blue-500" />
        </div>
      </div>

      <div class="card h-full">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Publiés</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ surveyStore.publishedSurveys.length }}
            </p>
          </div>
          <RocketLaunchIcon class="w-8 h-8 text-green-500" />
        </div>
      </div>

      <div class="card h-full">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Réponses totales</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ responseStore.totalResponses }}
            </p>
          </div>
          <ChatBubbleLeftRightIcon class="w-8 h-8 text-purple-500" />
        </div>
      </div>

      <div class="card h-full">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Taux moyen</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ averageCompletionRate }}%
            </p>
          </div>
          <ChartBarIcon class="w-8 h-8 text-yellow-500" />
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <div class="card h-full">
        <div class="flex items-center space-x-4">
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
            <PlusIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Créer un questionnaire
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Commencez par créer votre premier questionnaire
            </p>
          </div>
          <Button asChild v-slot="slotProps" severity="primary">
            <router-link to="/administration/qualite/enquetes/builder/new" :class="slotProps.class">
              Créer
            </router-link>
          </Button>
        </div>
      </div>

      <div class="card h-full">
        <div class="flex items-center space-x-4">
          <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
            <DocumentDuplicateIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Modèles
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Utilisez nos modèles prêts à l'emploi
            </p>
          </div>
          <Button severity="help" @click="showTemplates = true">
            Parcourir
          </Button>
        </div>
      </div>

      <div class="card h-full">
        <div class="flex items-center space-x-4">
          <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
            <AcademicCapIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Guide d'utilisation
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Apprenez à utiliser la plateforme
            </p>
          </div>
          <Button severity="secondary">
            Apprendre
          </Button>
        </div>
      </div>
    </div>

    <!-- Recent Surveys -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- My Surveys -->
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Mes questionnaires
          </h2>
          <router-link to="/administration/qualite/enquetes/builder" class="text-primary-600 dark:text-primary-400 hover:underline text-sm">
            Voir tout
          </router-link>
        </div>

        <div class="space-y-4">
          <div
            v-for="survey in recentSurveys"
            :key="survey.id"
            class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
          >
            <div class="flex-1">
              <h3 class="font-medium text-gray-900 dark:text-white">
                {{ survey.titre }}
              </h3>
              <div class="flex items-center space-x-4 mt-1">
                <span
                  :class="[
                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    survey.status === 'published'
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                      : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                  ]"
                >
                  {{ survey.status === 'published' ? 'Publié' : 'Brouillon' }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatDate(survey.updated) }}
                </span>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <router-link
                :to="`/administration/qualite/enquetes/builder/${survey.uuid}`"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                title="Modifier"
              >
                <PencilIcon class="w-4 h-4" />
              </router-link>

              <router-link
                v-if="survey.status === 'published'"
                :to="`/administration/qualite/enquetes/responses/${survey.id}`"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                title="Réponses"
              >
                <ChatBubbleLeftRightIcon class="w-4 h-4" />
              </router-link>

              <router-link
                v-if="survey.status === 'published'"
                :to="`/administration/qualite/enquetes/analytics/${survey.uuid}`"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                title="Statistiques"
              >
                <ChartBarIcon class="w-4 h-4" />
              </router-link>

              <Menu as="div" class="relative">
                <MenuButton class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded">
                  <EllipsisVerticalIcon class="w-4 h-4" />
                </MenuButton>
                <MenuItems class="survey-menu">
                  <MenuItem>
                    <button @click="duplicateSurvey(survey)" class="menu-item w-full">
                      <DocumentDuplicateIcon class="w-4 h-4" />
                      Dupliquer
                    </button>
                  </MenuItem>
                  <MenuItem>
                    <button @click="deleteSurvey(survey)" class="menu-item w-full text-red-600 dark:text-red-400">
                      <TrashIcon class="w-4 h-4" />
                      Supprimer
                    </button>
                  </MenuItem>
                </MenuItems>
              </Menu>
            </div>
          </div>

          <div v-if="recentSurveys.length === 0" class="text-center py-8">
            <DocumentTextIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <p class="text-gray-600 dark:text-gray-400">
              Aucun questionnaire créé pour le moment
            </p>
            <router-link to="/administration/qualite/enquetes/builder/new" class="btn-primary mt-4">
              Créer votre premier questionnaire
            </router-link>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Activité récente
          </h2>
        </div>

        <div class="space-y-4">
          <div
            v-for="activity in recentActivity"
            :key="activity.id"
            class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div
              :class="[
                'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm',
                getActivityColor(activity.type)
              ]"
            >
              <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
            </div>
            <div class="flex-1">
              <p class="text-sm text-gray-900 dark:text-white">
                {{ activity.message }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ formatRelativeTime(activity.timestamp) }}
              </p>
            </div>
          </div>

          <div v-if="recentActivity.length === 0" class="text-center py-8">
            <ClockIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <p class="text-gray-600 dark:text-gray-400">
              Aucune activité récente
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Templates Modal -->
    <div v-if="showTemplates" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="showTemplates = false">
      <div
        class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-4xl mx-4 max-h-[80vh] overflow-y-auto"
        @click.stop
      >
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Modèles de questionnaires
          </h2>
          <button
            @click="showTemplates = false"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="template in templates"
            :key="template.id"
            class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:border-primary-300 dark:hover:border-primary-600 transition-colors cursor-pointer"
            @click="createFromTemplate(template)"
          >
            <div class="flex items-center space-x-3 mb-3">
              <component :is="template.icon" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
              <h3 class="font-medium text-gray-900 dark:text-white">
                {{ template.title }}
              </h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
              {{ template.description }}
            </p>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ template.questions }} questions
              </span>
              <button class="btn-primary text-sm">
                Utiliser
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  PlusIcon,
  DocumentTextIcon,
  RocketLaunchIcon,
  ChatBubbleLeftRightIcon,
  ChartBarIcon,
  DocumentDuplicateIcon,
  AcademicCapIcon,
  PencilIcon,
  EllipsisVerticalIcon,
  TrashIcon,
  ClockIcon,
  XMarkIcon,
  HeartIcon,
  BuildingOfficeIcon,
  UserGroupIcon,
  ShoppingBagIcon,
  AcademicCapIcon as EducationIcon
} from '@heroicons/vue/24/outline';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import { format, formatRelative } from 'date-fns';
import { fr } from 'date-fns/locale';
import type { Survey } from '@/types/survey';

const surveyStore = useSurveyStore();
const responseStore = useResponseStore();
const uiStore = useUIStore();

const showTemplates = ref(false);

const recentSurveys = computed(() =>
  surveyStore.surveys
    .sort((a, b) => b.updated.getTime() - a.updated.getTime())
    .slice(0, 5)
);

const averageCompletionRate = computed(() => {
  const rates = surveyStore.publishedSurveys.map(survey =>
    responseStore.completionRate(survey.id)
  );
  return rates.length > 0
    ? Math.round(rates.reduce((sum, rate) => sum + rate, 0) / rates.length)
    : 0;
});

const recentActivity = computed(() => {
  const activities = [];

  // Add survey creation activities
  surveyStore.surveys.forEach(survey => {
    activities.push({
      id: `survey-${survey.id}`,
      type: 'survey_created',
      message: `Questionnaire "${survey.titre}" créé`,
      timestamp: survey.created
    });

    if (survey.status === 'published') {
      activities.push({
        id: `publish-${survey.id}`,
        type: 'survey_published',
        message: `Questionnaire "${survey.titre}" publié`,
        timestamp: survey.updated
      });
    }
  });

  // Add response activities
  responseStore.responses.forEach(response => {
    if (response.completed) {
      const survey = surveyStore.surveys.find(s => s.id === response.surveyId);
      activities.push({
        id: `response-${response.id}`,
        type: 'response_received',
        message: `Nouvelle réponse pour "${survey?.titre || 'Questionnaire'}"`,
        timestamp: response.submittedAt || response.lastActivity
      });
    }
  });

  return activities
    .sort((a, b) => b.timestamp.getTime() - a.timestamp.getTime())
    .slice(0, 10);
});

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
    icon: EducationIcon
  },
  {
    id: 'market',
    title: 'Étude de marché',
    description: 'Analysez votre marché et concurrence',
    questions: 18,
    icon: ChartBarIcon
  }
];

function formatDate(date: Date): string {
  return format(date, 'dd/MM/yyyy', { locale: fr });
}

function formatRelativeTime(date: Date): string {
  return formatRelative(date, new Date(), { locale: fr });
}

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

function duplicateSurvey(survey: Survey) {
  const duplicate = surveyStore.duplicateSurvey(survey.id);
  if (duplicate) {
    //redirect to the new survey builder
    window.location.href = `/builder/${duplicate.id}`;
  }
}

function deleteSurvey(survey: Survey) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer "${survey.titre}" ?`)) {
    surveyStore.deleteSurvey(survey.uuid);
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
  // Redirect to builder
  window.location.href = `/builder/${survey.id}`;
}

onMounted(async () => {
  await surveyStore.loadFromLocalStorage();
  responseStore.loadFromLocalStorage();
  // Generate demo data if no surveys exist
  // if (surveyStore.surveys.length === 0) {
  //   const demoSurvey = surveyStore.createSurvey(
  //     'Questionnaire de satisfaction client',
  //     'Un exemple de questionnaire pour évaluer la satisfaction de vos clients'
  //   );
  //
  //   // Add some demo questions
  //   const section = demoSurvey.sections[0];
  //   surveyStore.addQuestion(section.id, 'single_choice', 'Comment évaluez-vous notre service ?');
  //   surveyStore.addQuestion(section.id, 'scale', 'Sur une échelle de 1 à 10, quelle note donneriez-vous ?');
  //   surveyStore.addQuestion(section.id, 'text_long', 'Avez-vous des commentaires ou suggestions ?');
  //
  //   surveyStore.setSurveyStatus(demoSurvey.id, 'published');
  //
  //   // Generate demo responses
  //   responseStore.generateDemoData(demoSurvey.id, 25);
  // }
});
</script>

<style scoped>
.survey-menu {
  @apply absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50;
}

.menu-item {
  @apply flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}
</style>
