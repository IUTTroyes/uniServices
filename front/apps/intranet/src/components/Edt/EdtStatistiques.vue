<script setup>
import {onMounted, ref} from "vue";
import {ErrorView} from "@components";
import {ValidatedInput} from "@components";

const hasError = false;
const minDate = ref();
const maxDate = ref();

onMounted(() => {
  //todo: calculer les dates min et max en fonction de l'année universitaire
  const today = new Date();
  const priorDate = new Date().setDate(today.getDate() - 30);
  minDate.value = new Date(priorDate);
  maxDate.value = today;
})
</script>

<template>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 mb-6 w-full bg-neutral-100/20">
    <div class="text-lg font-bold mb-4">Filtres</div>

    <div class="flex items-start gap-4">
        <ValidatedInput
            v-model="hasError"
            name="date"
            label="Période"
            type="date"
            :rules="[]"
            selectionMode="range"
            :manual-input="false"
            :min-date="minDate"
            :max-date="maxDate"
        />

      <ValidatedInput
          v-model="hasError"
          name="enseignant"
          label="Enseignant"
          type="select"
          :rules="[]"
          class="w-1/5"
      />

      <ValidatedInput
          v-model="hasError"
          name="semestre"
          label="Semestres"
          type="select"
          :rules="[]"
          class="w-1/5"
      />

      <ValidatedInput
          v-model="hasError"
          name="groupe"
          label="Groupes"
          type="select"
          :rules="[]"
          class="w-1/5"
      />

      <ValidatedInput
          v-model="hasError"
          name="salle"
          label="Salles"
          type="select"
          :rules="[]"
          class="w-1/5"
      />
    </div>
  </div>
</template>

<style scoped>
#date {
  width: 100% !important;
}
</style>
