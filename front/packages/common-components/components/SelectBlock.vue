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
  modelValue: {
    type: [String, Number, Object],
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])


const computedId = computed(() => {
  return props.id || `input-${Math.random().toString(36).substring(7)}`
})

const updateValue = (value) => {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <label :for="computedId">
      {{ label }}
    </label>
    <Select :aria-describedby="help"
            @update:modelValue="updateValue"
            :disabled="disabled"
            :id="computedId"
            :options="data" :optionLabel="optionLabel" />
    <Message size="small" severity="secondary" variant="simple">
      {{ help }}
    </Message>
  </div>
</template>

<style scoped>

</style>
