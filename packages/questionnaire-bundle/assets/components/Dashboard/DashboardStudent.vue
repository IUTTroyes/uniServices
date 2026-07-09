<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import {
  ClockIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ArrowRightIcon,
  CheckIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline';
import { getStudentInvitations } from '@/requests/questionnaire_services/questionnaireService';

const isLoading = ref(false);
const studentPendingSurveys = ref<any[]>([]);
const studentCompletedSurveys = ref<any[]>([]);

const publishedAnalytics = ref([
  {
    id: 'report-1',
    title: 'Rapport d\'analyse & Actions - Évaluation du S1',
    date: '14/02/2026',
    stats: { satisfaction: 85, participation: 74 },
    actions: [
      'Achat de 15 nouveaux PC performants pour la salle réseau (M305).',
      'Ajustement du calendrier des examens du S2 pour limiter la charge de travail hebdomadaire.',
      'Mise à disposition de tutoriels vidéos supplémentaires en programmation.'
    ]
  },
  {
    id: 'report-2',
    title: 'Retour sur l\'enquête d\'intégration des nouveaux étudiants 2025',
    date: '10/10/2025',
    stats: { satisfaction: 91, participation: 88 },
    actions: [
      'Création d\'un système de parrainage pérenne BUT1 - BUT2.',
      'Refonte de l\'intranet d\'accueil pour centraliser les emplois du temps.'
    ]
  }
]);

onMounted(() => {
  loadData();
});

async function loadData() {
  isLoading.value = true;
  try {
    const res = await getStudentInvitations();
    const items = res?.member || res || [];

    studentPendingSurveys.value = items.filter((item: any) => item.status !== 'submitted').map((item: any) => ({
      ...item,
      duration: item.estimatedTime ? Math.ceil(item.estimatedTime / 60) + ' min' : '5 min'
    }));

    studentCompletedSurveys.value = items.filter((item: any) => item.status === 'submitted').map((item: any) => ({
      ...item,
      // Default placeholder date if not available, since submittedAt is not currently on DTO
      submittedAt: item.deadline || 'Récemment'
    }));
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
          <p class="text-xs text-blue-100">À remplir</p>
        </div>
        <div class="text-center bg-white/10 px-4 py-3 rounded-xl backdrop-blur-md">
          <p class="text-2xl font-bold">{{ studentCompletedSurveys.length }}</p>
          <p class="text-xs text-blue-100">Complétés</p>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent">
      </div>
      <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">Chargement de vos enquêtes...</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Pending Surveys -->
      <div class="lg:col-span-2 space-y-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <ClockIcon class="w-5 h-5 text-orange-500" />
          Questionnaires en attente
        </h3>

        <div v-if="studentPendingSurveys.length === 0" class="card text-center py-8">
          <CheckCircleIcon class="w-12 h-12 text-green-500 mx-auto mb-2" />
          <p class="text-gray-700 dark:text-gray-300 font-medium">Félicitations, vous êtes à jour !</p>
          <p class="text-gray-500 dark:text-gray-400 text-sm">Aucun questionnaire en attente.</p>
        </div>

        <div v-else class="space-y-4">
          <div v-for="survey in studentPendingSurveys" :key="survey.uuid"
            class="card hover:shadow-lg transition-shadow border-l-4 border-orange-500 flex flex-col md:flex-row md:items-center justify-between p-5 gap-4">
            <div>
              <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ survey.title }}</h4>
              <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ survey.description }}</p>
              <p class="text-xs mt-2 italic flex items-center gap-1.5" :class="survey.anonymous ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="survey.anonymous ? 'bg-green-500' : 'bg-amber-500'"></span>
                <span v-if="survey.anonymous">L'accès est authentifié pour le suivi mais les réponses resteront anonymes.</span>
                <span v-else>Les réponses à ce questionnaire ne sont pas anonymes.</span>
              </p>
              <div class="flex items-center gap-4 mt-3 flex-wrap">
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400">
                  <ClockIcon class="w-3.5 h-3.5" /> Environ {{ survey.duration }}
                </span>
                <span v-if="survey.deadline"
                  class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-500">
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

          <div v-if="studentCompletedSurveys.length === 0" class="card text-center py-8">
            <CheckCircleIcon class="w-12 h-12 text-green-500 mx-auto mb-2" />
            <p class="text-gray-700 dark:text-gray-300 font-medium">Félicitations, vous êtes à jour !</p>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Aucun questionnaire complété pour le moment.</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="survey in studentCompletedSurveys" :key="survey.uuid"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-750/30 transition-colors">
              <div class="truncate">
                <h4 class="font-bold text-gray-900 dark:text-white truncate">{{ survey.title }}</h4>
                <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Soumis : {{ survey.submittedAt }}</p>
              </div>
              <span
                class="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 text-xs px-2.5 py-1 rounded-full font-bold flex items-center gap-1 shrink-0">
                <CheckIcon class="w-3.5 h-3.5" /> Complété
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Published Analytics / Actions -->
      <div class="space-y-4">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <ChartBarIcon class="w-5 h-5 text-purple-500" />
          Retours & Actions
        </h3>

        <div class="space-y-4">
          <div v-for="analysis in publishedAnalytics" :key="analysis.id"
            class="card overflow-hidden hover:scale-101 transition-transform border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="p-5 border-b border-gray-150 dark:border-gray-700 bg-gray-55 dark:bg-gray-800/40">
              <span
                class="text-xs text-purple-600 dark:text-purple-400 font-bold tracking-wider uppercase bg-purple-100 dark:bg-purple-950 px-2 py-0.5 rounded">Rapport
                de Synthèse</span>
              <h4 class="font-bold text-gray-900 dark:text-white mt-2">{{ analysis.title }}</h4>
              <p class="text-gray-500 dark:text-gray-400 text-xs mt-0.5">Publié le {{ analysis.date }}</p>
            </div>

            <div class="p-5 space-y-4">
              <!-- Custom Mini Stat Cards -->
              <div class="grid grid-cols-2 gap-2">
                <div class="bg-gray-100 dark:bg-gray-700 p-2.5 rounded-lg text-center">
                  <p class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ analysis.stats.satisfaction
                    }}%</p>
                  <p class="text-2xs text-gray-600 dark:text-gray-400 uppercase tracking-tight">Satisfaction globale</p>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 p-2.5 rounded-lg text-center">
                  <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400">{{
                    analysis.stats.participation }}%</p>
                  <p class="text-2xs text-gray-600 dark:text-gray-400 uppercase tracking-tight">Participation</p>
                </div>
              </div>

              <!-- Actions Decided -->
              <div>
                <h5 class="text-xs font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider mb-2">Actions
                  décidées :</h5>
                <ul class="space-y-2">
                  <li v-for="(action, idx) in analysis.actions" :key="idx"
                    class="text-sm text-gray-750 dark:text-gray-300 flex items-start gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 shrink-0"></span>
                    {{ action }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

