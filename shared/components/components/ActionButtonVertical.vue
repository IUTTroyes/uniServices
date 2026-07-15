<template>
  <component
    :is="to ? 'router-link' : 'button'"
    :to="to"
    :disabled="disabled"
    class="flex flex-col items-center justify-center p-3 rounded-xl border transition-all duration-200 group text-center min-w-[120px]"
    :class="[
      severityClasses,
      disabled ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105 active:scale-95 cursor-pointer shadow-sm hover:shadow-md'
    ]"
    @click="handleClick"
  >
    <component :is="icon" class="w-5 h-5 mb-1.5 transition-transform group-hover:scale-110" />
    <span class="text-xs font-semibold leading-tight select-none">{{ label }}</span>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  to?: string | object;
  icon: any;
  label: string;
  severity?: 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'help' | 'danger';
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  severity: 'secondary',
  disabled: false
});

const emit = defineEmits<{
  click: [event: MouseEvent]
}>();

function handleClick(event: MouseEvent) {
  if (!props.disabled) {
    emit('click', event);
  }
}

const severityClasses = computed(() => {
  switch (props.severity) {
    case 'primary':
      return 'bg-orange-50 hover:bg-orange-100 text-orange-600 border-orange-200 hover:border-orange-300 dark:bg-orange-950/20 dark:hover:bg-orange-900/40 dark:text-orange-400 dark:border-orange-900/50';
    case 'success':
      return 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border-emerald-200 hover:border-emerald-300 dark:bg-emerald-950/20 dark:hover:bg-emerald-900/40 dark:text-emerald-400 dark:border-emerald-900/50';
    case 'info':
      return 'bg-blue-50 hover:bg-blue-100 text-blue-600 border-blue-200 hover:border-blue-300 dark:bg-blue-950/20 dark:hover:bg-blue-900/40 dark:text-blue-400 dark:border-blue-900/50';
    case 'warning':
      return 'bg-amber-50 hover:bg-amber-100 text-amber-600 border-amber-200 hover:border-amber-300 dark:bg-amber-950/20 dark:hover:bg-amber-900/40 dark:text-amber-400 dark:border-amber-900/50';
    case 'help':
      return 'bg-purple-50 hover:bg-purple-100 text-purple-600 border-purple-200 hover:border-purple-300 dark:bg-purple-950/20 dark:hover:bg-purple-900/40 dark:text-purple-400 dark:border-purple-900/50';
    case 'danger':
      return 'bg-red-50 hover:bg-red-100 text-red-600 border-red-200 hover:border-red-300 dark:bg-red-950/20 dark:hover:bg-red-900/40 dark:text-red-400 dark:border-red-900/50';
    case 'secondary':
    default:
      return 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200 hover:border-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700/80 dark:text-gray-300 dark:border-gray-700';
  }
});
</script>
