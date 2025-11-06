<script setup>
import { computed } from 'vue';
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
  // valeur de l'option (utilisée pour les radios)
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
  // Password component specific props
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
  }
});

const emit = defineEmits(['update:modelValue', 'validation']);

const updateValue = (event) => {
  emit('update:modelValue', event.target.value);
};

const updateModelValue = (value) => {
  emit('update:modelValue', value);
};

const onValidation = (result) => {
  emit('validation', result);
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

        <Password
            v-else-if="type === 'password'"
            :inputId="name"
            :name="name"
            :placeholder="placeholder"
            :class="[inputClass, { 'p-invalid': showError }, 'pwd']"
            :feedback="feedback"
            :toggleMask="toggleMask"
            :modelValue="modelValue"
            @update:modelValue="updateModelValue"
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
            @blur="handleBlur"
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
            @blur="handleBlur"
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
          @blur="handleBlur"
        />

        <InputNumber
          v-else-if="type === 'number'"
          :id="name"
          :name="name"
          :modelValue="modelValue"
          :placeholder="placeholder"
          :class="[inputClass, { 'p-invalid': showError }]"
          @update:modelValue="updateModelValue"
          @blur="handleBlur"
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
            @change="handleBlur"
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
