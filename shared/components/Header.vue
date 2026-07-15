<script setup>
import { defineProps, computed } from "vue";

const props = defineProps({
  icon: {
    type: [String, Object, Function],
    default: null
  },
  titre: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  backUrl: {
    type: [String, Object],
    default: null
  },
  showBack: {
    type: Boolean,
    default: true
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
  <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
    <div>
      <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-3 mb-0!">
        <span v-if="icon" :class="[
          colorClasses ? 'w-10 h-10 rounded-xl flex items-center justify-center border transition-all shrink-0' : '',
          colorClasses ? `${colorClasses.bg} ${colorClasses.text} ${colorClasses.border}` : '',
          iconBgClass
        ]">
          <!-- Support PrimeIcons string and Heroicons component -->
          <i v-if="typeof icon === 'string'" :class="[icon, colorClasses ? 'text-lg' : 'text-primary-500 text-2xl!', colorClasses ? '' : iconClass]" />
          <component v-else :is="icon" :class="[colorClasses ? 'w-5 h-5' : 'w-7 h-7 text-primary-500', colorClasses ? '' : iconClass]" />
        </span>
        <span>{{ props.titre }}</span>
      </h1>
      <p class="text-gray-600 dark:text-gray-400 mt-1" :class="[icon && colorClasses ? 'pl-[52px]' : '']">
        {{ props.description }}
      </p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <Button v-if="props.showBack" severity="primary" label="Retour" icon="pi pi-arrow-left" @click="props.backUrl ? $router.push(props.backUrl) : $router.go(-1)" />
      <slot name="actions" />
    </div>
  </div>
</template>

<style scoped>

</style>
