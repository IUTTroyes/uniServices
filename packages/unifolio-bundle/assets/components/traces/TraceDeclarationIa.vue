<script setup>
import { Card } from '@components';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { CONCEPTION_IA, REALISATION_IA } from './options';

/** Déclaration d'usage de l'IA d'une trace (obligatoire au dépôt). */
defineProps({
  modifiable: { type: Boolean, default: true },
});

const conception = defineModel('conception', { default: null });
const realisation = defineModel('realisation', { default: null });
const detail = defineModel('detail', { type: String, default: '' });
</script>

<template>
  <Card bare tag="section" class="p-4 flex flex-col gap-4">
    <h3 class="text-base font-semibold m-0">Usage de l'IA</h3>
    <p class="text-sm text-muted-color m-0">Déclaration obligatoire pour déposer la trace.</p>
    <div class="flex flex-col gap-2">
      <label for="trace-conception-ia" class="font-medium">Dans la conception</label>
      <Select id="trace-conception-ia" v-model="conception" :options="CONCEPTION_IA" option-label="label" option-value="value" placeholder="À renseigner" :disabled="!modifiable" />
    </div>
    <div class="flex flex-col gap-2">
      <label for="trace-realisation-ia" class="font-medium">Dans la réalisation</label>
      <Select id="trace-realisation-ia" v-model="realisation" :options="REALISATION_IA" option-label="label" option-value="value" placeholder="À renseigner" :disabled="!modifiable" />
    </div>
    <div class="flex flex-col gap-2">
      <label for="trace-ia-detail" class="font-medium">Précisions</label>
      <Textarea id="trace-ia-detail" v-model="detail" rows="3" auto-resize placeholder="Outils utilisés et pour quoi faire" :disabled="!modifiable" />
    </div>
  </Card>
</template>
