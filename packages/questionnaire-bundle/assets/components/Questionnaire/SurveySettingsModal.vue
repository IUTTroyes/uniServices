<template>
  <Dialog
    :style="{ width: '92vw', maxWidth: '780px' }"
    :visible="true"
    :modal="true"
    :closable="true"
    :draggable="false"
    @update:visible="$emit('close')"
  >
    <template #header>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 border border-primary-200 dark:border-primary-800/60 flex items-center justify-center shrink-0">
          <AdjustmentsHorizontalIcon class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
            Paramètres du questionnaire
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Configurez la diffusion, l'anonymat, l'expérience de réponse et les messages
          </p>
        </div>
      </div>
    </template>

    <form @submit.prevent="saveSettings" class="space-y-5 py-2">
      <!-- 1. Publication & Dates -->
      <div class="bg-slate-50/80 dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-700/80 p-4 md:p-5 space-y-4">
        <div class="flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-900/60 flex items-center justify-center shrink-0">
            <CalendarDaysIcon class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Période de diffusion & Durée</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Planifiez la disponibilité et estimez le temps requis</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="opening-date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Date et heure d'ouverture
            </label>
            <input
              id="opening-date"
              v-model="formState.openingDate"
              type="datetime-local"
              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors shadow-2xs"
            />
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Laissez vide pour ouverture immédiate</p>
          </div>

          <div>
            <label for="closing-date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Date et heure de fermeture
            </label>
            <input
              id="closing-date"
              v-model="formState.closingDate"
              type="datetime-local"
              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors shadow-2xs"
            />
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Date limite de réponse</p>
          </div>

          <div>
            <label for="estimated-time" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Temps estimé (minutes)
            </label>
            <div class="relative">
              <input
                id="estimated-time"
                v-model.number="formState.estimatedTime"
                type="number"
                min="1"
                max="300"
                placeholder="ex: 10"
                class="w-full px-3 py-2 pr-12 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors shadow-2xs"
              />
              <span class="absolute right-3 top-2.5 text-xs text-slate-400 dark:text-slate-500 pointer-events-none">min</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Indiqué aux participants</p>
          </div>
        </div>
      </div>

      <!-- 2. Privacy & Confidentiality -->
      <div class="bg-slate-50/80 dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-700/80 p-4 md:p-5 space-y-3">
        <div class="flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/60 flex items-center justify-center shrink-0">
            <ShieldCheckIcon class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Confidentialité des réponses</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Garantie d'anonymat et traçabilité</p>
          </div>
        </div>

        <div
          @click="localSettings.anonymous = !localSettings.anonymous"
          :class="[
            'p-3.5 rounded-xl border transition-all cursor-pointer flex items-center justify-between gap-4 shadow-2xs select-none',
            localSettings.anonymous
              ? 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800/80'
              : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700/80 hover:border-slate-300 dark:hover:border-slate-600'
          ]"
        >
          <div class="flex items-start gap-3">
            <div :class="['w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5', localSettings.anonymous ? 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-500']">
              <LockClosedIcon class="w-4 h-4" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-slate-900 dark:text-white">Questionnaire 100% anonyme</span>
                <span v-if="localSettings.anonymous" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/80 text-emerald-800 dark:text-emerald-200">
                  Activé
                </span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed">
                Les réponses collectées ne seront jamais associées à l'identité ou à l'adresse e-mail des participants.
              </p>
            </div>
          </div>
          <ToggleSwitch v-model="localSettings.anonymous" @click.stop class="shrink-0" />
        </div>
      </div>

      <!-- 3. User Experience & Navigation -->
      <div class="bg-slate-50/80 dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-700/80 p-4 md:p-5 space-y-3">
        <div class="flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-900/60 flex items-center justify-center shrink-0">
            <SparklesIcon class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Expérience utilisateur & Navigation</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Comportement du formulaire lors de la complétion</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <!-- Auto-save -->
          <div
            @click="localSettings.autoSave = !localSettings.autoSave"
            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600 transition-all cursor-pointer flex items-center justify-between gap-3 shadow-2xs select-none"
          >
            <div class="flex items-start gap-2.5 min-w-0">
              <CloudArrowUpIcon class="w-5 h-5 text-purple-600 dark:text-purple-400 shrink-0 mt-0.5" />
              <div class="min-w-0">
                <span class="text-xs font-bold text-slate-900 dark:text-white block leading-tight">Sauvegarde automatique</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Enregistre les réponses en cours au fur et à mesure</span>
              </div>
            </div>
            <ToggleSwitch v-model="localSettings.autoSave" @click.stop class="shrink-0" />
          </div>

          <!-- Allow back -->
          <div
            @click="localSettings.allowBack = !localSettings.allowBack"
            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600 transition-all cursor-pointer flex items-center justify-between gap-3 shadow-2xs select-none"
          >
            <div class="flex items-start gap-2.5 min-w-0">
              <ArrowUturnLeftIcon class="w-5 h-5 text-purple-600 dark:text-purple-400 shrink-0 mt-0.5" />
              <div class="min-w-0">
                <span class="text-xs font-bold text-slate-900 dark:text-white block leading-tight">Retour en arrière</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Permet de revenir modifier les sections précédentes</span>
              </div>
            </div>
            <ToggleSwitch v-model="localSettings.allowBack" @click.stop class="shrink-0" />
          </div>

          <!-- Show progress -->
          <div
            @click="localSettings.showProgress = !localSettings.showProgress"
            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600 transition-all cursor-pointer flex items-center justify-between gap-3 shadow-2xs select-none"
          >
            <div class="flex items-start gap-2.5 min-w-0">
              <ChartBarIcon class="w-5 h-5 text-purple-600 dark:text-purple-400 shrink-0 mt-0.5" />
              <div class="min-w-0">
                <span class="text-xs font-bold text-slate-900 dark:text-white block leading-tight">Barre de progression</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Affiche le pourcentage et les étapes franchies</span>
              </div>
            </div>
            <ToggleSwitch v-model="localSettings.showProgress" @click.stop class="shrink-0" />
          </div>

          <!-- Require completion -->
          <div
            @click="localSettings.requireCompletion = !localSettings.requireCompletion"
            class="p-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600 transition-all cursor-pointer flex items-center justify-between gap-3 shadow-2xs select-none"
          >
            <div class="flex items-start gap-2.5 min-w-0">
              <CheckBadgeIcon class="w-5 h-5 text-purple-600 dark:text-purple-400 shrink-0 mt-0.5" />
              <div class="min-w-0">
                <span class="text-xs font-bold text-slate-900 dark:text-white block leading-tight">Validation complète</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Bloque la soumission si des champs requis manquent</span>
              </div>
            </div>
            <ToggleSwitch v-model="localSettings.requireCompletion" @click.stop class="shrink-0" />
          </div>
        </div>
      </div>

      <!-- 4. Messages (Introduction & Thank you) -->
      <div class="bg-slate-50/80 dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-700/80 p-4 md:p-5 space-y-4">
        <div class="flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60 flex items-center justify-center shrink-0">
            <ChatBubbleBottomCenterTextIcon class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Messages d'accompagnement</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Personnalisez les messages d'accueil et de remerciement</p>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <label for="start-text" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Message d'introduction & présentation
            </label>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
              Affiché au participant sur la première page avant de démarrer l'enquête.
            </p>
            <textarea
              id="start-text"
              v-model="formState.startText"
              rows="3"
              class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors shadow-2xs resize-y"
              placeholder="Ex: Bienvenue dans ce questionnaire d'évaluation. Vos retours nous aident à améliorer constamment nos formations..."
            />
          </div>

          <div>
            <label for="end-text" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Message de conclusion & remerciement
            </label>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
              Affiché au participant immédiatement après la soumission finale de ses réponses.
            </p>
            <textarea
              id="end-text"
              v-model="formState.endText"
              rows="3"
              class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors shadow-2xs resize-y"
              placeholder="Ex: Merci d'avoir pris le temps de répondre ! Vos retours ont été enregistrés avec succès."
            />
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-700">
        <Button
          severity="secondary"
          type="button"
          outlined
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium rounded-xl"
        >
          Annuler
        </Button>
        <Button
          type="submit"
          icon="pi pi-check"
          label="Enregistrer les paramètres"
          class="px-4 py-2 text-sm font-semibold rounded-xl shadow-xs"
        />
      </div>
    </form>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import type { Survey, SurveySettings } from '@types';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import {
  AdjustmentsHorizontalIcon,
  CalendarDaysIcon,
  ShieldCheckIcon,
  LockClosedIcon,
  SparklesIcon,
  CloudArrowUpIcon,
  ArrowUturnLeftIcon,
  ChartBarIcon,
  CheckBadgeIcon,
  ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline';

interface Props {
  survey: Survey | null;
}

interface SettingsPayload {
  opt: SurveySettings;
  openingDate?: string | null;
  closingDate?: string | null;
  estimatedTime?: number | null;
  startText?: string;
  endText?: string;
}

interface Emits {
  close: [];
  update: [payload: SettingsPayload | SurveySettings];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const localSettings = ref<SurveySettings>({
  anonymous: false,
  autoSave: true,
  allowBack: true,
  showProgress: true,
  requireCompletion: false
});

const formState = reactive({
  openingDate: '',
  closingDate: '',
  estimatedTime: null as number | null,
  startText: '',
  endText: ''
});

function toInputDateTime(val: any): string {
  if (!val) return '';
  const d = val instanceof Date ? val : new Date(val);
  if (isNaN(d.getTime())) return '';
  const pad = (n: number) => String(n).padStart(2, '0');
  const year = d.getFullYear();
  const month = pad(d.getMonth() + 1);
  const day = pad(d.getDate());
  const hours = pad(d.getHours());
  const minutes = pad(d.getMinutes());
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

watch(
  () => props.survey,
  (s) => {
    if (s) {
      localSettings.value = {
        anonymous: s.opt?.anonymous ?? false,
        autoSave: s.opt?.autoSave ?? true,
        allowBack: s.opt?.allowBack ?? true,
        showProgress: s.opt?.showProgress ?? true,
        requireCompletion: s.opt?.requireCompletion ?? false
      };

      formState.openingDate = toInputDateTime(s.openingDate);
      formState.closingDate = toInputDateTime(s.closingDate);
      formState.estimatedTime = s.estimatedTime ?? null;
      formState.startText = s.startText || '';
      formState.endText = s.endText || '';
    } else {
      localSettings.value = {
        anonymous: false,
        autoSave: true,
        allowBack: true,
        showProgress: true,
        requireCompletion: false
      };
      formState.openingDate = '';
      formState.closingDate = '';
      formState.estimatedTime = null;
      formState.startText = '';
      formState.endText = '';
    }
  },
  { immediate: true }
);

function saveSettings() {
  const payload: SettingsPayload = {
    opt: { ...localSettings.value },
    openingDate: formState.openingDate ? new Date(formState.openingDate).toISOString() : null,
    closingDate: formState.closingDate ? new Date(formState.closingDate).toISOString() : null,
    estimatedTime: formState.estimatedTime ? Number(formState.estimatedTime) : null,
    startText: formState.startText.trim(),
    endText: formState.endText.trim()
  };

  emit('update', payload);
}
</script>

