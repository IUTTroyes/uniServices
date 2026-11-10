<script setup>
import { Card } from '@components';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import { TYPES_ACTIVITE } from './options';

/** Informations d'une trace : titre, année de BUT, date, type d'activité, contexte et démarche. */
defineProps({
  scolarites: { type: Array, default: () => [] },
  modifiable: { type: Boolean, default: true },
  /** Les rattachements existants figent l'année de la trace. */
  changementAnneeBloque: { type: Boolean, default: false },
});

const titre = defineModel('titre', { type: String, default: '' });
const scolarite = defineModel('scolarite', { default: null });
const dateRealisation = defineModel('dateRealisation', { default: null });
const typeActivite = defineModel('typeActivite', { default: null });
const contexte = defineModel('contexte', { type: String, default: '' });

const libelleScolarite = (s) => [s.annee, s.anneeUniversitaire].filter(Boolean).join(' — ');
</script>

<template>
  <Card bare tag="section" class="p-4 flex flex-col gap-4">
    <h3 class="text-base font-semibold m-0">Description</h3>
    <div class="flex flex-col gap-2">
      <label for="trace-titre" class="font-medium">Titre *</label>
      <InputText id="trace-titre" v-model="titre" maxlength="255" :disabled="!modifiable" />
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
      <div class="flex flex-col gap-2">
        <label for="trace-annee" class="font-medium">Année de BUT</label>
        <span v-if="scolarites.length === 1 && scolarite" id="trace-annee" class="py-2 font-medium">
          {{ libelleScolarite(scolarites[0]) }}
        </span>
        <Select
          v-else
          id="trace-annee"
          v-model="scolarite"
          :options="scolarites"
          :option-label="libelleScolarite"
          option-value="id"
          :disabled="!modifiable || changementAnneeBloque"
          placeholder="Choisir"
        />
        <small v-if="changementAnneeBloque" class="text-muted-color">Retirez les rattachements pour changer d'année.</small>
      </div>
      <div class="flex flex-col gap-2">
        <label for="trace-date" class="font-medium">Date de réalisation</label>
        <DatePicker id="trace-date" v-model="dateRealisation" date-format="dd/mm/yy" show-icon :disabled="!modifiable" />
      </div>
      <div class="flex flex-col gap-2">
        <label for="trace-type" class="font-medium">Type d'activité</label>
        <Select id="trace-type" v-model="typeActivite" :options="TYPES_ACTIVITE" option-label="label" option-value="value" show-clear placeholder="Choisir" :disabled="!modifiable" />
      </div>
    </div>
    <div class="flex flex-col gap-2">
      <label for="trace-contexte" class="font-medium">Contexte et démarche</label>
      <Textarea id="trace-contexte" v-model="contexte" rows="6" auto-resize :disabled="!modifiable" placeholder="Situation, objectifs, votre rôle, ce que vous avez appris…" />
    </div>
  </Card>
</template>
