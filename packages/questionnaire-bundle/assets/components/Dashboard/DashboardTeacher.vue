<script setup lang="ts">
import { ref, computed } from 'vue';
import { 
  ChatBubbleLeftRightIcon 
} from '@heroicons/vue/24/outline';
import Kpi from '@components/Kpi.vue';
import Card from '@components/components/Card.vue';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import ScrollPanel from 'primevue/scrollpanel';

const selectedFormation = ref('all');

const teacherCoursesStats = ref([
  {
    formation: 'BUT Informatique A1',
    courseCode: 'R1.01',
    courseName: 'Initiation au Développement (S1)',
    satisfaction: 92,
    responseRate: 84,
    indicators: [
      { name: 'Pédagogie & Clarté', score: 4.6 },
      { name: 'Organisation des TP', score: 4.8 },
      { name: 'Volume horaire adapté', score: 4.1 },
      { name: 'Utilité perçue', score: 4.7 }
    ],
    comments: [
      { text: 'Le cours est très bien structuré, les TPs aident à bien comprendre la matière.', sentiment: 'positive' },
      { text: 'Parfois le rythme est un peu rapide au début pour les débutants.', sentiment: 'neutral' },
      { text: 'Super prof de TP, très disponible pour réexpliquer.', sentiment: 'positive' }
    ]
  },
  {
    formation: 'BUT Informatique A1',
    courseCode: 'R1.02',
    courseName: 'Développement Web (S1)',
    satisfaction: 88,
    responseRate: 78,
    indicators: [
      { name: 'Pédagogie & Clarté', score: 4.3 },
      { name: 'Organisation des TP', score: 4.5 },
      { name: 'Volume horaire adapté', score: 3.9 },
      { name: 'Utilité perçue', score: 4.6 }
    ],
    comments: [
      { text: 'TPs très intéressants et appliqués.', sentiment: 'positive' },
      { text: 'Dommage qu\'on ne puisse pas choisir nos groupes de projet.', sentiment: 'neutral' },
      { text: 'Excellente introduction au HTML/CSS.', sentiment: 'positive' }
    ]
  },
  {
    formation: 'LP Métiers du Multimédia',
    courseCode: 'R3.01',
    courseName: 'Services Web avancés (S2)',
    satisfaction: 79,
    responseRate: 65,
    indicators: [
      { name: 'Pédagogie & Clarté', score: 3.8 },
      { name: 'Organisation des TP', score: 4.2 },
      { name: 'Volume horaire adapté', score: 3.5 },
      { name: 'Utilité perçue', score: 4.3 }
    ],
    comments: [
      { text: 'La théorie est un peu abstraite, mais les projets finaux sont passionnants.', sentiment: 'neutral' },
      { text: 'Besoin de plus d\'exemples pratiques sur les API complexes.', sentiment: 'neutral' },
      { text: 'Le projet de groupe est très formateur.', sentiment: 'positive' }
    ]
  }
]);

const teacherFormations = computed(() => {
  const forms = new Set(teacherCoursesStats.value.map(c => c.formation));
  return Array.from(forms);
});

const filteredTeacherStats = computed(() => {
  if (selectedFormation.value === 'all') {
    return teacherCoursesStats.value;
  }
  return teacherCoursesStats.value.filter(s => s.formation === selectedFormation.value);
});

const averageTeacherSatisfaction = computed(() => {
  const stats = filteredTeacherStats.value;
  if (stats.length === 0) return 0;
  return Math.round(stats.reduce((sum, s) => sum + s.satisfaction, 0) / stats.length);
});

const averageTeacherParticipation = computed(() => {
  const stats = filteredTeacherStats.value;
  if (stats.length === 0) return 0;
  return Math.round(stats.reduce((sum, s) => sum + s.responseRate, 0) / stats.length);
});

const formationOptions = computed(() => [
  { label: 'Toutes les formations', value: 'all' },
  ...teacherFormations.value.map(f => ({ label: f, value: f }))
]);
</script>

<template>
  <div class="space-y-8">
    <!-- Section Filter / Intro -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Synthèse de vos évaluations</h2>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-0.5">Consultez les retours des étudiants pour chaque module et formation dans lesquels vous intervenez.</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Formation :</span>
        <Select 
          v-model="selectedFormation" 
          :options="formationOptions" 
          optionLabel="label" 
          optionValue="value" 
          class="w-64 text-sm" 
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <Kpi
        label="Matières concernées"
        :value="filteredTeacherStats.length"
        description="Modules avec évaluations actives"
        color="indigo"
        :icon="ChatBubbleLeftRightIcon"
      />
      <Kpi
        label="Moyenne de satisfaction"
        :value="averageTeacherSatisfaction + '%'"
        description="Moyenne globale pondérée"
        color="emerald"
        :icon="ChatBubbleLeftRightIcon"
      />
      <Kpi
        label="Taux de participation"
        :value="averageTeacherParticipation + '%'"
        description="Taux moyen de remplissage"
        color="purple"
        :icon="ChatBubbleLeftRightIcon"
      />
    </div>

    <!-- Course Evaluation Blocks -->
    <div class="space-y-6">
      <Card 
        v-for="stat in filteredTeacherStats" 
        :key="stat.courseCode"
        class="border border-gray-250 dark:border-gray-700 hover:shadow-md transition-shadow duration-200"
      >
        <!-- Course Header -->
        <template #header>
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div>
              <div class="flex items-center gap-2.5">
                <Tag :value="stat.formation" severity="info" class="text-xs font-bold" />
                <span class="text-xs text-gray-500 font-mono">{{ stat.courseCode }}</span>
              </div>
              <h3 class="text-xl font-bold mt-1 text-gray-900 dark:text-white">{{ stat.courseName }}</h3>
            </div>

            <!-- Stats Pill -->
            <div class="flex gap-4">
              <div class="text-right">
                <p class="text-xs text-gray-550 dark:text-gray-400">Taux de réponse</p>
                <p class="font-bold text-gray-800 dark:text-gray-200">{{ stat.responseRate }}%</p>
              </div>
              <div class="text-right border-l border-gray-200 dark:border-gray-700 pl-4">
                <p class="text-xs text-gray-550 dark:text-gray-400">Satisfaction</p>
                <p class="font-extrabold text-emerald-600 dark:text-emerald-400">{{ stat.satisfaction }}%</p>
              </div>
            </div>
          </div>
        </template>

        <!-- Stats & Comments Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
          <!-- Visual indicator bar -->
          <div class="lg:col-span-2 space-y-4">
            <h4 class="text-sm font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider">Indicateurs clés :</h4>
            
            <div v-for="indicator in stat.indicators" :key="indicator.name" class="space-y-1">
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400 font-medium">{{ indicator.name }}</span>
                <div class="flex items-center gap-2">
                  <Rating :modelValue="Math.round(indicator.score)" readonly :stars="5" :cancel="false" class="text-xs" />
                  <span class="font-semibold text-gray-900 dark:text-white text-xs">{{ indicator.score }}/5</span>
                </div>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div 
                  class="h-1.5 rounded-full transition-all duration-500"
                  :class="[
                    indicator.score >= 4 ? 'bg-emerald-500' :
                    indicator.score >= 3 ? 'bg-yellow-500' : 'bg-red-500'
                  ]"
                  :style="{ width: `${(indicator.score / 5) * 100}%` }"
                ></div>
              </div>
            </div>
          </div>

          <!-- Comments -->
          <div class="lg:col-span-3 space-y-3">
            <h4 class="text-sm font-bold text-gray-750 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
              <ChatBubbleLeftRightIcon class="w-4 h-4 text-primary-500" />
              Commentaires représentatifs :
            </h4>

            <ScrollPanel style="width: 100%; height: 180px">
              <div class="space-y-2.5 pr-3">
                <div 
                  v-for="(comment, cIdx) in stat.comments" 
                  :key="cIdx"
                  :class="[
                    'p-3 rounded-lg text-sm italic relative border-l-2',
                    comment.sentiment === 'positive' 
                      ? 'bg-green-50/50 dark:bg-green-950/10 border-green-400 text-gray-700 dark:text-gray-300' 
                      : 'bg-yellow-50/50 dark:bg-yellow-950/10 border-yellow-400 text-gray-700 dark:text-gray-300'
                  ]"
                >
                  "{{ comment.text }}"
                </div>
              </div>
            </ScrollPanel>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>

