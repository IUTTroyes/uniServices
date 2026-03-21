<template>
  <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <button
          @click="uiStore.toggleSidebar"
          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 lg:hidden"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>
        
        <nav class="hidden md:flex items-center space-x-4">
          <template v-if="currentSurvey">
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ currentSurvey.title }}
            </span>
            <div class="flex items-center space-x-2">
              <span
                :class="[
                  'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                  currentSurvey.status === 'published' 
                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                ]"
              >
                {{ currentSurvey.status === 'published' ? 'Publié' : 'Brouillon' }}
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                v{{ currentSurvey.version }}
              </span>
            </div>
          </template>
        </nav>
      </div>

      <div class="flex items-center space-x-3">
        <!-- Quick Actions -->
        <div class="flex items-center space-x-2">
          <router-link
            v-if="currentSurvey && currentSurvey.status === 'published'"
            :to="`/responses/${currentSurvey.id}`"
            class="btn-secondary text-sm"
          >
            <ChatBubbleLeftRightIcon class="w-4 h-4" />
            <span class="hidden sm:inline">Réponses</span>
          </router-link>
          
          <button
            v-if="currentSurvey && $route.name === 'builder'"
            @click="previewSurvey"
            class="btn-secondary text-sm"
          >
            <EyeIcon class="w-4 h-4" />
            <span class="hidden sm:inline">Aperçu</span>
          </button>
          
          <button
            v-if="currentSurvey && currentSurvey.status === 'draft'"
            @click="publishSurvey"
            class="btn-primary text-sm"
          >
            <RocketLaunchIcon class="w-4 h-4" />
            <span class="hidden sm:inline">Publier</span>
          </button>
        </div>

        <!-- Notifications -->
        <div class="relative">
          <button
            @click="showNotifications = !showNotifications"
            class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <BellIcon class="w-5 h-5" />
            <span
              v-if="uiStore.notifications.length > 0"
              class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full"
            >
              {{ uiStore.notifications.length }}
            </span>
          </button>

          <!-- Notifications dropdown -->
          <div
            v-if="showNotifications"
            class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
          >
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-sm font-semibold">Notifications</h3>
            </div>
            <div class="max-h-64 overflow-y-auto">
              <div
                v-for="notification in uiStore.notifications"
                :key="notification.id"
                class="p-4 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
              >
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <p class="text-sm font-medium">{{ notification.title }}</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                      {{ notification.message }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                      {{ formatRelativeTime(notification.timestamp) }}
                    </p>
                  </div>
                  <button
                    @click="uiStore.removeNotification(notification.id)"
                    class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    <XMarkIcon class="w-3 h-3" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- User menu -->
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center">
            <span class="text-sm font-medium text-white">U</span>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  Bars3Icon,
  BellIcon,
  EyeIcon,
  ChatBubbleLeftRightIcon,
  RocketLaunchIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';
import { useSurveyStore } from '@/stores/survey';
import { useUIStore } from '@/stores/ui';
import { formatRelative } from 'date-fns';
import { fr } from 'date-fns/locale';

const surveyStore = useSurveyStore();
const uiStore = useUIStore();

const showNotifications = ref(false);

const currentSurvey = computed(() => surveyStore.currentSurvey);

function previewSurvey() {
  if (currentSurvey.value) {
    // Open preview in new tab/window
    window.open(`/take/preview-${currentSurvey.value.id}`, '_blank');
  }
}

function publishSurvey() {
  if (currentSurvey.value) {
    surveyStore.setSurveyStatus(currentSurvey.value.id, 'published');
    uiStore.addNotification(
      'success',
      'Questionnaire publié',
      'Votre questionnaire est maintenant accessible aux participants.'
    );
  }
}

function formatRelativeTime(date: Date): string {
  return formatRelative(date, new Date(), { locale: fr });
}
</script>