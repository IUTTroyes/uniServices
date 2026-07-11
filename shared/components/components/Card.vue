<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  icon: {
    type: [String, Object, Function],
    default: null
  },
  color: {
    type: String,
    default: ''
  },
  iconClass: {
    type: String,
    default: ''
  },
  iconBgClass: {
    type: String,
    default: ''
  },
  badge: {
    type: String,
    default: ''
  },
  badgeSeverity: {
    type: String,
    default: 'secondary'
  },
  bodyClass: {
    type: String,
    default: ''
  }
});

const colorMap = {
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
  if (!props.color) return null;
  return colorMap[props.color] || colorMap.blue;
});
</script>

<template>
  <div class="card">
    <div v-if="title || $slots.header" class="card-header flex justify-between items-center gap-4">
      <slot name="header">
        <div class="flex flex-col min-w-0">
          <div class="flex items-center gap-3">
            <span v-if="icon" :class="[
              'flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center border transition-all duration-300',
              colorClasses ? `${colorClasses.bg} ${colorClasses.text} ${colorClasses.border}` : 'bg-slate-50 dark:bg-slate-900/50 border-slate-100 dark:border-slate-700/50',
              iconBgClass
            ]">
              <!-- Support PrimeIcons string and Heroicons component -->
              <i v-if="typeof icon === 'string'" :class="[icon, 'text-sm', colorClasses ? '' : iconClass]" />
              <component v-else :is="icon" :class="['w-4 h-4', colorClasses ? '' : iconClass]" />
            </span>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-snug">{{ title }}</h3>
          </div>
          <p v-if="subtitle" :class="['text-[11px] text-slate-400 mt-0.5 leading-normal', icon ? 'pl-11' : '']">
            {{ subtitle }}
          </p>
        </div>
        <div v-if="badge" class="flex-shrink-0">
          <Tag :value="badge" :severity="badgeSeverity" class="text-xs font-bold font-sans" />
        </div>
      </slot>
    </div>
    <div :class="['card-body', bodyClass]">
      <slot />
    </div>
  </div>
</template>

<style scoped></style>
