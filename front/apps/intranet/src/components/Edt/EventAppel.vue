<script setup lang="ts">
import { computed } from 'vue';
import { heuresMinutesDate, formatDateLong } from '@helpers/date.js';

type EventType = {
  id: number;
  anneeUniversitaire: string;
  debut: string;
  fin: string;
  start?: Date;
  end?: Date;
  date: Date;
  codeModule: string;
  title?: string;
  groupe?: {
    id?: number;
    libelle?: string;
  };
  semestre: {
    id?: number;
    libelle?: string;
  };
};

const props = defineProps<{ event: EventType }>();

const date = computed(() => props.event.date ?? undefined);
const debutDate = computed(() => props.event.start ?? props.event.debut ?? undefined);
const finDate = computed(() => props.event.end ?? props.event.fin ?? undefined);
</script>

<template>
  <div class="">
    <div class="">{{ props.event.title || `${props.event.codeModule} - ${props.event.title || ''}` }}</div>
    <div class="">{{ date ? formatDateLong(date) : '' }}</div>
    <div class="">
      <span>{{ debutDate ? heuresMinutesDate(debutDate) : '' }}</span>
      <span v-if="debutDate && finDate"> - </span>
      <span>{{ finDate ? heuresMinutesDate(finDate) : '' }}</span>
    </div>
    <div class="">
      {{ props.event.semestre.libelle || props.event.semestre.id }} - {{ props.event.groupe.libelle || props.event.groupe.id }}
    </div>
  </div>
</template>

<style scoped>
</style>
