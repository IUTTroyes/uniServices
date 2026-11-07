<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';
import { comparer, ecartGlobal, libelleReponse, qualifierEcart } from './comparaison';
import { pourcentage } from '../../requests/autoEvaluationService';

/** Auto-évaluation soumise de l'étudiant, comparée à l'évaluation en cours (dernière version enregistrée). */
const props = defineProps({
  auto: { type: Object, required: true },
  reponsesEnseignant: { type: Array, default: () => [] },
  scoreEnseignant: { type: Number, default: null },
});

const lignes = computed(() => comparer(props.reponsesEnseignant, props.auto.reponses));
const global = computed(() => qualifierEcart(ecartGlobal(props.scoreEnseignant, props.auto.score)));
const ptsEtudiant = computed(() => pourcentage(props.auto.score));
const ptsEnseignant = computed(() => pourcentage(props.scoreEnseignant));
</script>

<template>
  <section class="rounded-lg border border-violet-200 bg-violet-50/40 dark:bg-violet-950/20 p-3 flex flex-col gap-3 text-sm">
    <div class="flex flex-wrap items-center gap-2">
      <i class="pi pi-user text-violet-600" />
      <strong>Auto-évaluation de l'étudiant</strong>
      <span v-if="ptsEtudiant !== null" class="text-muted-color">{{ ptsEtudiant }} %</span>
      <template v-if="global">
        <span v-if="ptsEnseignant !== null" class="text-muted-color">· vous : {{ ptsEnseignant }} %</span>
        <Tag :value="global.libelle" :severity="global.severite" class="ml-auto" />
      </template>
    </div>

    <p v-if="auto.reflexion" class="m-0 whitespace-pre-line italic text-muted-color">« {{ auto.reflexion }} »</p>

    <table v-if="lignes.length" class="w-full text-left border-collapse">
      <thead>
        <tr class="text-muted-color text-xs">
          <th class="font-medium py-1">Critère</th>
          <th class="font-medium py-1">Étudiant</th>
          <th class="font-medium py-1">Vous</th>
          <th class="font-medium py-1" />
        </tr>
      </thead>
      <tbody>
        <tr v-for="l in lignes" :key="l.critere" class="border-t border-violet-100">
          <td class="py-1 pr-2">{{ l.libelle }}</td>
          <td class="py-1 pr-2">
            <span v-if="l.etudiant.echelon" class="px-2 py-0.5 rounded text-xs font-semibold text-white" :style="{ background: l.etudiant.echelon.couleur || 'var(--p-primary-color)' }">{{ l.etudiant.echelon.libelle }}</span>
            <span v-else>{{ libelleReponse(l.etudiant) }}</span>
          </td>
          <td class="py-1 pr-2">
            <span v-if="l.enseignant.echelon" class="px-2 py-0.5 rounded text-xs font-semibold text-white" :style="{ background: l.enseignant.echelon.couleur || 'var(--p-primary-color)' }">{{ l.enseignant.echelon.libelle }}</span>
            <span v-else-if="l.enseignant.normalisee !== null || l.enseignant.texte">{{ libelleReponse(l.enseignant) }}</span>
            <span v-else class="text-muted-color">—</span>
          </td>
          <td class="py-1 text-right">
            <Tag v-if="l.qualification" :value="l.qualification.libelle" :severity="l.qualification.severite" />
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
