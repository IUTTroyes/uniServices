<script setup lang="ts">
import {computed, onMounted, ref} from 'vue';
import { heuresMinutesDate, formatDateLong } from '@helpers/date.js';
import {getEtudiantsService} from "@requests/";
import PersonCard from "../Trombinoscope/PersonCard.vue";

type Event = {
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

const props = defineProps<{ event: Event }>();

const date = computed(() => props.event.date ?? undefined);
const debutDate = computed(() => props.event.start ?? props.event.debut ?? undefined);
const finDate = computed(() => props.event.end ?? props.event.fin ?? undefined);

const etudiants = ref([]);

onMounted(async () => {
  console.log(props.event);
  await getEtudiants();
})

const getEtudiants = async () => {
  const params = {
    groupe: props.event.groupe.id,
  }
  etudiants.value = await getEtudiantsService(params, '', false, false);

  console.log(etudiants.value);
}
</script>

<template>
  <div class="mx-12">
    <div class="flex justify-between items-center">
      <div>
        <div class="">{{ date ? formatDateLong(date) : '' }}</div>
        <div class="">
          <span>{{ debutDate ? heuresMinutesDate(debutDate) : '' }}</span>
          <span v-if="debutDate && finDate"> - </span>
          <span>{{ finDate ? heuresMinutesDate(finDate) : '' }}</span>
        </div>
      </div>
      <div>
        <div class="">{{ props.event.title || `${props.event.codeModule} - ${props.event.title || ''}` }}</div>
        <div class="">
          {{ props.event.semestre.libelle || props.event.semestre.id }} - {{ props.event.groupe.libelle || props.event.groupe.id }}
        </div>
      </div>
    </div>
    <Divider></Divider>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <PersonCard
          v-for="etudiant in etudiants"
          :key="etudiant.id"
          :person="etudiant"
          :mode="'students'"
          :view-mode="'grid'"
      />
    </div>
  </div>
</template>

<style scoped>
</style>
