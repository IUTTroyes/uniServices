<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';
import { libelleReponse } from './comparaison';
import { pourcentage } from '../../requests/autoEvaluationService';

/** Retour d'un enseignant tel que l'étudiant le voit : appréciation et critères communiqués. */
const props = defineProps({ retour: { type: Object, required: true } });

const revision = computed(() => props.retour.statut.value === 'revision_demandee');
const score = computed(() => pourcentage(props.retour.score));
const date = computed(() => (props.retour.rendueLe ? new Date(props.retour.rendueLe).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : null));
</script>

<template>
  <div
    class="rounded-lg border p-3 flex flex-col gap-2 text-sm"
    :class="revision ? 'border-amber-300 bg-amber-50/60 dark:bg-amber-950/20' : 'border-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/20'"
  >
    <div class="flex flex-wrap items-center gap-2">
      <i :class="revision ? 'pi pi-exclamation-circle text-amber-600' : 'pi pi-check-circle text-emerald-600'" />
      <strong>{{ retour.evaluateur.nom }}</strong>
      <span v-if="date" class="text-muted-color">{{ date }}</span>
      <Tag v-if="revision" value="Révision demandée" severity="warn" />
      <Tag v-else-if="score !== null" :value="`${score} %`" severity="success" />
    </div>

    <p v-if="retour.appreciation" class="m-0 whitespace-pre-line">{{ retour.appreciation }}</p>

    <ul v-if="!revision && retour.reponses.length" class="list-none p-0 m-0 flex flex-col gap-1">
      <li v-for="r in retour.reponses" :key="r.critere" class="flex flex-wrap items-baseline gap-2">
        <span class="text-muted-color">{{ r.libelle }} :</span>
        <span
          v-if="r.echelon"
          class="px-2 py-0.5 rounded text-xs font-semibold text-white"
          :style="{ background: r.echelon.couleur || 'var(--p-primary-color)' }"
        >{{ r.echelon.libelle }}</span>
        <span v-else class="font-medium whitespace-pre-line">{{ libelleReponse(r) }}</span>
      </li>
    </ul>
  </div>
</template>
