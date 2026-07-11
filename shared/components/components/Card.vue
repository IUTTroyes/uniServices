<script setup>
defineProps({
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
})
</script>

<template>
  <div class="card">
    <div v-if="title || $slots.header" class="card-header flex justify-between items-center gap-4">
      <slot name="header">
        <div class="flex flex-col min-w-0">
          <div class="flex items-center gap-3">
            <span v-if="icon" :class="['flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700/50', iconBgClass]">
              <!-- Support PrimeIcons string and Heroicons component -->
              <i v-if="typeof icon === 'string'" :class="[icon, 'text-sm', iconClass]" />
              <component v-else :is="icon" :class="['w-4 h-4', iconClass]" />
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
