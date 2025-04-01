<script setup>

import { computed } from 'vue'

const props = defineProps({
  id: {
    type: String
  },
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

const emit = defineEmits(['update:modelValue'])

const updateValue = (value) => {
  emit('update:modelValue', value)
}

</script>

<template>
  <div class="flex flex-col gap-2">
    <label :for="computedId">
      {{ label }}
    </label>
    <InputText :id="computedId"
               @update:modelValue="updateValue"
               :aria-describedby="help" />
    <Message size="small" severity="secondary" variant="simple">
      {{ help }}
    </Message>
  </div>
</template>

<style scoped>

</style>
