<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  ClockIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ArrowRightIcon,
  CheckIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline';
import { getMesInvitationsService, getMesResultatsService } from '@/requests/questionnaire_service';
import { EmptyState, ListSkeleton } from '@components';
import { hasPermission } from '@utils/permissions';
import { formatDate } from '@/utils/date';

const isLoading = ref(false);
const studentPendingSurveys = ref<any[]>([]);
const studentCompletedSurveys = ref<any[]>([]);
const publishedAnalytics = ref<any[]>([]);
const estPersonnel = computed(() => hasPermission('isPersonnel'));

onMounted(() => {
  loadData();
});

async function loadData() {
  isLoading.value = true;
  try {
    const res = await getMesInvitationsService();
    const items = res?.member || res || [];

    // À remplir : non envoyés et encore ouverts aux réponses.
    studentPendingSurveys.value = items.filter((item: any) => item.status !== 'submitted' && item.ouvert).map((item: any) => ({
      ...item,
      duration: item.estimatedTime ? Math.ceil(item.estimatedTime / 60) + ' min' : ''
    }));

    studentCompletedSurveys.value = items.filter((item: any) => item.status === 'submitted').map((item: any) => ({
      ...item,
      submittedAt: item.submittedAt ? formatDate(item.submittedAt) : ''
    }));
    if (estPersonnel.value) {
      const resultats = await getMesResultatsService();
      publishedAnalytics.value = resultats?.member || resultats || [];
    }
  } catch (error) {
    console.error('Failed to load student invitations:', error);
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div class="space-y-8">
    <!-- Banner Info -->
    <div
      class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
      <div class="z-10">
        <h2 class="text-2xl font-bold mt-2 text-white">Votre avis compte pour améliorer nos formations !</h2>
        <p class="text-blue-100 mt-1 max-w-xl">
          Prenez quelques minutes pour évaluer vos enseignements. Vos réponses sont totalement anonymes et permettent
          d'adapter les cours et équipements.
        </p>
      </div>
      <div class="flex items-center gap-4 z-10 shrink-0">
        <div class="text-center bg-white/10 px-4 py-3 rounded-xl backdrop-blur-md">
          <p class="text-2xl font-bold">{{ studentPendingSurveys.length }}</p>
          <p class="text-sm text-blue-100">À remplir</p>
        </div>
        <div class="text-center bg-white/10 px-4 py-3 rounded-xl backdrop-blur-md">
          <p class="text-2xl font-bold">{{ studentCompletedSurveys.length }}</p>
          <p class="text-sm text-blue-100">Complétés</p>
        </div>
      </div>
    </div>

    <ListSkeleton v-if="isLoading" />

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Pending Surveys -->
      <div class="lg:col-span-2 space-y-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <ClockIcon class="w-5 h-5 text-orange-500" />
          Questionnaires en attente
        </h3>

        <EmptyState v-if="studentPendingSurveys.length === 0" :icon="CheckCircleIcon" color="emerald" title="Vous êtes à jour" description="Aucun questionnaire en attente de votre réponse." />

        <div v-else class="space-y-4">
          <div v-for="survey in studentPendingSurveys" :key="survey.uuid"
            class="card hover:shadow-lg transition-shadow border-l-4 border-orange-500 flex flex-col md:flex-row md:items-center justify-between p-5 gap-4">
            <div>
              <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ survey.title }}</h4>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ survey.description }}</p>
              <p class="text-sm mt-2 italic flex items-center gap-1.5" :class="survey.anonymous ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="survey.anonymous ? 'bg-green-500' : 'bg-amber-500'"></span>
                <span v-if="survey.anonymous">L'accès est authentifié pour le suivi mais les réponses resteront anonymes.</span>
                <span v-else>Les réponses à ce questionnaire ne sont pas anonymes.</span>
              </p>
              <div class="flex items-center gap-4 mt-3 flex-wrap">
                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 dark:text-gray-400">
                  <ClockIcon class="w-3.5 h-3.5" /> Environ {{ survey.duration }}
                </span>
                <span v-if="survey.deadline"
                  class="inline-flex items-center gap-1.5 text-sm font-semibold text-red-500">
                  <ExclamationCircleIcon class="w-3.5 h-3.5" /> Clôture : {{ survey.deadline }}
                </span>
               
              </div>
            </div>
            <router-link :to="{ name: 'questionnaire_take-survey', params: { token: survey.token } }"
              class="btn-primary flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl self-start md:self-auto shrink-0 animate-pulse hover:animate-none">
              Répondre
              <ArrowRightIcon class="w-4 h-4" />
            </router-link>
          </div>
        </div>

        <!-- Answered Surveys -->
        <div class="pt-6 space-y-4">
          <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <CheckCircleIcon class="w-5 h-5 text-green-500" />
            Questionnaires répondus
          </h3>

          <EmptyState v-if="studentCompletedSurveys.length === 0" compact title="Aucun questionnaire rempli pour le moment" />

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="survey in studentCompletedSurveys" :key="survey.uuid"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-750/30 transition-colors">
              <div class="truncate">
                <h4 class="font-bold text-gray-900 dark:text-white truncate">{{ survey.title }}</h4>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Soumis : {{ survey.submittedAt }}</p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <router-link v-if="survey.modifiable" :to="{ name: 'questionnaire_take-survey', params: { token: survey.token } }">
                  <Button icon="pi pi-pencil" label="Modifier" size="small" text severity="secondary" />
                </router-link>
                <Tag value="Complété" severity="success" icon="pi pi-check" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mes résultats : questionnaires dont les résultats sont publiés pour moi (diffusion ciblée) -->
      <div v-if="estPersonnel" class="space-y-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <ChartBarIcon class="w-5 h-5 text-purple-500" />
          Mes résultats
        </h3>
        <EmptyState
          v-if="publishedAnalytics.length === 0"
          compact
          icon="pi pi-chart-bar"
          title="Aucun résultat publié pour vous"
          description="Les résultats des questionnaires qui concernent vos enseignements apparaîtront ici après leur publication."
        />
        <ul v-else class="space-y-2">
          <li v-for="analysis in publishedAnalytics" :key="analysis.uuid"
            class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
            <div>
              <p class="font-semibold text-gray-900 dark:text-white">{{ analysis.title }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ analysis.sections.length }} section(s)<span v-if="analysis.anneeUniversitaire"> · {{ analysis.anneeUniversitaire }}</span>
              </p>
            </div>
            <router-link :to="{ name: 'questionnaire_mes_resultats', params: { id: analysis.uuid } }">
              <Button label="Consulter" icon="pi pi-chart-bar" size="small" text />
            </router-link>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

