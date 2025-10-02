<template>
  <div
    :class="[
      'fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform transition-transform duration-300 ease-in-out',
      uiStore.sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      'lg:translate-x-0 lg:static lg:inset-0'
    ]"
  >
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-sm">Q</span>
        </div>
        <span class="text-lg font-semibold text-gray-900 dark:text-white">
          QuestionnaireApp
        </span>
      </div>
      <button
        @click="uiStore.toggleSidebar"
        class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 lg:hidden"
      >
        <XMarkIcon class="w-5 h-5" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2">
      <!-- Dashboard -->
      <router-link
        to="/"
        :class="[
          'nav-item',
          $route.name === 'dashboard' ? 'nav-item-active' : ''
        ]"
      >
        <HomeIcon class="w-5 h-5" />
        <span>Tableau de bord</span>
      </router-link>

      <!-- Survey Builder -->
      <router-link
        to="/builder"
        :class="[
          'nav-item',
          $route.name === 'builder' ? 'nav-item-active' : ''
        ]"
      >
        <PencilSquareIcon class="w-5 h-5" />
        <span>Créateur</span>
      </router-link>

      <!-- Divider -->
      <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

      <!-- My Surveys -->
      <div class="space-y-1">
        <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
          Mes questionnaires
        </h3>
        
        <div v-if="recentSurveys.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
          Aucun questionnaire
        </div>
        
        <div v-else class="space-y-1">
          <div
            v-for="survey in recentSurveys"
            :key="survey.id"
            class="group"
          >
            <div class="flex items-center justify-between px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
              <div class="flex-1 min-w-0">
                <router-link
                  :to="`/builder/${survey.id}`"
                  class="block truncate text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                >
                  {{ survey.title }}
                </router-link>
                <div class="flex items-center space-x-2 mt-1">
                  <span
                    :class="[
                      'inline-block w-2 h-2 rounded-full',
                      survey.status === 'published' ? 'bg-green-400' : 'bg-yellow-400'
                    ]"
                  />
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ survey.status === 'published' ? 'Publié' : 'Brouillon' }}
                  </span>
                </div>
              </div>
              
              <!-- Survey Actions -->
              <Menu as="div" class="relative opacity-0 group-hover:opacity-100 transition-opacity">
                <MenuButton class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                  <EllipsisVerticalIcon class="w-4 h-4" />
                </MenuButton>
                <MenuItems class="survey-menu">
                  <MenuItem>
                    <router-link
                      :to="`/builder/${survey.id}`"
                      class="menu-item w-full"
                    >
                      <PencilIcon class="w-4 h-4" />
                      Modifier
                    </router-link>
                  </MenuItem>
                  <MenuItem v-if="survey.status === 'published'">
                    <router-link
                      :to="`/responses/${survey.id}`"
                      class="menu-item w-full"
                    >
                      <ChatBubbleLeftRightIcon class="w-4 h-4" />
                      Réponses
                    </router-link>
                  </MenuItem>
                  <MenuItem v-if="survey.status === 'published'">
                    <router-link
                      :to="`/analytics/${survey.id}`"
                      class="menu-item w-full"
                    >
                      <ChartBarIcon class="w-4 h-4" />
                      Statistiques
                    </router-link>
                  </MenuItem>
                  <MenuItem>
                    <button
                      @click="duplicateSurvey(survey)"
                      class="menu-item w-full"
                    >
                      <DocumentDuplicateIcon class="w-4 h-4" />
                      Dupliquer
                    </button>
                  </MenuItem>
                  <MenuItem>
                    <button
                      @click="deleteSurvey(survey)"
                      class="menu-item w-full text-red-600 dark:text-red-400"
                    >
                      <TrashIcon class="w-4 h-4" />
                      Supprimer
                    </button>
                  </MenuItem>
                </MenuItems>
              </Menu>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">U</span>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">Utilisateur</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">user@example.com</p>
          </div>
        </div>
        
        <button
          @click="uiStore.toggleDarkMode"
          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
          :title="uiStore.isDarkMode ? 'Mode clair' : 'Mode sombre'"
        >
          <SunIcon v-if="uiStore.isDarkMode" class="w-4 h-4" />
          <MoonIcon v-else class="w-4 h-4" />
        </button>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  XMarkIcon,
  HomeIcon,
  PencilSquareIcon,
  EllipsisVerticalIcon,
  PencilIcon,
  ChatBubbleLeftRightIcon,
  ChartBarIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  SunIcon,
  MoonIcon
} from '@heroicons/vue/24/outline';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { useSurveyStore } from '@/stores/survey';
import { useUIStore } from '@/stores/ui';
import type { Survey } from '@/types/survey';

const surveyStore = useSurveyStore();
const uiStore = useUIStore();

const recentSurveys = computed(() => 
  surveyStore.surveys
    .sort((a, b) => b.updatedAt.getTime() - a.updatedAt.getTime())
    .slice(0, 8)
);

function duplicateSurvey(survey: Survey) {
  const duplicate = surveyStore.duplicateSurvey(survey.id);
  if (duplicate) {
    uiStore.addNotification(
      'success',
      'Questionnaire dupliqué',
      `"${duplicate.title}" a été créé avec succès.`
    );
  }
}

function deleteSurvey(survey: Survey) {
  if (confirm(`Êtes-vous sûr de vouloir supprimer "${survey.title}" ?`)) {
    surveyStore.deleteSurvey(survey.id);
    uiStore.addNotification(
      'success',
      'Questionnaire supprimé',
      `"${survey.title}" a été supprimé avec succès.`
    );
  }
}
</script>

<style scoped>
.survey-menu {
  @apply absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50;
}

.menu-item {
  @apply flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}
</style>