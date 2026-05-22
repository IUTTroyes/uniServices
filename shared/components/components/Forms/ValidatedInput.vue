<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import FormValidator from './FormValidator.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Dropdown from 'primevue/dropdown';
import AutoComplete from 'primevue/autocomplete';
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
  },
  disabled: {
    type: Boolean,
    default: false
  },
  selectionMode: {
    type: String,
    default: 'single' // or 'range', 'multiple'
  },
  manualInput: {
    type: Boolean,
    default: false
  },
  minDate: {
    type: Date,
    default: null
  },
  maxDate: {
    type: Date,
    default: null
  },
  showClear: {
    type: Boolean,
    default: false
  },
  minQueryLength: {
    type: Number,
    default: 3
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

const addressSuggestions = ref([]);
const addressQuery = ref('');

const createEmptyAddress = () => ({
  adresse: '',
  complement1: '',
  complement2: '',
  ville: '',
  codePostal: '',
  pays: 'France'
});

const buildAddressObject = (feature: any) => {
  const label = feature?.properties?.label ?? '';
  const adresse = label.includes(',') ? label.split(',')[0] : (feature?.properties?.name ?? '');

  return {
    ...createEmptyAddress(),
    adresse,
    ville: feature?.properties?.city ?? '',
    codePostal: feature?.properties?.postcode ?? ''
  };
};

const mergeAddress = (adresse: string) => ({
  ...createEmptyAddress(),
  ...(typeof props.modelValue === 'object' && props.modelValue ? props.modelValue : {}),
  adresse
});

const normalizeAddressValue = (value: any) => {
  if (!value) {
    return createEmptyAddress();
  }

  if (typeof value === 'string') {
    return mergeAddress(value);
  }

  return {
    ...createEmptyAddress(),
    ...value
  };
};

watch(() => props.modelValue, (newValue: any) => {
  if (props.type !== 'address') return;

  if (typeof newValue === 'string') {
    addressQuery.value = newValue;
    return;
  }

  if (newValue && typeof newValue === 'object') {
    addressQuery.value = newValue.adresse ?? '';
    return;
  }

  addressQuery.value = '';
}, { immediate: true });

const handleSearchAddress = async (event: any) => {
  const query = (event.query || '').trim();

  if (query.length < props.minQueryLength) {
    addressSuggestions.value = [];
    return;
  }

  try {
    const response = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`);
    const data = await response.json();
    addressSuggestions.value = (data.features || []).map((feature: any) => ({
      label: feature.properties?.label,
      value: buildAddressObject(feature)
    }));
  } catch (error) {
    addressSuggestions.value = [];
    console.error('Error searching address:', error);
  }
};

const handleSelectAddress = (event: any) => {
  const value = normalizeAddressValue(event.value?.value ?? event.value?.label ?? '');
  addressQuery.value = value.adresse ?? '';
  emit('update:modelValue', value);
};

const handleInputAddress = (value: any) => {
  if (typeof value === 'string') {
    addressQuery.value = value;
    emit('update:modelValue', mergeAddress(value));
    return;
  }

  const normalizedValue = normalizeAddressValue(value?.value ?? value?.label ?? value);
  addressQuery.value = normalizedValue.adresse ?? '';
  emit('update:modelValue', normalizedValue);
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
            :disabled="disabled"
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
            :show-clear="showClear"
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
            :disabled="disabled"
        />

        <MultiSelect
            v-else-if="type === 'multiselect'"
            :show-clear="showClear"
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
          showIcon
          :showButtonBar="true"
          :id="name"
          :name="name"
          :modelValue="modelValue"
          :placeholder="placeholder"
          :class="[inputClass, { 'p-invalid': showError }]"
          @update:modelValue="updateModelValue"
          dateFormat="dd/mm/yy"
          @blur="e => onBlurModelValue(e, handleBlur)"
          :selectionMode="selectionMode"
          :manualInput="manualInput"
          :minDate="minDate"
          :maxDate="maxDate"
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

        <AutoComplete
            v-else-if="type === 'address'"
            :id="name"
            v-model="addressQuery"
            :suggestions="addressSuggestions"
            optionLabel="label"
            :placeholder="placeholder"
            :class="[inputClass, { 'p-invalid': showError }]"
            fluid
            @complete="handleSearchAddress"
            @item-select="handleSelectAddress"
            @update:modelValue="handleInputAddress"
            @blur="event => onBlurModelValue(event, handleBlur)"
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

        <input
            v-else-if="type === 'file'"
            :id="name"
            :name="name"
            type="file"
            :class="[inputClass, 'p-inputtext', { 'p-invalid': showError }]"
            @change="e => {
              const file = e.target.files[0];
              updateValue(file);
              handleBlur(e);
            }"
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
