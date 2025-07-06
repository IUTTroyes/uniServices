<script setup>
import { Form, Field, ErrorMessage, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';

import { ref, computed, watch } from 'vue';

const props = defineProps({
  formConfig: Object,
  formOptions: Object,
  initialValues: Object,
});

const emit = defineEmits([
  'submit',
  'autosave',
  'step-change',
  'file-upload',
  'before-step-change',
  'before-submit',
]);

const isSubmitting = ref(false);

const fields = computed(() => props.formConfig.fields);

const defaultOptions = {
  hasCancelButton: false,
  textCancelButton: 'Annuler',
  hasValidButton: true,
  textValidButton: 'Valider',
  autosave: false,
  autosaveUrl: '',
  steps: [],
  readonly: false,
  buttons: {},
};

const options = computed(() => {
  return { ...defaultOptions, ...(props.formOptions || {}) };
});

const validationSchema = computed(() => {
  const flatSchema = {};
    fields.value.forEach((field) => {
      let validator = yup.mixed().nullable();
      if (field.required) {
        validator = validator.required('Champ obligatoire');
      }
      if (field.type === 'email') {
        validator = validator.email('Email invalide');
      }
      console.log('validator 2', validator);
      flatSchema[field.name] = validator;
    });
  return yup.object(flatSchema);
});

const { handleSubmit, setFieldValue, values: formValues, errors } = useForm({
  initialValues: props.initialValues,
  validationSchema,
});

const onSubmit = handleSubmit(async (values) => {
  if (options.value.onBeforeSubmit) {
    await options.value.onBeforeSubmit(values);
  }
  emit('submit', values);
});

const handleFileUpload = (event, fieldName) => {
  const file = event.files?.[0];
  if (file) {
    setFieldValue(fieldName, file);
    emit('file-upload', { fieldName, file });
  }
};

// Autosave watch
if (options.value.autosave) {
  watch(formValues, async (newValues) => {
    emit('autosave', newValues);
    if (options.value.autosaveUrl) {
      await fetch(options.value.autosaveUrl, {
        method: 'POST',
        body: JSON.stringify(newValues),
        headers: { 'Content-Type': 'application/json' },
      });
    }
  }, { deep: true });
}

// Dynamic options loading
watch(fields, async () => {
      for (const field of fields.value) {
        if (typeof field.options === 'function') {
          field.options = await field.options();
        }


  }
}, { immediate: true });

</script>

<template>
   <Form :validation-schema="validationSchema" @submit="onSubmit">
          <Card class="p-mb-4 no-border-card">
            <template #title v-if="formConfig.title">
              {{ formConfig.title }}
            </template>
            <template #content>
              {{ validationSchema }}<br>
              {{ errors }}<br>
              {{ formValues }}
              <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                <template v-for="field in fields" :key="field.name">
                  <div
                      :class="field.gridClass || ''"
                  >
                    <label :for="field.name">{{ field.label }}</label>

                    <!-- InputText -->
                    <Field :name="field.name" v-slot="{ field: fieldProps, meta }"
                           v-if="field.type === 'text' || field.type === 'email'"
                           v-model="initialValues[field.name]">
                      <InputText
                          v-bind="fieldProps"
                          :id="field.name"
                          :type="field.type"
                          :disabled="options.readonly"
                          class="p-inputtext p-component w-full"
                          :invalid="!meta.valid"
                          :placeholder="field.placeholder"
                      />
                    </Field>

                    <!-- Select -->
                    <Field
                        v-if="field.type === 'select'"
                        :name="field.name"
                        v-slot="{ field: dropdownField }"
                    >
                      <Select
                          v-bind="dropdownField"
                          :options="field.options"
                          :value="formValues[field.name]"
                          @change="e => setFieldValue(field.name, e.value?.toString?.() ?? '')"
                          optionLabel="label"
                          optionValue="value"
                          :disabled="options.readonly"
                          placeholder="Sélectionner..."
                          class="w-full"
                      />
                    </Field>

                    <!-- Calendar -->
                    <Field
                        v-if="field.type === 'calendar'"
                        :name="field.name"
                        v-slot="{ field: calField }"
                    >
                      <Calendar
                          v-bind="calField"
                          dateFormat="yy-mm-dd"
                          showIcon
                          class="w-full"
                          :disabled="options.readonly"
                      />
                    </Field>

                    <!-- FileUpload -->
                    <Field
                        v-if="field.type === 'file'"
                        :name="field.name"
                        v-slot="{ field: fileField, meta }"
                    >
                      <FileUpload
                          name="file[]"
                          customUpload
                          :auto="true"
                          :multiple="false"
                          @uploader="(event) => handleFileUpload(event, field.name)"
                          mode="basic"
                          chooseLabel="Choisir un fichier"
                      />
                    </Field>

                    <!-- Errors -->
                    <ErrorMessage :name="field.name">
                      <template #default="{ message }">
                        <small class="p-error">{{ message }}</small>
                      </template>
                    </ErrorMessage>
                  </div>
                </template>
              </div>
            </template>
            <template #footer>
              <div class="flex gap-4 mt-1">
                <Button :label="options.textCancelButton" severity="secondary" outlined class="mt-4 w-full"
                        v-if="options.hasCancelButton"
                        @click="$emit('cancel')"
                />
                <Button
                    type="submit"
                    :label="options.textValidButton"
                    :disabled="isSubmitting"
                    v-if="options.hasValidButton"
                    class="p-button-primary mt-4 w-full"
                />
              </div>
            </template>
          </Card>
    </Form>
</template>
