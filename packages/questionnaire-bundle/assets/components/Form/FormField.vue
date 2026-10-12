<template>
  <div>
    <label v-if="label" :for="for" :class="['q-label', { 'q-label-required': required }]">{{ label }}</label>
    <p v-if="hint && hintPosition === 'top'" class="q-hint !mt-0 mb-2">{{ hint }}</p>
    <slot />
    <p v-if="error" class="q-error">{{ error }}</p>
    <p v-else-if="hint && hintPosition === 'bottom'" class="q-hint">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  label?: string;
  for?: string;
  hint?: string;
  hintPosition?: 'top' | 'bottom';
  error?: string | null;
  required?: boolean;
}>(), {
  hintPosition: 'bottom',
  required: false
});
</script>
