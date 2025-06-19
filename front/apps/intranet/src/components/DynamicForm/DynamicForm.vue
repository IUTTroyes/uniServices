<!--
DynamicForm - Générateur de formulaires dynamiques Vue 3
=======================================================

Composant permettant de générer des formulaires dynamiques à partir d'une configuration JSON.
Utilise VeeValidate 4 + Yup pour la validation et Axios pour les appels HTTP.

Props:
- formConfig: Configuration complète du formulaire (voir types ci-dessous)
- initialData: Données initiales (optionnel)
- id: ID de l'entité pour mode édition (optionnel)

Events:
- submit-success: Émis après soumission réussie
- submit-error: Émis en cas d'erreur
- cancel: Émis lors de l'annulation

Slots:
- header: En-tête du formulaire
- footer: Pied du formulaire
- field-{name}: Rendu personnalisé pour un champ spécifique
-->

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import axios from 'axios'
import { fieldMap, getFieldConfig } from './field-map'

const props = defineProps({
  formConfig: {
    type: Object,
    required: true,
    validator(config) {
      return config.fields && Array.isArray(config.fields) && config.saveUrl
    }
  },
  initialData: {
    type: Object,
    default: () => ({})
  },
  id: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['submit-success', 'submit-error', 'cancel'])

// État local
const formData = ref({ ...props.initialData })
const isSubmitting = ref(false)
const fieldOptions = ref({})

// Validation schema
const validationSchema = computed(() => {
  const schema = {}
  props.formConfig.fields.forEach(field => {
    if (field.rules) {
      let fieldSchema = yup.mixed()
      field.rules.split('|').forEach(rule => {
        const [ruleName, params] = rule.split(':')
        switch (ruleName) {
          case 'required':
            fieldSchema = fieldSchema.required('Ce champ est requis')
            break
          case 'email':
            fieldSchema = fieldSchema.email('Email invalide')
            break
          case 'min':
            fieldSchema = fieldSchema.min(parseInt(params), `Minimum ${params} caractères`)
            break
          case 'max':
            fieldSchema = fieldSchema.max(parseInt(params), `Maximum ${params} caractères`)
            break
        }
      })
      schema[field.name] = fieldSchema
    }
  })
  return yup.object().shape(schema)
})

// Chargement des options pour les champs select
const loadFieldOptions = async (field) => {
  if (field.options?.dataUrl) {
    try {
      const response = await axios.get(field.options.dataUrl)
      fieldOptions.value[field.name] = response.data
    } catch (error) {
      console.error(`Erreur lors du chargement des options pour ${field.name}:`, error)
      fieldOptions.value[field.name] = []
    }
  }
}

// Initialisation
onMounted(async () => {
  // Chargement des options pour tous les champs select
  const optionsPromises = props.formConfig.fields
    .filter(field => field.options?.dataUrl)
    .map(field => loadFieldOptions(field))
  await Promise.all(optionsPromises)
})

// Soumission du formulaire
const onSubmit = async (values) => {
  isSubmitting.value = true
  try {
    const url = props.id 
      ? `${props.formConfig.saveUrl}/${props.id}`
      : props.formConfig.saveUrl
    const method = props.id ? 'put' : 'post'
    
    const response = await axios[method](url, values)
    emit('submit-success', response.data)
  } catch (error) {
    emit('submit-error', error)
  } finally {
    isSubmitting.value = false
  }
}

// Classes CSS pour le layout
const formClasses = computed(() => ({
  'grid gap-6': true,
  'grid-cols-1': props.formConfig.layout === 'one-column',
  'grid-cols-2': props.formConfig.layout === 'two-columns'
}))
</script>

<template>
  <Form
    :validation-schema="validationSchema"
    @submit="onSubmit"
    class="space-y-6"
  >
    <slot name="header" />

    <div :class="formClasses">
      <template v-for="field in formConfig.fields" :key="field.name">
        <div class="space-y-2">
          <label
            :for="field.name"
            class="block text-sm font-medium text-gray-700"
          >
            {{ field.label }}
          </label>

          <!-- Slot personnalisé pour le champ si fourni -->
          <slot
            :name="`field-${field.name}`"
            v-bind="{ field, value: formData[field.name] }"
          >
            <!-- Rendu par défaut basé sur le type -->
            <Field
              :id="field.name"
              :name="field.name"
              v-model="formData[field.name]"
              :type="field.type"
              v-bind="getFieldConfig(field.type).defaultProps"
              :placeholder="field.placeholder"
            >
              <template v-if="field.type === 'select'">
                <option value="">Sélectionnez...</option>
                <option
                  v-for="option in fieldOptions[field.name] || field.options"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </template>
            </Field>
          </slot>

          <ErrorMessage
            :name="field.name"
            class="text-sm text-red-600"
          />
        </div>
      </template>
    </div>

    <div class="flex justify-end space-x-4">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50"
      >
        Annuler
      </button>
      <button
        type="submit"
        :disabled="isSubmitting"
        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
      >
        {{ isSubmitting ? 'Enregistrement...' : 'Enregistrer' }}
      </button>
    </div>

    <slot name="footer" />
  </Form>
</template> 