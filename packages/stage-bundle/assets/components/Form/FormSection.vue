<template>
  <div class="q-section">
    <div class="flex items-start gap-3">
      <div
        v-if="icon"
        :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border shadow-2xs', toneClasses.container]"
      >
        <component :is="icon" :class="['w-5 h-5', toneClasses.icon]" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">
            {{ title }}
          </h3>
          <slot name="badge" />
        </div>
        <p v-if="description" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
          {{ description }}
        </p>
      </div>
    </div>
    <div class="space-y-4 pt-1">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import { TONE_CLASSES, type ToneColor } from './tones';

const props = withDefaults(defineProps<{
  title: string;
  description?: string;
  icon?: Component;
  tone?: ToneColor;
}>(), {
  tone: 'teal'
});

const toneClasses = computed(() => TONE_CLASSES[props.tone] || TONE_CLASSES.teal);
</script>
