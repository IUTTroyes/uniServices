<script setup>
import { computed } from 'vue';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import { Card } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import { TYPES_CRITERE, CRITERE_IA, nouveauCritere } from './options';

/**
 * Critères d'une grille. En édition, `criteres` est modifié sur place ; sinon `affiche` (lecture seule) est présenté.
 */
const props = defineProps({
  criteres: { type: Array, required: true },
  affiche: { type: Array, default: null },
  enEdition: { type: Boolean, default: false },
});
const emit = defineEmits(['modifie']);

const cloner = (v) => JSON.parse(JSON.stringify(v));

const critereIa = computed({
  get: () => props.criteres.some((c) => c.source === 'declaration_ia'),
  set: (actif) => {
    if (actif && !critereIa.value) props.criteres.push(cloner(CRITERE_IA));
    if (!actif) props.criteres.splice(0, props.criteres.length, ...props.criteres.filter((c) => c.source !== 'declaration_ia'));
    emit('modifie');
  },
});
const ajouterCritere = () => { props.criteres.push(nouveauCritere()); emit('modifie'); };
const retirerCritere = (i) => { props.criteres.splice(i, 1); emit('modifie'); };

const poidsTotal = computed(() => props.criteres.filter((c) => c.type !== 'texte').reduce((t, c) => t + (Number(c.poids) || 0), 0));
const part = (c) => (c.type === 'texte' || !poidsTotal.value ? null : Math.round(((Number(c.poids) || 0) * 100) / poidsTotal.value));
const libelleType = (v) => TYPES_CRITERE.find((t) => t.value === v)?.label ?? v;
</script>

<template>
  <Card bare tag="section" class="p-4 flex flex-col gap-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h3 class="text-base font-semibold m-0">Critères</h3>
      <div v-if="enEdition" class="flex items-center gap-4">
        <label class="flex items-center gap-2 text-sm cursor-pointer">
          <ToggleSwitch v-model="critereIa" /> Évaluer l'usage de l'IA
        </label>
        <Button label="Critère" icon="pi pi-plus" size="small" outlined @click="ajouterCritere" />
      </div>
    </div>
  
    <draggable :list="criteres" handle=".poignee" :disabled="!enEdition" class="flex flex-col gap-3" @end="emit('modifie')">
      <div
        v-for="(c, i) in (enEdition ? criteres : affiche ?? [])"
        :key="i"
        class="border rounded-lg p-3 flex flex-col gap-3"
        :class="c.source === 'declaration_ia' ? 'border-violet-300 bg-violet-50/40 dark:bg-violet-950/20' : 'border-surface-200'"
      >
        <template v-if="enEdition">
          <div class="flex flex-wrap items-center gap-2">
            <i class="pi pi-bars poignee cursor-move text-muted-color" />
            <Tag v-if="c.source === 'declaration_ia'" value="IA" severity="contrast" icon="pi pi-sparkles" />
            <InputText v-model="c.libelle" placeholder="Libellé du critère" class="flex-1 min-w-48" @input="emit('modifie')" />
            <Select v-model="c.type" :options="TYPES_CRITERE" option-label="label" option-value="value" class="w-48" @change="emit('modifie')" />
            <Button icon="pi pi-trash" severity="danger" text rounded size="small" aria-label="Retirer le critère" @click="retirerCritere(i)" />
          </div>
          <div class="grid gap-3 md:grid-cols-2">
            <Textarea v-model="c.description" rows="2" auto-resize placeholder="Description (vue par l'étudiant si visible)" @input="emit('modifie')" />
            <Textarea v-model="c.aide" rows="2" auto-resize placeholder="Indicateurs observables (aide à l'évaluateur)" @input="emit('modifie')" />
          </div>
          <div class="flex flex-wrap items-center gap-4 text-sm">
            <label v-if="c.type !== 'texte'" class="flex items-center gap-2">
              Poids <InputNumber v-model="c.poids" :min="0" :max="100" :max-fraction-digits="2" input-class="w-16" @input="emit('modifie')" />
              <span v-if="part(c) !== null" class="text-muted-color">({{ part(c) }} %)</span>
            </label>
            <label v-if="c.type === 'note'" class="flex items-center gap-2">
              Sur <InputNumber v-model="c.noteMax" :min="1" input-class="w-16" @input="emit('modifie')" />
            </label>
            <label v-if="c.type !== 'texte'" class="flex items-center gap-2 cursor-pointer"><Checkbox v-model="c.obligatoire" binary @change="emit('modifie')" /> Obligatoire</label>
            <span v-else class="text-muted-color">Une appréciation libre est toujours facultative.</span>
            <label class="flex items-center gap-2 cursor-pointer"><Checkbox v-model="c.visibleEtudiant" binary @change="emit('modifie')" /> Visible par l'étudiant</label>
            <label class="flex items-center gap-2 cursor-pointer"><Checkbox v-model="c.utiliseAutoEval" binary @change="emit('modifie')" /> Utilisé en auto-évaluation</label>
          </div>
        </template>
        <template v-else>
          <div class="flex flex-wrap items-center gap-2">
            <Tag v-if="c.source === 'declaration_ia'" value="IA" severity="contrast" icon="pi pi-sparkles" />
            <span class="font-medium flex-1">{{ c.libelle }}</span>
            <Tag :value="libelleType(c.type)" severity="secondary" />
            <Tag v-if="c.type !== 'texte'" :value="`poids ${c.poids}`" severity="secondary" />
          </div>
          <p v-if="c.description" class="text-sm m-0">{{ c.description }}</p>
          <p v-if="c.aide" class="text-sm text-muted-color m-0">{{ c.aide }}</p>
        </template>
      </div>
    </draggable>
  </Card>
</template>
