<script setup lang="ts">
import {ref} from 'vue'

const matieres = ref([
  {
    id: 1,
    display: 'Mathématiques',
    evaluations: [
      {
        id: 101,
        title: 'Examen mi-semestre',
        type: 'Examen',
        status: 'incomplete' as const,
        visibility: true,
        locked: false,
        notesCount: 18,
        totalStudents: 30
      },
      {
        id: 102,
        title: 'Contrôle continu 1',
        type: 'Contrôle',
        status: 'partial' as const,
        visibility: true,
        locked: false,
        notesCount: 25,
        totalStudents: 30
      },
      {
        id: 103,
        title: 'Projet final',
        type: 'Projet',
        status: 'complete' as const,
        visibility: false,
        locked: true,
        notesCount: 30,
        totalStudents: 30
      }
    ]
  },
  {
    id: 2,
    display: 'Informatique',
    evaluations: [
      {
        id: 201,
        title: 'TP Programmation',
        type: 'Travaux Pratiques',
        status: 'complete' as const,
        visibility: true,
        locked: false,
        notesCount: 28,
        totalStudents: 28
      },
      {
        id: 202,
        title: 'Examen final',
        type: 'Examen',
        status: 'incomplete' as const,
        visibility: true,
        locked: false,
        notesCount: 0,
        totalStudents: 28
      }
    ]
  },
  {
    id: 3,
    display: 'Physique',
    evaluations: [
      {
        id: 301,
        title: 'Laboratoire 1',
        type: 'Laboratoire',
        status: 'partial' as const,
        visibility: true,
        locked: false,
        notesCount: 15,
        totalStudents: 25
      },
      {
        id: 302,
        title: 'Quiz hebdomadaire',
        type: 'Quiz',
        status: 'complete' as const,
        visibility: true,
        locked: false,
        notesCount: 25,
        totalStudents: 25
      },
      {
        id: 303,
        title: 'Examen de synthèse',
        type: 'Examen',
        status: 'incomplete' as const,
        visibility: false,
        locked: true,
        notesCount: 0,
        totalStudents: 25
      }
    ]
  },
  {
    id: 4,
    display: 'Chimie Organique',
    evaluations: [
      {
        id: 401,
        title: 'Test de réactivité',
        type: 'Test',
        status: 'complete' as const,
        visibility: true,
        locked: false,
        notesCount: 22,
        totalStudents: 22
      }
    ]
  }
])

// Types
interface Evaluation {
  id: number
  title: string
  type: string
  status: 'incomplete' | 'partial' | 'complete'
  visibility: boolean
  locked: boolean
  notesCount: number
  totalStudents: number
}

interface Subject {
  id: number
  name: string
  evaluations: Evaluation[]
}

// Emits
const emit = defineEmits<{
  editEvaluation: [evaluation: Evaluation]
  deleteEvaluation: [evaluation: Evaluation]
  toggleVisibility: [evaluation: Evaluation]
}>()

// State
const expandedCards = ref<Set<number>>(new Set())

// Methods
const toggleExpansion = (subjectId: number) => {
  if (expandedCards.value.has(subjectId)) {
    expandedCards.value.delete(subjectId)
  } else {
    expandedCards.value.add(subjectId)
  }
}

const getSubjectProgress = (subject: Subject): number => {
  if (!subject.evaluations.length) return 0

  const totalNotes = subject.evaluations.reduce((sum, evaluation) => sum + evaluation.notesCount, 0)
  const totalExpected = subject.evaluations.reduce((sum, evaluation) => sum + evaluation.totalStudents, 0)

  return totalExpected > 0 ? (totalNotes / totalExpected) * 100 : 0
}

const getTotalNotesEntered = (subject: Subject): number => {
  return subject.evaluations.reduce((sum, evaluation) => sum + evaluation.notesCount, 0)
}

const getTotalNotesExpected = (subject: Subject): number => {
  return subject.evaluations.reduce((sum, evaluation) => sum + evaluation.totalStudents, 0)
}

const getCompleteEvaluations = (subject: Subject): number => {
  return subject.evaluations.filter(evaluation => evaluation.status === 'complete').length
}

const getPartialEvaluations = (subject: Subject): number => {
  return subject.evaluations.filter(evaluation => evaluation.status === 'partial').length
}

const getIncompleteEvaluations = (subject: Subject): number => {
  return subject.evaluations.filter(evaluation => evaluation.status === 'incomplete').length
}

const getProgressStatus = (progress: number): string => {
  if (progress >= 80) return 'Excellent'
  if (progress >= 60) return 'Bon'
  if (progress >= 40) return 'Moyen'
  return 'À améliorer'
}

const getProgressBadgeClass = (progress: number): string => {
  if (progress === 100) return 'bg-green-100 text-green-800'
  if (progress >= 80) return 'bg-blue-100 text-blue-800'
  if (progress >= 60) return 'bg-yellow-100 text-yellow-800'
  if (progress >= 40) return 'bg-orange-100 text-orange-800'
  return 'bg-red-100 text-red-800'
}

const getProgressBarClass = (progress: number): string => {
  if (progress === 100) return 'bg-gradient-to-r from-green-500 to-green-600'
  if (progress >= 80) return 'bg-gradient-to-r from-blue-500 to-blue-600'
  if (progress >= 60) return 'bg-gradient-to-r from-yellow-500 to-yellow-600'
  if (progress >= 40) return 'bg-gradient-to-r from-orange-500 to-orange-600'
  return 'bg-gradient-to-r from-red-500 to-red-600'
}

const getStatusText = (status: string): string => {
  const statusMap = {
    'incomplete': 'À saisir',
    'partial': 'Partiel',
    'complete': 'Complet'
  }
  return statusMap[status as keyof typeof statusMap] || status
}

const getStatusBadgeClass = (status: string): string => {
  const classMap = {
    'incomplete': 'bg-red-100 text-red-800',
    'partial': 'bg-yellow-100 text-yellow-800',
    'complete': 'bg-green-100 text-green-800'
  }
  return classMap[status as keyof typeof classMap] || 'bg-gray-100 text-gray-800'
}

const getEvaluationProgressClass = (status: string): string => {
  const classMap = {
    'incomplete': 'bg-gradient-to-r from-red-500 to-red-600',
    'partial': 'bg-gradient-to-r from-yellow-500 to-yellow-600',
    'complete': 'bg-gradient-to-r from-green-500 to-green-600'
  }
  return classMap[status as keyof typeof classMap] || 'bg-gradient-to-r from-gray-500 to-gray-600'
}

const getEvaluationIcon = (type: string): string => {
  const iconMap: { [key: string]: string } = {
    'Examen': '📝',
    'Contrôle': '📋',
    'Projet': '💼',
    'Travaux Pratiques': '🔬',
    'Laboratoire': '🧪',
    'Quiz': '❓',
    'Test': '📊'
  }
  return iconMap[type] || '📄'
}

// Event handlers
const editEvaluation = (evaluation: Evaluation) => {
  emit('editEvaluation', evaluation)
}

const deleteEvaluation = (evaluation: Evaluation) => {
  emit('deleteEvaluation', evaluation)
}

const toggleVisibility = (evaluation: Evaluation) => {
  emit('toggleVisibility', evaluation)
}
</script>

<template>
    <Card
        v-for="matiere in matieres"
        :key="matiere.id"
        class="mb-3"
    >
      <!-- Card Header - Clickable -->
      <template #title>
        <div
            @click="toggleExpansion(matiere.id)"
            @keydown.enter="toggleExpansion(matiere.id)"
            @keydown.space.prevent="toggleExpansion(matiere.id)"
            tabindex="0"
            role="button"
            :aria-expanded="expandedCards.has(matiere.id)"
            :aria-label="`${matiere.display} - Cliquez pour ${expandedCards.has(matiere.id) ? 'réduire' : 'développer'} les détails`"
        >
          <!-- Subject Header -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
              <div
                  class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-xl font-bold">
                📖
              </div>
              <h3 class="text-2xl font-bold text-slate-800">{{ matiere.display }}</h3>
            </div>
            <div class="flex items-center space-x-3">
              <span class="text-sm text-slate-500 font-medium">
                {{ matiere.evaluations.length }} évaluation{{ matiere.evaluations.length > 1 ? 's' : '' }}
              </span>
              <div
                  class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center transition-transform duration-200"
                  :class="{ 'rotate-180': expandedCards.has(matiere.id) }">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </template>
      <template #content>

        <!-- Progress Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Progress -->
          <div class="lg:col-span-2 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                  <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"></path>
                  </svg>
                </div>
                <span class="font-semibold text-slate-700">Progression globale</span>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-2xl font-bold text-slate-800">{{ Math.round(getSubjectProgress(matiere)) }}%</span>
                <div class="px-2 py-1 rounded-full text-xs font-medium"
                     :class="getProgressBadgeClass(getSubjectProgress(matiere))">
                  {{ getProgressStatus(getSubjectProgress(matiere)) }}
                </div>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500 ease-out"
                   :class="getProgressBarClass(getSubjectProgress(matiere))"
                   :style="{ width: getSubjectProgress(matiere) + '%' }">
              </div>
            </div>

            <div class="mt-2 text-sm text-slate-600">
              {{ getTotalNotesEntered(matiere) }} / {{ getTotalNotesExpected(matiere) }} notes saisies
            </div>
          </div>

          <!-- Status Summary -->
          <div class="bg-gradient-to-br from-slate-50 to-gray-50 rounded-xl p-4 border border-slate-200">
            <h4 class="font-semibold text-slate-700 mb-3 flex items-center">
              <svg class="w-4 h-4 mr-2 text-slate-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                      clip-rule="evenodd"></path>
              </svg>
              Statut
            </h4>
            <div class="space-y-2">
              <div v-if="getCompleteEvaluations(matiere) > 0" class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span class="text-sm text-slate-600">Complètes</span>
                </div>
                <span class="text-sm font-semibold text-green-600">{{ getCompleteEvaluations(matiere) }}</span>
              </div>
              <div v-if="getPartialEvaluations(matiere) > 0" class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                  <span class="text-sm text-slate-600">Partielles</span>
                </div>
                <span class="text-sm font-semibold text-yellow-600">{{ getPartialEvaluations(matiere) }}</span>
              </div>
              <div v-if="getIncompleteEvaluations(matiere) > 0" class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                  <span class="text-sm text-slate-600">À saisir</span>
                </div>
                <span class="text-sm font-semibold text-red-600">{{ getIncompleteEvaluations(matiere) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Expanded Content -->
        <div
            v-show="expandedCards.has(matiere.id)"
            class="border-t border-slate-200 bg-slate-50"
            role="region"
            :aria-label="`Détails des évaluations pour ${matiere.display}`"
        >
          <div class="p-6">
            <h4 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                      clip-rule="evenodd"></path>
              </svg>
              Détail des évaluations
            </h4>

            <div class="space-y-4">
              <div
                  v-for="evaluation in matiere.evaluations"
                  :key="evaluation.id"
                  class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition-shadow duration-200"
              >
                <!-- Evaluation Header -->
                <div class="flex items-start justify-between mb-4">
                  <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                      <span class="text-2xl">{{ getEvaluationIcon(evaluation.type) }}</span>
                      <h5 class="text-lg font-semibold text-slate-800">{{ evaluation.title }}</h5>
                      <div v-if="evaluation.locked"
                           class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Verrouillé
                      </div>
                    </div>
                    <div class="flex items-center space-x-3">
                      <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                        {{ evaluation.type }}
                      </span>
                    </div>
                  </div>

                  <!-- Status Badge -->
                  <div class="px-3 py-1 rounded-full text-sm font-medium flex items-center space-x-1"
                       :class="getStatusBadgeClass(evaluation.status)">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path v-if="evaluation.status === 'complete'" fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                      <path v-else-if="evaluation.status === 'partial'" fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd"></path>
                      <path v-else fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ getStatusText(evaluation.status) }}</span>
                  </div>
                </div>

                <!-- Progress Bar for this evaluation -->
                <div class="mb-4">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-600 flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                      </svg>
                      Notes saisies
                    </span>
                    <span class="text-sm font-semibold text-slate-800">
                      {{ evaluation.notesCount }}/{{ evaluation.totalStudents }}
                      <span class="text-slate-500 ml-1">({{
                          Math.round((evaluation.notesCount / evaluation.totalStudents) * 100)
                        }}%)</span>
                    </span>
                  </div>
                  <div class="w-full bg-slate-200 rounded-full h-2">
                    <div class="h-full rounded-full transition-all duration-300"
                         :class="getEvaluationProgressClass(evaluation.status)"
                         :style="{ width: (evaluation.notesCount / evaluation.totalStudents) * 100 + '%' }">
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                  <div class="flex space-x-2">
                    <!-- Edit Button -->
                    <button
                        v-if="!evaluation.locked"
                        @click="editEvaluation(evaluation)"
                        class="px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-200 flex items-center space-x-1 text-sm font-medium"
                        :aria-label="`Modifier l'évaluation ${evaluation.title}`"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                      <span>Modifier</span>
                    </button>

                    <!-- Locked Indicator -->
                    <div
                        v-else
                        class="px-3 py-2 bg-gray-50 text-gray-400 rounded-lg flex items-center space-x-1 text-sm font-medium"
                    >
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                              clip-rule="evenodd"></path>
                      </svg>
                      <span>Verrouillé</span>
                    </div>

                    <!-- Delete Button -->
                    <button
                        @click="deleteEvaluation(evaluation)"
                        class="px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-200 flex items-center space-x-1 text-sm font-medium"
                        :aria-label="`Supprimer l'évaluation ${evaluation.title}`"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                      <span>Supprimer</span>
                    </button>
                  </div>

                  <!-- Visibility Toggle -->
                  <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2 text-sm text-slate-600">
                      <svg v-if="evaluation.visibility" class="w-4 h-4 text-green-600" fill="currentColor"
                           viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                        <path fill-rule="evenodd"
                              d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                              clip-rule="evenodd"></path>
                      </svg>
                      <svg v-else class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z"
                              clip-rule="evenodd"></path>
                        <path
                            d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"></path>
                      </svg>
                      <span class="font-medium">{{ evaluation.visibility ? 'Visible' : 'Masqué' }}</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input
                          type="checkbox"
                          v-model="evaluation.visibility"
                          @change="toggleVisibility(evaluation)"
                          class="sr-only peer"
                          :aria-label="`Basculer la visibilité de l'évaluation ${evaluation.title}`"
                      >
                      <div
                          class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </template>

  <style scoped>

  </style>
