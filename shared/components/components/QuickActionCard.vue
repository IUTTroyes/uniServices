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
    iconBg: 'bg-blue-100 dark:bg-blue-950/70 border border-blue-200 dark:border-blue-900/50',
    iconText: 'text-blue-700 dark:text-blue-300',
    btn: 'bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/70 dark:hover:bg-blue-900/70 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800/60 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors'
  },
  green: {
    iconBg: 'bg-emerald-100 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-900/50',
    iconText: 'text-emerald-700 dark:text-emerald-300',
    btn: 'bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/70 dark:hover:bg-emerald-900/70 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800/60 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors'
  },
  purple: {
    iconBg: 'bg-purple-100 dark:bg-purple-950/70 border border-purple-200 dark:border-purple-900/50',
    iconText: 'text-purple-700 dark:text-purple-300',
    btn: 'bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/70 dark:hover:bg-purple-900/70 text-purple-800 dark:text-purple-200 border border-purple-200 dark:border-purple-800/60 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors'
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
    class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/80 p-4.5 flex items-center gap-4 cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-750/50 hover:border-slate-300 dark:hover:border-slate-600 transition-all duration-200 shadow-sm hover:shadow"
  >
    <div :class="['w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-xs', colorClasses.iconBg]">
      <component :is="props.icon" :class="['w-5 h-5', colorClasses.iconText]" />
    </div>
    <div class="flex-1 min-w-0">
      <h3 class="font-bold text-gray-900 dark:text-white text-base leading-tight">{{ props.title }}</h3>
      <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-normal">{{ props.description }}</p>
    </div>
    <div :class="[colorClasses.btn, 'shrink-0']">
      {{ props.buttonLabel }}
    </div>
  </router-link>

  <div
    v-else
    @click="$emit('action')"
    class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/80 p-4.5 flex items-center gap-4 cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-750/50 hover:border-slate-300 dark:hover:border-slate-600 transition-all duration-200 shadow-sm hover:shadow"
  >
    <div :class="['w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-xs', colorClasses.iconBg]">
      <component :is="props.icon" :class="['w-5 h-5', colorClasses.iconText]" />
    </div>
    <div class="flex-1 min-w-0">
      <h3 class="font-bold text-gray-900 dark:text-white text-base leading-tight">{{ props.title }}</h3>
      <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-normal">{{ props.description }}</p>
    </div>
    <div :class="[colorClasses.btn, 'shrink-0']">
      {{ props.buttonLabel }}
    </div>
  </div>
</template>
