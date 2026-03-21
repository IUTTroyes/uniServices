<script setup>

import { computed } from 'vue'

const props = defineProps({
  id: {
    type: String,
    required: true
  },
  label: {
    type: String,
    required: true
  },
  help: {
    type: String,
    default: null
  },
  data: {
    type: Array,
    required: true
  },
  optionLabel: {
    type: String,
    default: 'label'
  },
})

</script>

<template>
  <div class="flex flex-col gap-2">
    <label :for="computedId">
      {{ label }}
    </label>
    <Select v-model="value" :aria-describedby="help" :options="data" :optionLabel="optionLabel" />
    <Message size="small" severity="secondary" variant="simple">
      {{ help }}
    </Message>
  </div>
</template>

<style scoped>

</style>
