<script setup>
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import { Card } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';

/**
 * Échelle d'une grille (niveaux communs à tous les critères « échelle »).
 * En édition, `echelons` est modifié sur place ; sinon `affiche` (lecture seule) est présenté.
 */
const props = defineProps({
  echelons: { type: Array, required: true },
  affiche: { type: Array, default: null },
  enEdition: { type: Boolean, default: false },
});
const emit = defineEmits(['modifie']);

const ajouterEchelon = () => {
  const max = Math.max(-1, ...props.echelons.map((e) => Number(e.valeur) || 0));
  props.echelons.push({ libelle: '', valeur: max + 1, couleur: '#16a34a', descripteur: '' });
  emit('modifie');
};
const retirerEchelon = (i) => { props.echelons.splice(i, 1); emit('modifie'); };
</script>

<template>
  <Card bare tag="section" class="p-4 flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold m-0">Échelle</h3>
        <p class="text-sm text-muted-color m-0">Commune à tous les critères « échelle » de la grille.</p>
      </div>
      <Button v-if="enEdition" label="Niveau" icon="pi pi-plus" size="small" outlined @click="ajouterEchelon" />
    </div>
  
    <draggable :list="echelons" handle=".poignee" :disabled="!enEdition" class="flex flex-col gap-2" @end="emit('modifie')">
      <div v-for="(e, i) in (enEdition ? echelons : affiche ?? [])" :key="i" class="flex flex-wrap items-center gap-2">
        <i v-if="enEdition" class="pi pi-bars poignee cursor-move text-muted-color" />
        <template v-if="enEdition">
          <input v-model="e.couleur" type="color" class="w-9 h-9 rounded cursor-pointer border-0 p-0" aria-label="Couleur" @input="emit('modifie')" />
          <InputText v-model="e.libelle" placeholder="Libellé" class="w-48" @input="emit('modifie')" />
          <InputNumber v-model="e.valeur" :min-fraction-digits="0" :max-fraction-digits="2" input-class="w-20" aria-label="Valeur" @input="emit('modifie')" />
          <InputText v-model="e.descripteur" placeholder="Descripteur (facultatif)" class="flex-1 min-w-48" @input="emit('modifie')" />
          <Button icon="pi pi-trash" severity="danger" text rounded size="small" aria-label="Retirer le niveau" @click="retirerEchelon(i)" />
        </template>
        <template v-else>
          <span class="text-sm font-semibold px-2 py-1 rounded text-white" :style="{ background: e.couleur || 'var(--p-primary-color)' }">{{ e.libelle }}</span>
          <span class="text-sm text-muted-color">valeur {{ e.valeur }}<template v-if="e.descripteur"> — {{ e.descripteur }}</template></span>
        </template>
      </div>
    </draggable>
  </Card>
</template>
