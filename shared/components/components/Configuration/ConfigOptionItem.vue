<script setup>
import { computed } from 'vue';
import ToggleSwitch from 'primevue/toggleswitch';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'switch',
    validator: (v) => ['switch', 'select', 'number', 'text'].includes(v)
  },
  modelValue: {
    type: [Boolean, String, Number, Object, null],
    default: null
  },
  options: {
    type: Array,
    default: () => []
  },
  optionLabel: {
    type: String,
    default: 'label'
  },
  optionValue: {
    type: String,
    default: 'value'
  },
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  icon: {
    type: String,
    default: ''
  },
  iconColorClass: {
    type: String,
    default: 'text-primary'
  },
  iconBgClass: {
    type: String,
    default: 'bg-primary/10'
  },
  badge: {
    type: String,
    default: ''
  },
  badgeSeverity: {
    type: String,
    default: 'info'
  },
  min: {
    type: Number,
    default: null
  },
  max: {
    type: Number,
    default: null
  },
  step: {
    type: Number,
    default: 1
  },
  suffix: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Sélectionner...'
  },
  showButtons: {
    type: Boolean,
    default: false
  },
  controlClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const localValue = computed({
  get: () => props.modelValue,
  set: (val) => {
    emit('update:modelValue', val);
    emit('change', val);
  }
});
</script>

<template>
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-3.5 px-3 rounded-xl transition-all duration-200 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 max-w-full">
    <div class="flex items-start sm:items-center gap-3.5 flex-1 min-w-0 pr-2">
      <div
        v-if="icon"
        :class="[
          'w-9 h-9 rounded-lg flex items-center justify-center shrink-0 border border-slate-200/50 dark:border-slate-700/50 transition-colors',
          iconBgClass,
          iconColorClass
        ]"
      >
        <i :class="[icon, 'text-sm']" />
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-sm font-semibold text-slate-900 dark:text-white leading-snug">
            {{ title }}
          </span>
          <Tag v-if="badge" :value="badge" :severity="badgeSeverity" class="text-[10px] font-bold px-2 py-0.5 tracking-wide uppercase" />
        </div>
        <p v-if="description" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
          {{ description }}
        </p>
      </div>
    </div>

    <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
      <ProgressSpinner
        v-if="loading"
        style="width: 18px; height: 18px"
        strokeWidth="4"
        class="text-primary shrink-0"
      />

      <!-- Type Switch -->
      <ToggleSwitch
        v-if="type === 'switch'"
        v-model="localValue"
        :disabled="disabled || loading"
      />

      <!-- Type Select -->
      <Select
        v-else-if="type === 'select'"
        v-model="localValue"
        :options="options"
        :optionLabel="optionLabel"
        :optionValue="optionValue"
        :placeholder="placeholder"
        :disabled="disabled || loading"
        class="w-full sm:w-64 text-sm"
        :class="controlClass"
      />

      <!-- Type Number -->
      <InputNumber
        v-else-if="type === 'number'"
        v-model="localValue"
        :min="min"
        :max="max"
        :step="step"
        :suffix="suffix ? ` ${suffix}` : ''"
        :disabled="disabled || loading"
        :show-buttons="showButtons"
        class="w-32 max-w-[140px] text-sm"
        :inputClass="'w-full min-w-0 text-center sm:text-right text-sm py-1.5 px-2.5'"
        :class="controlClass"
      />

      <!-- Type Text -->
      <InputText
        v-else-if="type === 'text'"
        v-model="localValue"
        :placeholder="placeholder"
        :disabled="disabled || loading"
        class="w-full sm:w-64 text-sm"
        :class="controlClass"
      />
    </div>
  </div>
</template>

<style scoped>
</style>
