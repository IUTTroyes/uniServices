<script setup lang="ts">
import { computed, nextTick } from 'vue';
import FormValidator from './FormValidator.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Dropdown from 'primevue/dropdown';
import { validationRules } from '@components';

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
    default: null
  },
  value: {
    type: [String, Number, Boolean, Object],
    default: null
  },
  rules: {
    type: [Array, Object, String],
    default: null
  },
  validateOnInput: {
    type: Boolean,
    default: false
  },
  validateOnBlur: {
    type: Boolean,
    default: true
  },
  name: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  inputClass: {
    type: String,
    default: 'w-full'
  },
  helpText: {
    type: String,
    default: ''
  },
  feedback: {
    type: Boolean,
    default: false
  },
  toggleMask: {
    type: Boolean,
    default: true
  },
  options : {
    type: Array,
    default: () => []
  },
  filter: {
    type: Boolean,
    default: false
  },
  inputId: {
    type: String,
    default: null
  },
  min: {
    type: [Number, String],
    default: null
  },
  max: {
    type: [Number, String],
    default: null
  },
  minfractiondigits: {
    type: Number,
    default: null
  },
  maxfractiondigits: {
    type: Number,
    default: null
  }
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void;
  (e: 'blur', event?: Event): void;
  (e: 'validation', result: any): void;
}>();

const updateValue = (eventOrValue: any) => {
  const value =
      eventOrValue && typeof eventOrValue === 'object' && 'target' in eventOrValue
          ? eventOrValue.target.value
          : eventOrValue;
  emit('update:modelValue', value);
};

const updateModelValue = (value: any) => {
  // utilisé par InputNumber/Select qui émettent la valeur directement
  updateValue(value);
};

const handleBlur = (event?: Event) => {
  emit('blur', event);
};

const onValidation = (result) => {
  emit('validation', result);
};

// Wrapper utilisé dans le template pour attendre que le modelValue soit mis à jour
const onBlurModelValue = async (event: Event, handleBlurFn: Function) => {
  await nextTick();
  handleBlurFn(event);
};
</script>

<template>
  <div class="validated-input">
    <label v-if="label" :for="name" class="block mb-1">
      {{ label }}
      <span v-if="rules && (rules === 'required' || (Array.isArray(rules) && (rules.includes('required') || rules.includes(validationRules.required))))" class="text-red-500">*</span>
    </label>
    <FormValidator
        :model-value="modelValue"
        :rules="rules"
        :validate-on-input="validateOnInput"
        :validate-on-blur="validateOnBlur"
        :name="name"
        @validation="onValidation"
    >
      <template #default="{ handleBlur, showError }">
        <InputText
            v-if="type === 'text'"
            :id="name"
            :name="name"
            :value="modelValue"
            :placeholder="placeholder"
            :type="type"
            :class="[inputClass, { 'p-invalid': showError }]"
            @input="updateValue"
            @blur="handleBlur"
        />

        <InputNumber
            v-if="type === 'number'"
            :id="inputId || name"
            :name="name"
            :placeholder="placeholder"
            :modelValue="modelValue"
            :class="[inputClass]"
            @update:modelValue="updateModelValue"
            @blur="e => onBlurModelValue(e, handleBlur)"
            :min="min"
            :max="max"
            :min-fraction-digits="minfractiondigits"
            :max-fraction-digits="maxfractiondigits"
        />

        <Password
            v-else-if="type === 'password'"
            :inputId="name"
            :name="name"
            :placeholder="placeholder"
            :class="[inputClass, { 'p-invalid': showError }, 'pwd']"
            :feedback="feedback"
            :toggleMask="toggleMask"
            :modelValue="modelValue"
            @input="updateValue"
            @blur="handleBlur"
        />

        <Select
            v-else-if="type === 'select'"
            :id="name"
            :name="name"
            :options="options"
            :modelValue="modelValue"
            :placeholder="placeholder"
            :class="[inputClass, { 'p-invalid': showError }]"
            optionLabel="label"
            optionValue="value"
            @update:modelValue="updateModelValue"
            @blur="e => onBlurModelValue(e, handleBlur)"
            :filter="filter"
        />

        <MultiSelect
            v-else-if="type === 'multiselect'"
            :id="name"
            :name="name"
            :options="options"
            :modelValue="modelValue"
            :placeholder="placeholder"
            :class="[inputClass, { 'p-invalid': showError }]"
            optionLabel="label"
            optionValue="value"
            @update:modelValue="updateModelValue"
            @blur="e => onBlurModelValue(e, handleBlur)"
            :filter="filter"
        />

        <DatePicker
          v-else-if="type === 'date'"
          :id="name"
          :name="name"
          :modelValue="modelValue"
          :placeholder="placeholder"
          :class="[inputClass, { 'p-invalid': showError }]"
          @update:modelValue="updateModelValue"
          dateFormat="dd/mm/yy"
          @blur="e => onBlurModelValue(e, handleBlur)"
        />

        <Textarea
          v-else-if="type === 'textarea'"
          :id="name"
          :name="name"
          :value="modelValue"
          :placeholder="placeholder"
          :class="[inputClass, { 'p-invalid': showError }]"
          @input="updateValue"
          @blur="handleBlur"
        />

        <RadioButton
            v-else-if="type === 'radio'"
            :inputId="`${name}-${value}`"
            :name="name"
            :modelValue="modelValue"
            :value="value"
            :class="[inputClass, { 'p-invalid': showError }]"
            @update:modelValue="updateModelValue"
            @change="e => onBlurModelValue(e, handleBlur)"
        />

        <small v-if="helpText && !showError" class="text-sm text-muted-color mt-1">{{ helpText }}</small>
      </template>
    </FormValidator>
  </div>
</template>

<style scoped>
.validated-input {
  margin-bottom: 1rem;
}
</style>
