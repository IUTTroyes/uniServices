<template>
  <div class="flex items-center gap-3">
    <div :class="['w-10 h-10 rounded-xl border flex items-center justify-center shrink-0', toneClasses[tone]]">
      <component :is="icon" v-if="icon" class="w-5 h-5" />
    </div>
    <div class="min-w-0">
      <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">{{ title }}</h2>
      <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ subtitle }}</p>
    </div>
  </div>
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
