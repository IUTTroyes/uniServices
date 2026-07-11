<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  label: string;
  value: string | number;
  icon: any;
  color: string;
  description?: string;
}>();

const colorMap: Record<string, { bg: string; text: string; card: string }> = {
  blue: {
    bg: 'bg-blue-100 dark:bg-blue-900/30',
    text: 'text-blue-600 dark:text-blue-400',
    card: 'bg-gradient-to-br from-blue-50 to-blue-100/30 dark:from-blue-950/20 dark:to-transparent border-blue-200/50 dark:border-blue-900/50'
  },
  green: {
    bg: 'bg-green-100 dark:bg-green-900/30',
    text: 'text-green-600 dark:text-green-400',
    card: 'bg-gradient-to-br from-green-50 to-green-100/30 dark:from-green-950/20 dark:to-transparent border-green-200/50 dark:border-green-900/50'
  },
  yellow: {
    bg: 'bg-yellow-100 dark:bg-yellow-900/30',
    text: 'text-yellow-600 dark:text-yellow-400',
    card: 'bg-gradient-to-br from-yellow-50 to-yellow-100/30 dark:from-yellow-950/20 dark:to-transparent border-yellow-200/50 dark:border-yellow-900/50'
  },
  purple: {
    bg: 'bg-purple-100 dark:bg-purple-900/30',
    text: 'text-purple-600 dark:text-purple-400',
    card: 'bg-gradient-to-br from-purple-50 to-purple-100/30 dark:from-purple-950/20 dark:to-transparent border-purple-200/50 dark:border-purple-900/50'
  },
  red: {
    bg: 'bg-red-100 dark:bg-red-900/30',
    text: 'text-red-600 dark:text-red-400',
    card: 'bg-gradient-to-br from-red-50 to-red-100/30 dark:from-red-950/20 dark:to-transparent border-red-200/50 dark:border-red-900/50'
  },
  indigo: {
    bg: 'bg-indigo-100 dark:bg-indigo-900/30',
    text: 'text-indigo-600 dark:text-indigo-400',
    card: 'bg-gradient-to-br from-indigo-50 to-indigo-100/30 dark:from-indigo-950/20 dark:to-transparent border-indigo-200/50 dark:border-indigo-900/50'
  },
  gray: {
    bg: 'bg-gray-100 dark:bg-gray-900/30',
    text: 'text-gray-600 dark:text-gray-400',
    card: 'bg-gradient-to-br from-gray-50 to-gray-100/30 dark:from-gray-950/20 dark:to-transparent border-gray-200/50 dark:border-gray-900/50'
  },
  emerald: {
    bg: 'bg-emerald-100 dark:bg-emerald-900/30',
    text: 'text-emerald-600 dark:text-emerald-400',
    card: 'bg-gradient-to-br from-emerald-50 to-emerald-100/30 dark:from-emerald-950/20 dark:to-transparent border-emerald-200/50 dark:border-emerald-900/50'
  },
  orange: {
    bg: 'bg-orange-100 dark:bg-orange-900/30',
    text: 'text-orange-600 dark:text-orange-400',
    card: 'bg-gradient-to-br from-orange-50 to-orange-100/30 dark:from-orange-950/20 dark:to-transparent border-orange-200/50 dark:border-orange-900/50'
  },
  teal: {
    bg: 'bg-teal-100 dark:bg-teal-900/30',
    text: 'text-teal-600 dark:text-teal-400',
    card: 'bg-gradient-to-br from-teal-50 to-teal-100/30 dark:from-teal-950/20 dark:to-transparent border-teal-200/50 dark:border-teal-900/50'
  },
  pink: {
    bg: 'bg-pink-100 dark:bg-pink-900/30',
    text: 'text-pink-600 dark:text-pink-400',
    card: 'bg-gradient-to-br from-pink-50 to-pink-100/30 dark:from-pink-950/20 dark:to-transparent border-pink-200/50 dark:border-pink-900/50'
  },
  indigo: {
    bg: 'bg-indigo-100 dark:bg-indigo-900/30',
    text: 'text-indigo-600 dark:text-indigo-400',
    card: 'bg-gradient-to-br from-indigo-50 to-indigo-100/30 dark:from-indigo-950/20 dark:to-transparent border-indigo-200/50 dark:border-indigo-900/50'
  }
};

const colorClasses = computed(() => {
  return colorMap[props.color] || colorMap.blue;
});

const textColor = computed(() => {
  return 'text-' + props.color + '-500 dark:text-' + props.color + '-500';
});
</script>

<template>
  <div :class="['card p-5 border shadow-sm rounded-xl', colorClasses.card]">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-semibold uppercase tracking-wider" :class="colorClasses.text">{{ props.label }}</p>
        <p class="text-4xl font-bold" :class="textColor">
          {{ props.value }}
        </p>
        <p v-if="props.description" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
          {{ props.description }}
        </p>
      </div>
      <div :class="['p-2.5 rounded-lg', colorClasses.bg, colorClasses.text]">
        <component :is="props.icon" class="w-8 h-8" />
      </div>
    </div>
  </div>
</template>
