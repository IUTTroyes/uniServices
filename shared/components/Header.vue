<script setup>
import {defineProps} from "vue";

const props = defineProps({
  icon: {
    type: String,
    required: true
  },
  titre: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  }
});
</script>

<template>
  <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
    <div>
      <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2 mb-0!">
        <i :class="props.icon" class="text-primary-500 text-2xl!" />
        {{ props.titre }}
      </h1>
      <p class="text-gray-600 dark:text-gray-400 mt-1">
        {{ props.description }}
      </p>
    </div>
    <Button severity="primary" label="Retour" icon="pi pi-arrow-left" @click="$router.go(-1)" />
  </div>
</template>

<style scoped>

</style>
