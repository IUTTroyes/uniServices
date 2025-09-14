<script setup>
import { computed, ref, watch } from 'vue';
import { validateField, validationRules } from '@components';
import Message from 'primevue/message';

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
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
  }
});

const emit = defineEmits(['update:modelValue', 'validation']);

// Process rules prop to get actual validation rules
const processedRules = computed(() => {
  if (!props.rules) return null;

  // If rules is a string, it's a predefined rule name
  if (typeof props.rules === 'string') {
    const ruleName = props.rules;
    return validationRules[ruleName];
  }

  // If rules is an array of strings, convert to array of rule objects
  if (Array.isArray(props.rules) && props.rules.every(rule => typeof rule === 'string')) {
    return props.rules.map(ruleName => validationRules[ruleName]);
  }

  // Otherwise, it's already a rule object or array of rule objects
  return props.rules;
});

const touched = ref(false);
const dirty = ref(false);
const validationResult = ref({ isValid: true, errorMessage: null });

// Validate the value
const validate = () => {
  if (!processedRules.value) {
    validationResult.value = { isValid: true, errorMessage: null };
    return true;
  }

  validationResult.value = validateField(props.modelValue, processedRules.value);
  emit('validation', validationResult.value);
  return validationResult.value.isValid;
};

// Watch for changes in the model value
watch(() => props.modelValue, () => {
  dirty.value = true;
  if (props.validateOnInput) {
    validate();
  }
});

// Handle blur event
const handleBlur = () => {
  touched.value = true;
  if (props.validateOnBlur) {
    validate();
  }
};

// Computed property to determine if error should be shown
const showError = computed(() => {
  return !validationResult.value.isValid && ((touched.value && props.validateOnBlur) || (dirty.value && props.validateOnInput));
});

// Expose validate method to parent components
defineExpose({ validate });
</script>

<template>
  <div class="form-validator">
    <slot
      :validate="validate"
      :is-valid="validationResult.isValid"
      :error-message="validationResult.errorMessage"
      :handle-blur="handleBlur"
      :show-error="showError"
    ></slot>

    <Message
      v-if="showError"
      severity="error"
      class="mt-1 p-0"
    >
      {{ validationResult.errorMessage }}
    </Message>
  </div>
</template>

<style scoped>
.form-validator {
  display: flex;
  flex-direction: column;
  width: 100%;
}
</style>
