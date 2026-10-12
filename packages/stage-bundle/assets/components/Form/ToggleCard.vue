<template>
  <label
    :class="[
      'q-choice',
      modelValue ? 'q-choice-active' : ''
    ]"
  >
    <input
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      class="q-check mt-0.5"
      @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <div class="flex-1 min-w-0">
      <span class="q-choice-title">{{ title }}</span>
      <p v-if="description" class="q-choice-desc">{{ description }}</p>
      <slot />
    </div>
  </label>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean;
  title: string;
  description?: string;
  disabled?: boolean;
}>();

defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
}>();
</script>
