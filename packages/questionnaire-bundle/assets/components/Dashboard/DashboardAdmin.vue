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
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import { useUsersStore } from '@stores';
import { hasPermission } from '@utils/permissions';
import { formatDate, formatRelativeTime } from '@/utils/date';
import { Kpi, QuickActionCard, EmptyState, Card } from '@components';
import DuplicateSurveyModal from '@/components/Questionnaire/DuplicateSurveyModal.vue';
import { getAllDepartementsService } from '@requests';
import type { Survey } from '@types';

const router = useRouter();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();
const uiStore = useUIStore();
const userStore = useUsersStore();

const showTemplates = ref(false);
const showDuplicateModal = ref(false);
const selectedSurveyToDuplicate = ref<Survey | null>(null);

// Scope management
const isGlobalQuality = computed(() => {
  return (
    hasPermission('isQualite') ||
    hasPermission('isSuperAdmin') ||
    hasPermission('isDirection') ||
    Boolean(userStore?.isQualite) ||
    Boolean(userStore?.isSuperAdmin) ||
    Boolean(userStore?.isDirection)
  );
});

const isChefDepartement = computed(() => {
  return hasPermission('isChefDepartement') || Boolean(userStore?.isChefDepartement);
});

const currentDepartmentName = computed(() => {
  return userStore?.departementDefaut?.libelle || 
         userStore?.departementDefaut?.sigle || 
         (userStore?.departements?.[0]?.libelle ?? 'Mon Département');
});

const selectedDepartement = ref<string | number>('all');

const allDepartements = ref<any[]>([]);

const departementOptions = computed(() => {
  const list = allDepartements.value.length > 0
    ? allDepartements.value
    : (Array.isArray(userStore?.departements) ? userStore.departements : []);
  const options = [
    { label: 'Tous les départements (Vue Globale IUT)', value: 'all' },
    { label: 'Uniquement IUT (Global)', value: 'global' }
  ];
  list.forEach(dept => {
    if (dept && (dept.id || dept.libelle)) {
      options.push({
        label: dept.libelle || dept.sigle || `Département ${dept.id}`,
        value: dept.id ?? dept.libelle
      });
    }
  });
  return options;
});

onMounted(async () => {
  try {
    const list = await getAllDepartementsService();
    if (Array.isArray(list) && list.length > 0) {
      allDepartements.value = list;
    }
  } catch (e) {
    // fallback to userStore
  }
  if (!surveyStore.surveys || surveyStore.surveys.length === 0) {
    await surveyStore.loadQuestionnaires();
  }
});

const templates = [
  {
    id: 'satisfaction',
    title: 'Satisfaction globale',
    description: 'Évaluez la satisfaction globale des étudiants et usagers sur la formation.',
    questions: 8,
    icon: HeartIcon
  },
  {
    id: 'employee',
    title: 'Enquête collaborateurs',
    description: 'Recueillez les avis et feedbacks de vos équipes pédagogiques et administratives.',
    questions: 12,
    icon: BuildingOfficeIcon
  },
  {
    id: 'event',
    title: 'Feedback événement',
    description: 'Évaluez le déroulement et l\'organisation d\'un projet, stage ou événement.',
    questions: 10,
    icon: UserGroupIcon
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
    title: 'Besoins & Débouchés',
    description: 'Analysez l\'insertion professionnelle et les opportunités d\'évolution.',
    questions: 18,
    icon: ChartBarIcon
  }
];

// Scoped Surveys
const scopedSurveys = computed<Survey[]>(() => {
  if (!surveyStore.surveys || surveyStore.surveys.length === 0) return [];
  if (isGlobalQuality.value && selectedDepartement.value === 'all') {
    return surveyStore.surveys;
  }
  if (selectedDepartement.value === 'global') {
    return surveyStore.surveys.filter(s => !s.departement && !(s as any).departementId);
  }
  if (selectedDepartement.value !== 'all') {
    return surveyStore.surveys.filter(s => {
      const sDept = (s as any).departementId || s.departement?.id;
      return !sDept || String(sDept) === String(selectedDepartement.value);
    });
  }
  return surveyStore.surveys;
});

const scopedPublishedSurveys = computed(() => 
  scopedSurveys.value.filter(s => s.status === 'published')
);

const scopedTotalResponses = computed(() => {
  return scopedSurveys.value.reduce((sum, s) => {
    const stats = getSurveyStats(s.uuid);
    return sum + stats.responded;
  }, 0);
});

const scopedAverageRate = computed(() => {
  if (scopedPublishedSurveys.value.length === 0) return 0;
  const rates = scopedPublishedSurveys.value.map(s => getSurveyStats(s.uuid).rate);
  return Math.round(rates.reduce((sum, r) => sum + r, 0) / rates.length);
});

const lastPublishedSurvey = computed<Survey | null>(() => {
  if (!scopedPublishedSurveys.value || scopedPublishedSurveys.value.length === 0) return null;
  return [...scopedPublishedSurveys.value].sort((a, b) => {
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
  if (!scopedSurveys.value || scopedSurveys.value.length === 0) return [];

  // Add survey activities
  scopedSurveys.value.forEach(survey => {
    const createdDate = survey.createdAt || survey.updatedAt || new Date();
    activities.push({
      id: `survey-create-${survey.uuid || (survey as any).id || Math.random()}`,
      type: 'survey_created',
      message: `Enquête "${survey.title || 'Sans titre'}" créée`,
      timestamp: createdDate
    });

    if (survey.status === 'published') {
      activities.push({
        id: `survey-publish-${survey.uuid || (survey as any).id}`,
        type: 'survey_published',
        message: `Enquête "${survey.title || 'Sans titre'}" publiée`,
        timestamp: survey.publishedAt || survey.updatedAt || survey.createdAt || new Date()
      });
    } else if (survey.status === 'closed') {
      activities.push({
        id: `survey-close-${survey.uuid || (survey as any).id}`,
        type: 'survey_closed',
        message: `Enquête "${survey.title || 'Sans titre'}" clôturée`,
        timestamp: survey.closingDate || survey.updatedAt || survey.createdAt || new Date()
      });
    }
  });

  // Add response activities
  if (responseStore.responses && responseStore.responses.length > 0) {
    responseStore.responses.forEach(response => {
      if (response.completed) {
        const survey = scopedSurveys.value.find(s => s.uuid === response.surveyId || (s as any).id === response.surveyId);
        if (survey) {
          activities.push({
            id: `response-${response.id}`,
            type: 'response_received',
            message: `Nouvelle réponse enregistrée pour "${survey.title || 'Enquête'}"`,
            timestamp: response.submittedAt || response.lastActivity || new Date()
          });
        }
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
      uiStore.addNotification('success', 'Questionnaire dupliqué', `"${duplicate.title}" et ses paramètres ont été dupliqués.`);
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
    `L'enquête "${template.title}" a été créée à partir du modèle.`
  );
  router.push({ name: 'questionnaire_builder', params: { id: survey.uuid } });
}

function getSurveyStats(surveyUuid: string) {
  const survey = surveyStore.surveys?.find(s => s.uuid === surveyUuid);
  const invited = (survey as any)?.totalInvited ?? responseStore.responsesBySurvey(surveyUuid).length;
  const responded = (survey as any)?.totalResponses ?? responseStore.completedResponses(surveyUuid).length;
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
    <!-- Scope / Périmètre de Pilotage Bar -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4.5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
          <BuildingOfficeIcon class="w-6 h-6" />
        </div>
        <div>
          <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Périmètre de pilotage qualité
          </div>
          <div class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mt-0.5">
            <span v-if="isGlobalQuality">Démarche Qualité — Vue Globale IUT</span>
            <span v-else>Département {{ currentDepartmentName }}</span>
            <Tag 
              :value="isGlobalQuality ? 'Tous départements' : 'Département'" 
              :severity="isGlobalQuality ? 'info' : 'secondary'"
              class="text-xs font-semibold"
            />
          </div>
        </div>
      </div>

      <!-- Scope Selector for Global Quality Managers -->
      <div v-if="isGlobalQuality && departementOptions.length > 1" class="flex items-center gap-2.5">
        <label for="dept-scope-select" class="text-xs font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">
          Filtrer par département :
        </label>
        <Select 
          id="dept-scope-select"
          v-model="selectedDepartement" 
          :options="departementOptions" 
          optionLabel="label" 
          optionValue="value" 
          class="w-72 text-sm" 
        />
      </div>
      <div v-else class="text-xs text-gray-500 dark:text-gray-400 italic">
        Vue restreinte aux enquêtes et formations de votre département
      </div>
    </div>

    <!-- Base Quality Metrics Banner -->
    <section aria-label="Statistiques clés" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <Kpi label="Campagnes dans le périmètre" :value="scopedSurveys.length" :icon="DocumentTextIcon" color="blue" />
      <Kpi label="Enquêtes publiées" :value="scopedPublishedSurveys.length" :icon="RocketLaunchIcon" color="green" />
      <Kpi label="Réponses collectées" :value="scopedTotalResponses" :icon="ChatBubbleLeftRightIcon" color="teal" />
      <Kpi label="Taux moyen de retour" :value="scopedAverageRate + '%'" :icon="ArrowTrendingUpIcon" color="orange" />
    </section>

    <!-- Quick Actions Row -->
    <section aria-label="Actions rapides" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <QuickActionCard
        title="Créer une enquête"
        description="Concevoir un nouveau questionnaire d'évaluation"
        :icon="PlusIcon"
        color="blue"
        button-label="Créer"
        :to="{ name: 'questionnaire_builder', params: { id: 'new' } }"
      />

      <QuickActionCard
        title="Modèles pré-définis"
        description="Créer depuis un modèle qualité prêt à l'emploi"
        :icon="DocumentDuplicateIcon"
        color="green"
        button-label="Parcourir les modèles"
        @action="showTemplates = true"
      />

      <QuickActionCard
        title="Tous les questionnaires"
        description="Consulter et administrer la liste exhaustive"
        :icon="ListBulletIcon"
        color="purple"
        button-label="Voir la liste"
        :to="{ name: 'questionnaire_enquetes-liste' }"
      />
    </section>

    <!-- Dynamic Content Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left: Quality Campaigns Synthesis Table (2/3 width) -->
      <section aria-labelledby="campaigns-synthesis-title" class="lg:col-span-2 space-y-4">
        <Card 
          title="Campagnes d'Évaluation Qualité" 
          subtitle="Suivi de la participation et accès direct au suivi détaillé sans ressaisie" 
          icon="pi pi-chart-bar" 
          color="blue"
        >
          <template #header-actions>
            <router-link 
              :to="{ name: 'questionnaire_enquetes-liste' }" 
              class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-1.5 py-0.5"
            >
              <span>Voir toute la liste</span>
              <i class="pi pi-arrow-right text-[10px]" aria-hidden="true"></i>
            </router-link>
          </template>

          <div v-if="scopedSurveys.length > 0" class="overflow-x-auto -mx-5 -mb-5">
            <table class="w-full text-left text-sm border-collapse">
              <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50/70 dark:bg-gray-800/40">
                  <th scope="col" class="py-3 px-5">Enquête / Campagne</th>
                  <th scope="col" class="py-3 px-4">Portée</th>
                  <th scope="col" class="py-3 px-4">Statut</th>
                  <th scope="col" class="py-3 px-4">Participation</th>
                  <th scope="col" class="py-3 px-4">Période</th>
                  <th scope="col" class="py-3 px-5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-150 dark:divide-gray-800 bg-white dark:bg-gray-850">
                <tr 
                  v-for="survey in scopedSurveys" 
                  :key="survey.uuid" 
                  class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors"
                >
                  <td class="py-3.5 px-5">
                    <div class="font-bold text-gray-900 dark:text-white">{{ survey.title }}</div>
                    <div v-if="survey.description" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs mt-0.5">
                      {{ survey.description }}
                    </div>
                  </td>
                  <td class="py-3.5 px-4 whitespace-nowrap">
                    <span
                      v-if="survey.departement"
                      class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold border shadow-2xs"
                      :style="{
                        backgroundColor: survey.departement.couleur ? `${survey.departement.couleur}15` : '#f0fdf4',
                        color: survey.departement.couleur || '#16a34a',
                        borderColor: survey.departement.couleur ? `${survey.departement.couleur}40` : '#bbf7d0'
                      }"
                    >
                      <span
                        class="w-1.5 h-1.5 rounded-full shrink-0"
                        :style="{ backgroundColor: survey.departement.couleur || '#16a34a' }"
                      ></span>
                      <span>{{ survey.departement.libelle || `Dpt ${survey.departement.id}` }}</span>
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
                    >
                      <span>IUT (Global)</span>
                    </span>
                  </td>
                  <td class="py-3.5 px-4 whitespace-nowrap">
                    <Tag 
                      :value="survey.status === 'published' ? 'Publiée' : survey.status === 'closed' ? 'Clôturée' : 'Brouillon'"
                      :severity="survey.status === 'published' ? 'success' : survey.status === 'closed' ? 'secondary' : 'warn'"
                      class="text-xs font-semibold"
                    />
                  </td>
                  <td class="py-3.5 px-4 min-w-[170px]">
                    <div class="flex items-center justify-between text-xs mb-1">
                      <span class="text-gray-600 dark:text-gray-300 font-medium">
                        {{ getSurveyStats(survey.uuid).responded }} / {{ getSurveyStats(survey.uuid).invited }}
                      </span>
                      <span class="font-bold text-gray-900 dark:text-white">
                        {{ getSurveyStats(survey.uuid).rate }}%
                      </span>
                    </div>
                    <div 
                      class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden"
                      role="progressbar"
                      :aria-valuenow="getSurveyStats(survey.uuid).rate"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    >
                      <div 
                        class="h-1.5 rounded-full transition-all duration-500"
                        :class="[
                          getSurveyStats(survey.uuid).rate >= 60 ? 'bg-emerald-500' :
                          getSurveyStats(survey.uuid).rate >= 30 ? 'bg-amber-500' : 'bg-primary-500'
                        ]"
                        :style="{ width: `${getSurveyStats(survey.uuid).rate}%` }"
                      ></div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                    <span v-if="survey.closingDate">Jusqu'au {{ formatDate(survey.closingDate) }}</span>
                    <span v-else-if="survey.publishedAt">Publiée le {{ formatDate(survey.publishedAt) }}</span>
                    <span v-else>Créée le {{ formatDate(survey.createdAt) }}</span>
                  </td>
                  <td class="py-3.5 px-5 text-right whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                      <!-- Direct Link to existing Responses & Reminders (No duplication!) -->
                      <router-link 
                        v-if="survey.status === 'published'"
                        :to="{ name: 'questionnaire_responses', params: { id: survey.uuid } }"
                        v-tooltip.top="'Suivi des répondants & Relances'"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-300 dark:border-emerald-800 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500"
                      >
                        <i class="pi pi-users text-xs" aria-hidden="true"></i>
                        <span class="hidden sm:inline">Suivi & Relances</span>
                      </router-link>

                      <!-- Direct Link to Analytics -->
                      <router-link 
                        v-if="survey.status === 'published'"
                        :to="{ name: 'questionnaire_analytics', params: { id: survey.uuid } }"
                        v-tooltip.top="'Synthèse des résultats'"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/40 hover:bg-purple-100 dark:hover:bg-purple-900/60 border border-purple-300 dark:border-purple-800 transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500"
                      >
                        <i class="pi pi-chart-pie text-xs" aria-hidden="true"></i>
                        <span class="hidden sm:inline">Résultats</span>
                      </router-link>

                      <!-- Edit Builder -->
                      <router-link 
                        :to="{ name: 'questionnaire_builder', params: { id: survey.uuid } }"
                        v-tooltip.top="'Modifier le questionnaire'"
                        class="p-1.5 text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-950/40 hover:bg-orange-100 dark:hover:bg-orange-900/60 border border-orange-200 dark:border-orange-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500"
                      >
                        <PencilIcon class="w-4 h-4" aria-hidden="true" />
                      </router-link>

                      <!-- Dropdown menu for duplicate / delete -->
                      <Menu as="div" class="relative inline-block text-left">
                        <MenuButton 
                          v-tooltip.top="'Plus d\'options'"
                          class="p-1.5 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500"
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
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <EmptyState 
            v-else
            title="Aucune campagne d'enquête pour ce périmètre"
            description="Créez votre première enquête ou utilisez un modèle pour débuter."
            icon="pi pi-chart-bar"
            color="blue"
            action-label="Créer une enquête"
            action-icon="pi pi-plus"
            @action="router.push({ name: 'questionnaire_builder', params: { id: 'new' } })"
          />
        </Card>
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

