<script setup lang="ts">
import { computed } from 'vue';
import { SimpleSkeleton } from '@components';

const props = withDefaults(defineProps<{
  label: string;
  value: string | number;
  icon?: any;
  color?: string;
  description?: string;
  loading?: boolean;
  to?: string | object;
}>(), {
  icon: null,
  color: 'blue',
  loading: false,
  description: '',
  to: undefined,
});

const emit = defineEmits<{
  click: [event: MouseEvent]
}>();

const colorMap: Record<string, { bg: string; text: string; border: string }> = {
  blue: {
    bg: 'bg-blue-50 dark:bg-blue-950/20',
    text: 'text-blue-600 dark:text-blue-400',
    border: 'border-blue-100 dark:border-blue-900/30'
  },
  green: {
    bg: 'bg-green-50 dark:bg-green-950/20',
    text: 'text-green-600 dark:text-green-400',
    border: 'border-green-100 dark:border-green-900/30'
  },
  emerald: {
    bg: 'bg-emerald-50 dark:bg-emerald-950/20',
    text: 'text-emerald-600 dark:text-emerald-400',
    border: 'border-emerald-100 dark:border-emerald-900/30'
  },
  yellow: {
    bg: 'bg-yellow-50 dark:bg-yellow-950/20',
    text: 'text-yellow-600 dark:text-yellow-400',
    border: 'border-yellow-100 dark:border-yellow-900/30'
  },
  amber: {
    bg: 'bg-amber-50 dark:bg-amber-950/20',
    text: 'text-amber-600 dark:text-amber-400',
    border: 'border-amber-100 dark:border-amber-900/30'
  },
  purple: {
    bg: 'bg-purple-50 dark:bg-purple-950/20',
    text: 'text-purple-600 dark:text-purple-400',
    border: 'border-purple-100 dark:border-purple-900/30'
  },
  violet: {
    bg: 'bg-violet-50 dark:bg-violet-950/20',
    text: 'text-violet-600 dark:text-violet-400',
    border: 'border-violet-100 dark:border-violet-900/30'
  },
  indigo: {
    bg: 'bg-indigo-50 dark:bg-indigo-950/20',
    text: 'text-indigo-600 dark:text-indigo-400',
    border: 'border-indigo-100 dark:border-indigo-900/30'
  },
  red: {
    bg: 'bg-red-50 dark:bg-red-950/20',
    text: 'text-red-600 dark:text-red-400',
    border: 'border-red-100 dark:border-red-900/30'
  },
  pink: {
    bg: 'bg-pink-50 dark:bg-pink-950/20',
    text: 'text-pink-600 dark:text-pink-400',
    border: 'border-pink-100 dark:border-pink-900/30'
  },
  teal: {
    bg: 'bg-teal-50 dark:bg-teal-950/20',
    text: 'text-teal-600 dark:text-teal-400',
    border: 'border-teal-100 dark:border-teal-900/30'
  },
  orange: {
    bg: 'bg-orange-50 dark:bg-orange-950/20',
    text: 'text-orange-600 dark:text-orange-400',
    border: 'border-orange-100 dark:border-orange-900/30'
  },
  gray: {
    bg: 'bg-gray-50 dark:bg-gray-950/20',
    text: 'text-gray-600 dark:text-gray-400',
    border: 'border-gray-100 dark:border-gray-900/30'
  }
};

const colorClasses = computed(() => {
  if (!props.color) return colorMap.blue;
  return colorMap[props.color] || colorMap.blue;
});

function handleClick(event: MouseEvent) {
  emit('click', event);
}
</script>

<template>
  <component
    :is="props.to ? 'router-link' : 'div'"
    :to="props.to"
    :class="[
      'card w-full flex items-center justify-center flex-col transition-all duration-200 no-underline text-inherit',
      props.to ? 'cursor-pointer hover:shadow-md hover:border-primary-400/50 hover:-translate-y-0.5 group' : ''
    ]"
    @click="handleClick"
  >
    <div class="font-bold text-lg card-header text-center w-full">{{ props.label }}</div>
    <div class="flex items-center gap-3 card-body">
      <!-- Icon supporting both PrimeIcons class string and Heroicons component -->
      <span
        v-if="props.icon"
        :class="[
          'w-10 h-10 rounded-xl flex items-center justify-center border transition-all duration-300 shrink-0',
          colorClasses.bg,
          colorClasses.text,
          colorClasses.border
        ]"
      >
        <i v-if="typeof props.icon === 'string'" :class="[props.icon, 'text-xl']" />
        <component v-else :is="props.icon" class="w-6 h-6" />
      </span>

      <SimpleSkeleton v-if="props.loading" class="w-20 h-10" />
      <span v-else :class="colorClasses.text" class="text-4xl font-extrabold tracking-tight">
        {{ props.value }}
      </span>
    </div>
    <p v-if="props.description" class="text-xs text-gray-600 dark:text-gray-400 mt-1 text-center">
      {{ props.description }}
    </p>
  </component>
</template>
