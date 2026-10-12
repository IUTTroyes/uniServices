<script setup lang="ts">
import { computed } from 'vue';

interface AC {
  id?: number;
  code?: string;
  libelle?: string;
}

interface Niveau {
  id?: number;
  libelle?: string;
  ordre?: number;
  ordreAnnee?: number;
  apprentissageCritique?: AC[];
}

interface Competence {
  id?: number;
  nomCourt?: string;
  libelle?: string;
  couleur?: string;
  composantesEssentielles?: Array<string | { libelle?: string }>;
  situationsProfessionnelles?: Array<string | { libelle?: string }>;
  niveaux?: Niveau[];
}

interface Props {
  competence: Competence;
}

const props = defineProps<Props>();

const getText = (item: any): string => {
  if (!item) return '';
  if (typeof item === 'string') return item;
  return item.libelle || item.nom || item.description || String(item);
};

// Map color keys (c1..c6) to rich Tailwind styles
const colorStyles = computed(() => {
  const c = props.competence.couleur || 'c1';
  switch (c) {
    case 'c1':
      return {
        badge: 'bg-blue-600 text-white',
        border: 'border-blue-200 dark:border-blue-800',
        cardBg: 'bg-blue-50/40 dark:bg-blue-950/20',
        text: 'text-blue-700 dark:text-blue-300',
        iconBg: 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300',
        acTag: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-700',
        bullet: 'bg-blue-500',
        dot: 'bg-blue-500'
      };
    case 'c2':
      return {
        badge: 'bg-emerald-600 text-white',
        border: 'border-emerald-200 dark:border-emerald-800',
        cardBg: 'bg-emerald-50/40 dark:bg-emerald-950/20',
        text: 'text-emerald-700 dark:text-emerald-300',
        iconBg: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300',
        acTag: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700',
        bullet: 'bg-emerald-500',
        dot: 'bg-emerald-500'
      };
    case 'c3':
      return {
        badge: 'bg-amber-600 text-white',
        border: 'border-amber-200 dark:border-amber-800',
        cardBg: 'bg-amber-50/40 dark:bg-amber-950/20',
        text: 'text-amber-700 dark:text-amber-300',
        iconBg: 'bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300',
        acTag: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700',
        bullet: 'bg-amber-500',
        dot: 'bg-amber-500'
      };
    case 'c4':
      return {
        badge: 'bg-rose-600 text-white',
        border: 'border-rose-200 dark:border-rose-800',
        cardBg: 'bg-rose-50/40 dark:bg-rose-950/20',
        text: 'text-rose-700 dark:text-rose-300',
        iconBg: 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300',
        acTag: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-700',
        bullet: 'bg-rose-500',
        dot: 'bg-rose-500'
      };
    case 'c5':
      return {
        badge: 'bg-purple-600 text-white',
        border: 'border-purple-200 dark:border-purple-800',
        cardBg: 'bg-purple-50/40 dark:bg-purple-950/20',
        text: 'text-purple-700 dark:text-purple-300',
        iconBg: 'bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300',
        acTag: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-700',
        bullet: 'bg-purple-500',
        dot: 'bg-purple-500'
      };
    case 'c6':
    default:
      return {
        badge: 'bg-cyan-600 text-white',
        border: 'border-cyan-200 dark:border-cyan-800',
        cardBg: 'bg-cyan-50/40 dark:bg-cyan-950/20',
        text: 'text-cyan-700 dark:text-cyan-300',
        iconBg: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/60 dark:text-cyan-300',
        acTag: 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-900/40 dark:text-cyan-300 dark:border-cyan-700',
        bullet: 'bg-cyan-500',
        dot: 'bg-cyan-500'
      };
  }
});

const sortedNiveaux = computed(() => {
  if (!props.competence?.niveaux) return [];
  return [...props.competence.niveaux].sort((a, b) => (a.ordre || 0) - (b.ordre || 0));
});
</script>

<template>
  <div class="space-y-6 mt-4">
    <!-- En-tête de la Compétence -->
    <div
      class="p-5 rounded-2xl border transition-all duration-200 bg-surface-0 dark:bg-surface-900 shadow-sm"
      :class="colorStyles.border"
    >
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
        <div class="flex items-center gap-3">
          <span
            class="px-3.5 py-1.5 rounded-xl font-bold text-sm tracking-wide shadow-sm"
            :class="colorStyles.badge"
          >
            {{ competence.nomCourt || 'Compétence' }}
          </span>
          <span class="text-xs uppercase tracking-wider text-muted-color font-semibold">
            Référentiel National B.U.T.
          </span>
        </div>
      </div>
      <h2 class="text-xl sm:text-2xl font-bold mt-3 text-surface-900 dark:text-surface-0 leading-snug">
        {{ competence.libelle }}
      </h2>
    </div>

    <!-- 2 Colonnes : Composantes Essentielles & Situations Professionnelles -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Composantes Essentielles -->
      <div
        class="rounded-2xl border p-5 bg-surface-0 dark:bg-surface-900 shadow-sm flex flex-col"
        :class="colorStyles.border"
      >
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-surface-200 dark:border-surface-800">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm" :class="colorStyles.iconBg">
              <i class="pi pi-check-circle"></i>
            </div>
            <h3 class="font-bold text-base text-surface-800 dark:text-surface-100">
              Composantes essentielles
            </h3>
          </div>
          <span
            v-if="competence.composantesEssentielles?.length"
            class="text-xs font-semibold px-2 py-0.5 rounded-full bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-400"
          >
            {{ competence.composantesEssentielles.length }}
          </span>
        </div>

        <div v-if="competence.composantesEssentielles && competence.composantesEssentielles.length > 0" class="space-y-2.5 flex-1">
          <div
            v-for="(composante, index) in competence.composantesEssentielles"
            :key="index"
            class="flex items-start gap-3 p-3 rounded-xl bg-surface-50 dark:bg-surface-800/40 hover:bg-surface-100/60 dark:hover:bg-surface-800/70 transition-colors"
          >
            <span class="mt-1 w-2 h-2 rounded-full shrink-0" :class="colorStyles.dot"></span>
            <span class="text-sm text-surface-700 dark:text-surface-200 leading-relaxed">
              {{ getText(composante) }}
            </span>
          </div>
        </div>
        <div v-else class="text-sm text-muted-color italic py-6 text-center flex-1 flex items-center justify-center">
          Aucune composante essentielle renseignée.
        </div>
      </div>

      <!-- Situations Professionnelles -->
      <div
        class="rounded-2xl border p-5 bg-surface-0 dark:bg-surface-900 shadow-sm flex flex-col"
        :class="colorStyles.border"
      >
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-surface-200 dark:border-surface-800">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm" :class="colorStyles.iconBg">
              <i class="pi pi-briefcase"></i>
            </div>
            <h3 class="font-bold text-base text-surface-800 dark:text-surface-100">
              Situations professionnelles
            </h3>
          </div>
          <span
            v-if="competence.situationsProfessionnelles?.length"
            class="text-xs font-semibold px-2 py-0.5 rounded-full bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-400"
          >
            {{ competence.situationsProfessionnelles.length }}
          </span>
        </div>

        <div v-if="competence.situationsProfessionnelles && competence.situationsProfessionnelles.length > 0" class="space-y-2.5 flex-1">
          <div
            v-for="(situation, index) in competence.situationsProfessionnelles"
            :key="index"
            class="p-3.5 rounded-xl bg-surface-50 dark:bg-surface-800/40 hover:bg-surface-100/60 dark:hover:bg-surface-800/70 transition-colors border-l-4"
            :class="colorStyles.border"
          >
            <p class="text-sm text-surface-700 dark:text-surface-200 leading-relaxed whitespace-pre-line">
              {{ getText(situation) }}
            </p>
          </div>
        </div>
        <div v-else class="text-sm text-muted-color italic py-6 text-center flex-1 flex items-center justify-center">
          Aucune situation professionnelle renseignée.
        </div>
      </div>
    </div>

    <!-- Section Niveaux et Apprentissages Critiques -->
    <div class="rounded-2xl border bg-surface-0 dark:bg-surface-900 p-5 shadow-sm" :class="colorStyles.border">
      <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-surface-200 dark:border-surface-800">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm" :class="colorStyles.iconBg">
          <i class="pi pi-graduation-cap"></i>
        </div>
        <div>
          <h3 class="font-bold text-lg text-surface-900 dark:text-surface-0">
            Niveaux et Apprentissages Critiques (AC)
          </h3>
          <p class="text-xs text-muted-color">
            Déclinaison par année de formation du B.U.T.
          </p>
        </div>
      </div>

      <div v-if="sortedNiveaux.length > 0" class="space-y-4">
        <div
          v-for="niveau in sortedNiveaux"
          :key="niveau.id || niveau.ordre"
          class="rounded-xl border border-surface-200 dark:border-surface-800 overflow-hidden"
        >
          <!-- Niveau Header -->
          <div
            class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
            :class="colorStyles.cardBg"
          >
            <div class="flex items-center gap-2.5">
              <span
                class="px-2.5 py-1 rounded-lg text-xs font-bold tracking-wider uppercase"
                :class="colorStyles.badge"
              >
                B.U.T. {{ niveau.ordre }}
              </span>
              <span class="font-semibold text-sm text-surface-900 dark:text-surface-0">
                {{ niveau.libelle || `Niveau ${niveau.ordre}` }}
              </span>
            </div>
            <span
              v-if="niveau.apprentissageCritique?.length"
              class="text-xs text-muted-color font-medium"
            >
              {{ niveau.apprentissageCritique.length }} apprentissage(s) critique(s)
            </span>
          </div>

          <!-- Apprentissages Critiques List -->
          <div class="p-4 bg-surface-0 dark:bg-surface-900">
            <div
              v-if="niveau.apprentissageCritique && niveau.apprentissageCritique.length > 0"
              class="grid grid-cols-1 md:grid-cols-2 gap-3"
            >
              <div
                v-for="ac in niveau.apprentissageCritique"
                :key="ac.id || ac.code"
                class="flex items-start gap-3 p-3 rounded-xl border border-surface-100 dark:border-surface-800 hover:border-surface-300 dark:hover:border-surface-700 bg-surface-50/50 dark:bg-surface-800/30 transition-all"
              >
                <span
                  v-if="ac.code"
                  class="px-2 py-0.5 rounded-md text-xs font-mono font-bold shrink-0 border"
                  :class="colorStyles.acTag"
                >
                  {{ ac.code }}
                </span>
                <span class="text-sm text-surface-700 dark:text-surface-200 leading-snug">
                  {{ ac.libelle }}
                </span>
              </div>
            </div>
            <div v-else class="text-sm text-muted-color italic py-2">
              Aucun apprentissage critique pour ce niveau.
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-sm text-muted-color italic py-6 text-center">
        Aucun niveau défini pour cette compétence.
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
