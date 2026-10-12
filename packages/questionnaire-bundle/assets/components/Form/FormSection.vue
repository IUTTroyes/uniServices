<template>
  <section class="q-section">
    <header class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-2.5 min-w-0">
        <div
          v-if="icon"
          :class="['w-7 h-7 rounded-lg border flex items-center justify-center shrink-0', toneClasses[tone]]"
        >
          <component :is="icon" class="w-4 h-4" />
        </div>
        <div class="min-w-0">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ title }}</h3>
          <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
        </div>
      </div>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </header>
    <slot />
  </section>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { toneClasses, type FormTone } from './tones';

withDefaults(defineProps<{
  title: string;
  subtitle?: string;
  icon?: Component;
  tone?: FormTone;
}>(), {
  tone: 'primary'
});
</script>
