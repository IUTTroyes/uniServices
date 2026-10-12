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
      <DialogHeader
        :icon="AdjustmentsHorizontalIcon"
        title="Paramètres du questionnaire"
        subtitle="Configurez la diffusion, l'anonymat, l'expérience de réponse et les messages"
      />
    </template>

    <form @submit.prevent="saveSettings" class="q-form">
      <!-- 1. Publication & Dates -->
      <FormSection
        :icon="CalendarDaysIcon"
        tone="blue"
        title="Période de diffusion & Durée"
        subtitle="Planifiez la disponibilité et estimez le temps requis"
      >
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <FormField label="Date et heure d'ouverture" for="opening-date" hint="Laissez vide pour ouverture immédiate">
            <input id="opening-date" v-model="formState.openingDate" type="datetime-local" class="q-input" />
          </FormField>

          <FormField label="Date et heure de fermeture" for="closing-date" hint="Date limite de réponse">
            <input id="closing-date" v-model="formState.closingDate" type="datetime-local" class="q-input" />
          </FormField>

          <FormField label="Temps estimé (minutes)" for="estimated-time" hint="Indiqué aux participants">
            <div class="relative">
              <input
                id="estimated-time"
                v-model.number="formState.estimatedTime"
                type="number"
                min="1"
                max="300"
                placeholder="ex: 10"
                class="q-input pr-12"
              />
              <span class="absolute right-3 top-2.5 text-xs text-slate-400 dark:text-slate-500 pointer-events-none">min</span>
            </div>
          </FormField>
        </div>
      </FormSection>

      <!-- 2. Privacy & Confidentiality -->
      <FormSection
        :icon="ShieldCheckIcon"
        tone="emerald"
        title="Confidentialité des réponses"
        subtitle="Garantie d'anonymat et traçabilité"
      >
        <ToggleCard
          v-model="localSettings.anonymous"
          :icon="LockClosedIcon"
          tone="emerald"
          description="Les réponses collectées ne seront jamais associées à l'identité ou à l'adresse e-mail des participants."
        >
          <template #title>
            <span class="inline-flex items-center gap-2">
              Questionnaire 100% anonyme
              <span v-if="localSettings.anonymous" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/80 text-emerald-800 dark:text-emerald-200">
                Activé
              </span>
            </span>
          </template>
        </ToggleCard>
      </FormSection>

      <!-- 3. User Experience & Navigation -->
      <FormSection
        :icon="SparklesIcon"
        tone="purple"
        title="Expérience utilisateur & Navigation"
        subtitle="Comportement du formulaire lors de la complétion"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <ToggleCard
            v-model="localSettings.autoSave"
            :icon="CloudArrowUpIcon"
            tone="purple"
            title="Sauvegarde automatique"
            description="Enregistre les réponses en cours au fur et à mesure"
          />
          <ToggleCard
            v-model="localSettings.allowBack"
            :icon="ArrowUturnLeftIcon"
            tone="purple"
            title="Retour en arrière"
            description="Permet de revenir modifier les sections précédentes"
          />
          <ToggleCard
            v-model="localSettings.showProgress"
            :icon="ChartBarIcon"
            tone="purple"
            title="Barre de progression"
            description="Affiche le pourcentage et les étapes franchies"
          />
          <ToggleCard
            v-model="localSettings.requireCompletion"
            :icon="CheckBadgeIcon"
            tone="purple"
            title="Validation complète"
            description="Bloque la soumission si des champs requis manquent"
          />
        </div>
      </FormSection>

      <!-- 4. Messages (Introduction & Thank you) -->
      <FormSection
        :icon="ChatBubbleBottomCenterTextIcon"
        tone="amber"
        title="Messages d'accompagnement"
        subtitle="Personnalisez les messages d'accueil et de remerciement"
      >
        <FormField
          label="Message d'introduction & présentation"
          for="start-text"
          hint="Affiché au participant sur la première page avant de démarrer l'enquête."
          hint-position="top"
        >
          <textarea
            id="start-text"
            v-model="formState.startText"
            rows="3"
            class="q-input"
            placeholder="Ex: Bienvenue dans ce questionnaire d'évaluation. Vos retours nous aident à améliorer constamment nos formations..."
          />
        </FormField>

        <FormField
          label="Message de conclusion & remerciement"
          for="end-text"
          hint="Affiché au participant immédiatement après la soumission finale de ses réponses."
          hint-position="top"
        >
          <textarea
            id="end-text"
            v-model="formState.endText"
            rows="3"
            class="q-input"
            placeholder="Ex: Merci d'avoir pris le temps de répondre ! Vos retours ont été enregistrés avec succès."
          />
        </FormField>
      </FormSection>

      <!-- Action Buttons -->
      <div class="q-actions">
        <Button severity="secondary" type="button" outlined label="Annuler" @click="$emit('close')" />
        <Button type="submit" icon="pi pi-check" label="Enregistrer les paramètres" />
      </div>
    </form>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import type { Survey, SurveySettings } from '@types';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { DialogHeader, FormSection, FormField, ToggleCard } from '../Form';
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
