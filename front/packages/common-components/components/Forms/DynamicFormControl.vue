<script setup>
import * as PrimeVue from 'primevue'
import { computed, inject, onBeforeMount, onMounted, ref } from 'vue'

const props = defineProps({
  as: {
    type: String,
    default: 'InputText'
  },
  fetchData: {
    type: Function,
    default: null
  },
  optionLabel: {
    type: String,
    default: 'libelle'
  },
  optionId: {
    type: String,
    default: '@id'
  },
  schema: null,
  defaultValue: {
    default: ''
  },
  layout: {
    type: String,
    default: 'row', // 'row' or 'col'
    validator: value => ['row', 'col'].includes(value)
  },
  dateFormat: {
    type: String,
    default: 'dd/mm/yy'
  }
})

const fetchedData = ref([])
const $fcDynamicForm = inject('$fcDynamicForm', undefined)
const $fcDynamicFormField = inject('$fcDynamicFormField', undefined)

const id = computed(() => $fcDynamicFormField?.groupId)
const name = computed(() => $fcDynamicFormField?.name)
const component = computed(() => PrimeVue[props.as] ?? props.as)
const options = computed(() => fetchedData.value)

$fcDynamicForm?.addField(name.value, props.schema, props.defaultValue)

onBeforeMount(async () => {
  if (props.fetchData !== null) {
    const data = await props.fetchData()
    fetchedData.value = Array.isArray(data['member']) ? data['member'] : []
    console.log(fetchedData.value)
    console.log(props.optionLabel)
    console.log(props.as)
  }
})

const mergedProps = computed(() => {
  if (props.as === 'Select' && fetchedData.value) {
    return {
      options: fetchedData.value,
      optionLabel: props.optionLabel,
      optionValue: props.optionId
    }
  } else if (props.as === 'DatePicker') {
    return {
      dateFormat: props.dateFormat,
    }
  } else {
    return {}
  }
})
</script>

<template>
  <div v-if="props.as === 'RadioButton'" :class="['options-container', layout]">
    <div v-for="option in options" :key="option[props.optionId]" :class="['option-item', layout]">
      <RadioButton
          :inputId="option[props.optionId]"
          :name="name" :value="option[props.optionId]"
          v-model="fetchedData.value"/>
      <label :for="option[props.optionId]"> {{ option[props.optionLabel] }}</label>
    </div>
  </div>
  <div v-else-if="props.as === 'Checkbox'" :class="['options-container', layout]">
    <div v-for="option in options" :key="option[props.optionId]" :class="['option-item', layout]">
      <Checkbox :inputId="option[props.optionId]"
                :name="name"
                :value="option[props.optionId]"
                v-model="fetchedData.value"/>
      <label :for="option[props.optionId]" class="ps-2">{{ option[props.optionLabel] }}</label>
    </div>
  </div>
  <component v-else :is="component" :id :name="name" class="w-full"
             v-bind="mergedProps"
  />
</template>

<style scoped>
.options-container.row {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
}

.options-container.col {
  display: flex;
  flex-direction: column;
}

.option-item.row {
  margin-right: 1rem;
}

.option-item.col {
  margin-bottom: 1rem;
}
</style>
