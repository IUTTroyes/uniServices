<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Aucune donnée disponible'
  },
  description: {
    type: String,
    default: ''
  },
  icon: {
    type: [String, Object, Function],
    default: 'pi pi-inbox'
  },
  color: {
    type: String,
    default: 'gray' // 'gray' | 'violet' | 'emerald' | 'amber' | 'blue' | 'rose' | 'indigo'
  },
  actionLabel: {
    type: String,
    default: ''
  },
  actionIcon: {
    type: [String, Object, Function],
    default: ''
  },
  compact: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['action']);

const colorClasses = computed(() => {
  switch (props.color) {
    case 'violet':
      return {
        bgCircle: 'bg-violet-100 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400',
        button: 'bg-violet-600 hover:bg-violet-700 text-white'
      };
    case 'emerald':
      return {
        bgCircle: 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400',
        button: 'bg-emerald-600 hover:bg-emerald-700 text-white'
      };
    case 'amber':
      return {
        bgCircle: 'bg-amber-100 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400',
        button: 'bg-amber-500 hover:bg-amber-600 text-white'
      };
    case 'blue':
      return {
        bgCircle: 'bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400',
        button: 'bg-blue-600 hover:bg-blue-700 text-white'
      };
    case 'rose':
      return {
        bgCircle: 'bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400',
        button: 'bg-rose-600 hover:bg-rose-700 text-white'
      };
    case 'indigo':
      return {
        bgCircle: 'bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400',
        button: 'bg-indigo-600 hover:bg-indigo-700 text-white'
      };
    case 'gray':
    default:
      return {
        bgCircle: 'bg-slate-100 dark:bg-slate-700/60 text-slate-400 dark:text-slate-400',
        button: 'bg-slate-800 hover:bg-slate-900 text-white'
      };
  }
});
</script>

<template>
  <div :class="[
    'w-full text-center rounded-3xl border border-slate-200/80 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/30 transition-all',
    compact ? 'p-6' : 'p-8 sm:p-10'
  ]">
    <!-- Circle Icon Box -->
    <div :class="[
      'rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-inner transition-transform duration-200 hover:scale-105',
      compact ? 'w-12 h-12' : 'w-14 h-14',
      colorClasses.bgCircle
    ]">
      <component v-if="typeof icon !== 'string'" :is="icon" :class="compact ? 'w-6 h-6' : 'w-7 h-7'" />
      <i v-else :class="[icon, compact ? 'text-xl' : 'text-2xl']"></i>
    </div>

    <!-- Title -->
    <h3
      :class="['font-extrabold text-slate-800 dark:text-slate-100 tracking-tight', compact ? 'text-xs' : 'text-sm sm:text-base']">
      <slot name="title">{{ title }}</slot>
    </h3>

    <!-- Description -->
    <div v-if="description || $slots.default"
      :class="['text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed mt-1.5', compact ? 'text-[11px]' : 'text-xs']">
      <slot>{{ description }}</slot>
    </div>

    <!-- Action Button / Slot -->
    <div v-if="actionLabel || $slots.action" class="mt-4 flex justify-center">
      <slot name="action">
        <button @click="emit('action')" :class="[
          'px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-2',
          colorClasses.button
        ]">
          <component v-if="actionIcon && typeof actionIcon !== 'string'" :is="actionIcon" class="w-4 h-4" />
          <i v-else-if="actionIcon" :class="actionIcon"></i>
          <span>{{ actionLabel }}</span>
        </button>
      </slot>
    </div>
  </div>
</template>
