<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  title: string;
  description: string;
  icon: any;
  color: 'blue' | 'green' | 'purple' | string;
  buttonLabel: string;
  to?: string | object;
}>();

defineEmits<{
  (e: 'action'): void;
}>();

const colorMap: Record<string, { iconBg: string; iconText: string; btn: string }> = {
  blue: {
    iconBg: 'bg-blue-100 dark:bg-blue-900/40',
    iconText: 'text-blue-600 dark:text-blue-400',
    btn: 'btn-primary bg-blue-100 hover:bg-blue-200 dark:bg-blue-950 text-blue-700 dark:text-blue-300 px-3 py-1.5 rounded-lg text-sm font-semibold'
  },
  green: {
    iconBg: 'bg-green-100 dark:bg-green-900/40',
    iconText: 'text-green-600 dark:text-green-400',
    btn: 'btn-secondary bg-green-100 hover:bg-green-200 dark:bg-green-950 text-green-700 dark:text-green-300 px-3 py-1.5 rounded-lg text-sm font-semibold'
  },
  purple: {
    iconBg: 'bg-purple-100 dark:bg-purple-900/40',
    iconText: 'text-purple-600 dark:text-purple-400',
    btn: 'btn-secondary bg-purple-100 hover:bg-purple-200 dark:bg-purple-950 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-lg text-sm font-semibold'
  }
};

const colorClasses = computed(() => {
  return colorMap[props.color] || colorMap.blue;
});
</script>

<template>
  <router-link
    v-if="props.to"
    :to="props.to"
    class="card flex items-center space-x-4 cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-all duration-200 shadow-sm hover:shadow"
  >
    <div :class="['w-12 h-12 rounded-lg flex items-center justify-center shrink-0', colorClasses.iconBg]">
      <component :is="props.icon" :class="['w-6 h-6', colorClasses.iconText]" />
    </div>
    <div class="flex-1">
      <h3 class="font-bold text-gray-900 dark:text-white">{{ props.title }}</h3>
      <p class="text-xs text-gray-500">{{ props.description }}</p>
    </div>
    <div :class="colorClasses.btn">
      {{ props.buttonLabel }}
    </div>
  </router-link>

  <div
    v-else
    @click="$emit('action')"
    class="card flex items-center space-x-4 cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-all duration-200 shadow-sm hover:shadow"
  >
    <div :class="['w-12 h-12 rounded-lg flex items-center justify-center shrink-0', colorClasses.iconBg]">
      <component :is="props.icon" :class="['w-6 h-6', colorClasses.iconText]" />
    </div>
    <div class="flex-1">
      <h3 class="font-bold text-gray-900 dark:text-white">{{ props.title }}</h3>
      <p class="text-xs text-gray-500">{{ props.description }}</p>
    </div>
    <div :class="colorClasses.btn">
      {{ props.buttonLabel }}
    </div>
  </div>
</template>
