<template>
  <label :class="['q-choice', modelValue ? toneActiveClasses[tone] : '', disabled ? 'opacity-60 !cursor-not-allowed' : '']">
    <div class="flex items-start gap-2.5 min-w-0 flex-1">
      <component :is="icon" v-if="icon" :class="['w-5 h-5 shrink-0 mt-0.5', toneIconClasses[tone]]" />
      <div class="min-w-0">
        <span class="q-choice-title">
          <slot name="title">{{ title }}</slot>
        </span>
        <span v-if="description || $slots.description" class="q-choice-desc block">
          <slot name="description">{{ description }}</slot>
        </span>
      </div>
    </div>
    <ToggleSwitch
      v-if="control === 'switch'"
      :modelValue="modelValue"
      :disabled="disabled"
      class="shrink-0"
      @update:modelValue="(v: boolean) => emit('update:modelValue', v)"
    />
    <input
      v-else
      type="checkbox"
      class="q-check mt-0.5"
      :checked="modelValue"
      :disabled="disabled"
      @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
  </label>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import ToggleSwitch from 'primevue/toggleswitch';
import { toneActiveClasses, toneIconClasses, type FormTone } from './tones';

withDefaults(defineProps<{
  modelValue: boolean;
  title?: string;
  description?: string;
  icon?: Component;
  tone?: FormTone;
  control?: 'switch' | 'checkbox';
  disabled?: boolean;
}>(), {
  tone: 'primary',
  control: 'switch',
  disabled: false
});

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
</script>
