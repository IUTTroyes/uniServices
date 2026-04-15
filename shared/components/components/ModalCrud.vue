<!-- ModalCrud.vue -->
<script setup>
import {ref, watch, computed, onMounted, useTemplateRef} from 'vue'
import { Button, Dialog, InputText } from 'primevue'
import DynamicFormField from '@components/components/Forms-OldVersion/DynamicFormField.vue'
import DynamicFormLabel from '@components/components/Forms-OldVersion/DynamicFormLabel.vue'
import DynamicFormMessage from '@components/components/Forms-OldVersion/DynamicFormMessage.vue'
import DynamicFormControl from '@components/components/Forms-OldVersion/DynamicFormControl.vue'

const props = defineProps({
  visible: Boolean,
  data: Object,
  params: Object,
  fields: {
    type: [Object, Function],
    required: true,
    validator: value => typeof value === 'object' || typeof value === 'function',
  },
  serviceMethod: Function,
  onClose: Function,
  onSuccess: Function,
  width: {
    type: Number,
    default: 450
  },
  mode: {
    type: String,
    required: true,
    validator: value => ['new', 'edit'].includes(value)
  }
})

const resolvedFields = ref(null)

const myForm = ref(null)
const initialValues = computed(() => {
  if (props.mode === 'edit') {
    return { ...props.data }
  } else {
    const values = {}
    for (const [key, field] of Object.entries(resolvedFields.value)) {
      values[key] = field.defaultValue || ''
    }
    return values
  }
})
const isVisible = ref(props.visible)
const localData = ref({ ...props.data })
const submitted = ref(false)



watch(() => props.data, (newData) => {
  localData.value = { ...newData }
})

const triggerSubmit = () => {
  if (myForm.value) {
    myForm.value.$el.dispatchEvent(new Event('submit', { cancelable: true }))
  }
}

watch(() => props.visible, (newVisible) => {
  isVisible.value = newVisible
})

const handleSubmit = async ({ states, valid, values }) => {
  console.log(states)
  console.log(values)
  console.log(valid)
  if (valid) {
    submitted.value = true
    try {
      //transformation des données en objet compatible pour l'api
      // objet json avec les champs de l'api et leur valeur uniquement
      const data = {}
      for (const [key, value] of Object.entries(states)) {
        if (resolvedFields.value[key]) {
          if (resolvedFields.value[key].typeData === 'int') {
            data[key] = parseInt(value.value)
          } else if (resolvedFields.value[key].typeData === 'float') {
            data[key] = parseFloat(value.value)
          } else {
            data[key] = value.value
          }
        }
      }

      console.log(data)

      await props.serviceMethod(data)
      props.onSuccess()
      props.onClose()
    } catch (error) {
      console.error(error)
    } finally {
      submitted.value = false
    }
  }
}

const form = useTemplateRef('form')

const resolver = ({ values }) => {
  const errors = [];

  Object.keys(resolvedFields.value).forEach(
    (field) => {
      errors[field] = []

      if (!values[field] && form.value.states[field].touched) {
        errors[field].push({ type: 'required', message: resolvedFields.value[field].label+'is required.' })
      }

      if (values[field]?.length < 3 && form.value.states[field].touched) {
        errors[field].push({ type: 'minimum', message: 'Username must be at least 3 characters long.' })
      }
    }
  )

  // if (!values.libelle) {
  //   errors.libelle.push({ type: 'required', message: 'Username is required.' })
  // }
  //
  // if (values.libelle?.length < 3) {
  //   errors.libelle.push({ type: 'minimum', message: 'Username must be at least 3 characters long.' })
  // }

  return {
    errors
  }
}

onMounted(() => {
  if (typeof props.fields === 'function') {
    console.log(props.params)
    resolvedFields.value = props.fields(props.params)
  } else {
    resolvedFields.value = props.fields
  }
})
</script>

<template>
  <Dialog v-model:visible="isVisible" :style="{ width: `${width}px` }" header="Details" :modal="true">
    <Form
        v-slot="$form"
        ref="form"
        :initialValues="initialValues"
        :resolver
        :validateOnValueUpdate="false"
        :validateOnBlur="false"
        @submit="handleSubmit">
      <div class="flex flex-col gap-6">
        <slot v-bind="$form">
          <template v-for="({ groupId, label, messages, ...rest }, name) in resolvedFields" :key="name">
            <DynamicFormField :groupId="groupId" :name="name">
              <DynamicFormLabel>{{ label }}</DynamicFormLabel>
              <DynamicFormControl v-bind="rest" />
              <DynamicFormMessage v-for="(message, index) in messages || [{}]" :key="index" v-bind="message"/>
            </DynamicFormField>
          </template>
        </slot>
      </div>
      {{ $form }}
    </Form>
    <template #footer>
      <Button label="Annuler" icon="pi pi-times" text @click="onClose"/>
      <Button label="Sauvegarder" @click="triggerSubmit" icon="pi pi-check"/>

    </template>

  </Dialog>
</template>

