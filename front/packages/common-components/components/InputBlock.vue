<script setup>

import { computed } from 'vue'

const props = defineProps({
  id: {
    type: String,
    required: true
  },
  // value: {
  //   type: String,
  //   required: true
  // },
  label: {
    type: String,
    required: true
  },
  help: {
    type: String,
    default: null
  }
})

const computedId = computed(() => {
  return props.id || `input-${Math.random().toString(36).substring(7)}`
})

</script>

<template>
  <div class="flex flex-col gap-2">
    <label :for="computedId">
      {{ label }}
    </label>
    <InputText :id="computedId" v-model="value" :aria-describedby="help" />
    <Message size="small" severity="secondary" variant="simple">
      {{ help }}
    </Message>
  </div>
</template>

<style scoped>

</style>
