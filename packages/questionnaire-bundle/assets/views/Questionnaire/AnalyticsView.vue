<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import {
  ArrowDownTrayIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import ActionButtonVertical from '@components/components/ActionButtonVertical.vue';
import { format, subDays } from 'date-fns';
import { fr } from 'date-fns/locale';
import { formatDuration } from '@/utils/date';
import { HeaderComponent, Kpi, Card } from '@components';
import { CheckCircleIcon } from '@heroicons/vue/24/outline';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
);

const route = useRoute();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();

const surveyId = route.params.id as string;
const timeFilter = ref('all');

const survey = computed(() => surveyStore.currentSurvey);
const detailedAnalytics = computed(() => responseStore.detailedAnalytics);

const kpis = computed(() => [
  {
    label: 'Réponses totales',
    value: detailedAnalytics.value?.totalResponses || 0,
    icon: ChartBarIcon,
    iconColorClass: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
  },
  {
    label: 'Taux de complétion',
    value: `${(detailedAnalytics.value?.completionRate || 0).toFixed(1)}%`,
    icon: CheckCircleIcon,
    iconColorClass: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
  },
  {
    label: 'Temps moyen',
    value: formatDuration(detailedAnalytics.value?.averageTimeSpent || 0),
    icon: ClockIcon,
    iconColorClass: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-500 dark:text-yellow-400'
  },
  {
    label: 'Participants uniques',
    value: detailedAnalytics.value?.totalInvited || 0,
    icon: UserGroupIcon,
    iconColorClass: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400'
  }
]);

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(107, 114, 128, 0.1)'
      }
    },
    x: {
      grid: {
        color: 'rgba(107, 114, 128, 0.1)'
      }
    }
  },
  plugins: {
    legend: {
      display: false
    }
  }
};

const questionChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(107, 114, 128, 0.1)'
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  },
  plugins: {
    legend: {
      display: false
    }
  }
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const
    }
  }
};

const responseChartData = computed(() => {
  const startDate = survey.value?.openingDate
    ? new Date(survey.value.openingDate)
    : survey.value?.publishedAt
      ? new Date(survey.value.publishedAt)
      : subDays(new Date(), 7);

  const endDate = new Date();

  const filterDays = timeFilter.value === '7d' ? 7 : timeFilter.value === '30d' ? 30 : timeFilter.value === '90d' ? 90 : null;
  const filteredStart = filterDays ? subDays(endDate, filterDays - 1) : startDate;
  const effectiveStart = filteredStart > startDate ? filteredStart : startDate;

  const dayCount = Math.max(1, Math.round((endDate.getTime() - effectiveStart.getTime()) / (1000 * 60 * 60 * 24)) + 1);

  const dates = Array.from({ length: dayCount }, (_, i) => {
    return subDays(endDate, dayCount - 1 - i);
  });

  const responsesByDate = detailedAnalytics.value?.responsesByDate || {};

  const labels = dates.map(date => format(date, 'dd/MM', { locale: fr }));
  const data = dates.map(date => {
    const key = format(date, 'yyyy-MM-dd');
    return responsesByDate[key] || 0;
  });

  return {
    labels,
    datasets: [
      {
        label: 'Réponses',
        data,
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.3
      }
    ]
  };
});

const statusChartData = computed(() => {
  const statusCounts = detailedAnalytics.value?.statusCounts || { submitted: 0, started: 0, pending: 0 };

  return {
    labels: ['Terminé', 'En cours', 'Non commencé'],
    datasets: [
      {
        data: [statusCounts.submitted, statusCounts.started, statusCounts.pending],
        backgroundColor: ['#10B981', '#F59E0B', '#6B7280'],
        borderWidth: 0
      }
    ]
  };
});

function getChoiceChartData(question: any) {
  const choices = question.stats.choices || [];
  return {
    labels: choices.map((c: any) => c.text),
    datasets: [
      {
        data: choices.map((c: any) => c.count),
        backgroundColor: [
          '#3B82F6',
          '#10B981',
          '#F59E0B',
          '#EF4444',
          '#8B5CF6',
          '#F97316'
        ].slice(0, choices.length),
        borderWidth: 0
      }
    ]
  };
}

function getScaleDistributionChartData(question: any) {
  const distribution = question.stats.distribution || {};
  const labels = Object.keys(distribution);
  const data = Object.values(distribution);

  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor: '#3B82F6',
        borderWidth: 0
      }
    ]
  };
}

function getRankingDistributionChartData(question: any) {
  const ranking = question.stats.ranking || [];
  return {
    labels: ranking.map((r: any) => r.text),
    datasets: [
      {
        label: 'Position moyenne',
        data: ranking.map((r: any) => r.averageRank),
        backgroundColor: '#8B5CF6',
        borderWidth: 0
      }
    ]
  };
}

async function exportAnalytics() {
  await responseStore.triggerExcelExport(surveyId);
}

onMounted(async () => {
  await surveyStore.selectSurvey(surveyId);
  await responseStore.fetchDetailedAnalytics(surveyId);
});
</script>

<template>
  <div>
    <HeaderComponent :titre="`Analyses - ${survey?.title}`"
      description="Explorez les résultats et tendances de votre questionnaire" icon="pi pi-chart-bar">
      <template #actions>
        <ActionButtonVertical :to="{ name: 'questionnaire_responses', params: { id: surveyId } }"
          :icon="ChatBubbleLeftRightIcon" label="Voir les réponses" severity="success" />
        <ActionButtonVertical :icon="ArrowDownTrayIcon" label="Exporter" severity="secondary" @click="exportAnalytics" />
      </template>
    </HeaderComponent>

    <!-- Overview Stats / KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <Kpi label="Réponses totales" :value="detailedAnalytics?.totalResponses || 0" :icon="ChartBarIcon"
        color="blue" />

      <Kpi label="Taux de complétion" :value="(detailedAnalytics?.completionRate || 0).toFixed(1) + '%'"
        :icon="CheckCircleIcon" color="green" />

      <Kpi label="Temps moyen" , :value="formatDuration(detailedAnalytics?.averageTimeSpent || 0)" :icon="ClockIcon"
        color="text-yellow-500" />
      <Kpi label="Participants uniques" , :value="detailedAnalytics?.totalInvited || 0" :icon="UserGroupIcon"
        color="text-purple-500" />
    </div>
  </div>


  <!-- Response Trends -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <Card title="Évolution des réponses" body-class="h-64">
      <Line :data="responseChartData" :options="chartOptions" />
    </Card>

    <Card title="Répartition par statut" body-class="h-64 flex items-center justify-center">
      <Doughnut :data="statusChartData" :options="doughnutOptions" />
    </Card>
  </div>

  <!-- Question Analytics -->
  <div class="space-y-8 mt-8">
    <div v-for="section in detailedAnalytics?.sections" :key="section.sectionInstanceId"
      class="border-t border-gray-200 dark:border-gray-700 pt-8">
      <div class="mb-6">
        <h2 class="card-header flex items-center gap-2">
          {{ section.sectionTitle }}
          <span v-if="section.sectionType === 'configurable'"
            class="inline-flex items-center rounded-md bg-indigo-50 dark:bg-indigo-950/50 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300 ring-1 ring-inset ring-indigo-700/10">
            Instance répétable
          </span>
        </h2>
        <p class="text-sm text-gray-500 mt-1" v-if="section.repeatItemType">
          Sujet évalué : {{ section.repeatItemType }} (ID: {{ section.repeatItemId }})
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card v-for="question in section.questions" :key="question.questionId" :title="question.questionLabel">
          <!-- Single/Multiple Choice Stats & Chart -->
          <div v-if="['single_choice', 'multiple_choice'].includes(question.questionType)">
            <div class="h-48 mb-4">
              <Bar :data="getChoiceChartData(question)" :options="questionChartOptions" />
            </div>
            <div class="space-y-2 mt-4 max-h-48 overflow-y-auto pr-1">
              <div v-for="choice in question.stats.choices" :key="choice.text"
                class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400">
                <span class="truncate pr-4" :title="choice.text">{{ choice.text }}</span>
                <span class="font-medium whitespace-nowrap">{{ choice.count }} réponses ({{ choice.percentage
                  }}%)</span>
              </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
              Total : {{ question.totalResponses }} réponses
            </div>
          </div>

          <!-- Scale Visualization -->
          <div v-else-if="question.questionType === 'scale'">
            <div class="h-48 mb-4">
              <Bar :data="getScaleDistributionChartData(question)" :options="questionChartOptions" />
            </div>
            <div class="flex items-center justify-between mt-3 text-sm text-gray-600 dark:text-gray-400">
              <span>Total : {{ question.totalResponses }} réponses</span>
              <span class="font-semibold">Moyenne : {{ question.stats.average }} / {{ question.stats.max }}</span>
            </div>
          </div>

          <!-- Text Response Summary / Comments -->
          <div v-else-if="['text_short', 'text_long'].includes(question.questionType)">
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4 text-sm bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                <div>
                  <span class="text-gray-500">Réponses :</span>
                  <span class="font-semibold ml-2 text-gray-900 dark:text-white">{{ question.totalResponses }}</span>
                </div>
                <div>
                  <span class="text-gray-500">Longueur moy. :</span>
                  <span class="font-semibold ml-2 text-gray-900 dark:text-white">{{ question.stats.averageLength }}
                    chars</span>
                </div>
              </div>

              <div>
                <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                  Commentaires et réponses :
                </h5>
                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                  <div v-if="!question.stats.samples || question.stats.samples.length === 0"
                    class="text-xs text-gray-500 italic">
                    Aucune réponse textuelle enregistrée.
                  </div>
                  <div v-for="(sample, index) in question.stats.samples" :key="index"
                    class="text-xs text-gray-700 dark:text-gray-300 p-2.5 bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-100 dark:border-gray-700/50 line-clamp-3 hover:line-clamp-none transition-all duration-200">
                    "{{ sample }}"
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Matrix Heatmap/Table -->
          <div v-else-if="question.questionType === 'matrix'">
            <div class="overflow-x-auto">
              <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                  <tr>
                    <th
                      class="p-2 text-left font-medium text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                      Critère</th>
                    <th v-for="col in question.stats.columns" :key="col"
                      class="p-2 text-center font-medium text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                      {{ col }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in question.stats.rows" :key="row"
                    class="border-b border-gray-100 dark:border-gray-800 last:border-none">
                    <td class="p-2 font-medium text-gray-900 dark:text-white">{{ row }}</td>
                    <td v-for="col in question.stats.columns" :key="col"
                      class="p-2 text-center text-gray-600 dark:text-gray-400">
                      <span class="px-2 py-0.5 rounded-full"
                        :class="question.stats.grid[row][col] > 0 ? 'bg-primary-50 dark:bg-primary-950/30 text-primary-700 dark:text-primary-400 font-semibold' : ''">
                        {{ question.stats.grid[row][col] || 0 }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="mt-3 text-xs text-gray-500">
              Total : {{ question.totalResponses }} réponses
            </div>
          </div>

          <!-- Ranking Analysis -->
          <div v-else-if="question.questionType === 'ranking'">
            <div class="h-48 mb-4">
              <Bar :data="getRankingDistributionChartData(question)" :options="questionChartOptions" />
            </div>
            <div class="space-y-2 mt-4 max-h-48 overflow-y-auto pr-1">
              <div v-for="rank in question.stats.ranking" :key="rank.text"
                class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400">
                <span class="truncate pr-4" :title="rank.text">{{ rank.text }}</span>
                <span class="font-medium">Rang moyen : {{ rank.averageRank }}</span>
              </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500">
              Total : {{ question.totalResponses }} réponses
            </div>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>