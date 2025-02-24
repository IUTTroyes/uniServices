<!-- ModalCrud.vue -->
<script setup>
import { ref, watch, computed } from 'vue'
import { Button, Dialog, InputText } from 'primevue'
import DynamicFormField from '@components/components/Forms/DynamicFormField.vue'
import DynamicFormLabel from '@components/components/Forms/DynamicFormLabel.vue'
import DynamicFormMessage from '@components/components/Forms/DynamicFormMessage.vue'
import DynamicFormControl from '@components/components/Forms/DynamicFormControl.vue'

const props = defineProps({
  visible: Boolean,
  data: Object,
  fields: Object,
  serviceMethod: Function,
  onClose: Function,
  onSuccess: Function,
  mode: {
    type: String,
    required: true,
    validator: value => ['new', 'edit'].includes(value)
  }
})

console.log(props.fields)

const myForm = ref(null)
const initialValues = computed(() => {
  if (props.mode === 'edit') {
    return { ...props.data }
  } else {
    const values = {}
    for (const [key, field] of Object.entries(props.fields)) {
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
        if (props.fields[key]) {
          if (props.fields[key].typeData === 'int') {
            data[key] = parseInt(value.value)
          } else if (props.fields[key].typeData === 'float') {
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

const resolver = ({ values }) => {
  console.log('resolver')
  const errors = { sigle: [] }

  if (!values.sigle) {
    errors.sigle.push({ type: 'required', message: 'Username is required.' })
  }

  if (values.sigle?.length < 3) {
    errors.sigle.push({ type: 'minimum', message: 'Username must be at least 3 characters long.' })
  }

  return {
    errors
  }
}
</script>

<template>
  <Dialog v-model:visible="isVisible" :style="{ width: '450px' }" header="Details" :modal="true">
    <Form
        v-slot="$form"
        ref="myForm"
        :initialValues="initialValues"
        :resolver
        @submit="handleSubmit">
      <div class="flex flex-col gap-6">
        <slot v-bind="$form">
          <template v-for="({ groupId, label, messages, ...rest }, name) in fields" :key="name">
            <DynamicFormField :groupId="groupId" :name="name">
              <DynamicFormLabel>{{ label }}</DynamicFormLabel>
              <DynamicFormControl v-bind="rest"/>
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

