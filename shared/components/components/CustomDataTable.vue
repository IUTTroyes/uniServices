<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { DataTable, Column, Button } from 'primevue'
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue'
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue'
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue'
import { FilterMatchMode } from '@primevue/core/api'
import api from '@helpers/axios.js'

const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  actionAdd: {
    type: Object,
    required: false,
    default: () => ({})
  },
  actions: {
    type: Array,
    required: false,
    default: () => []
  },
  extraActions: {
    type: Array,
    required: false,
    default: () => []
  },
  apiEndpoint: {
    type: String,
    required: true
  },
  refreshKey: {
    type: Number,
    required: false,
    default: 0
  },
  searchParameter: {
    type: String,
    required: false,
    default: null
  }
})

const data = ref([])
const totalRecords = ref(0)
const loading = ref(true)
const page = ref(0)
const rowOptions = [30, 60, 120]
const limit = ref(rowOptions[0])
const offset = computed(() => Number(limit.value * page.value))
const sortField = ref(null)
const sortOrder = ref(null)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
})

const fetchData = async () => {
  loading.value = true
  const endpoint = props.apiEndpoint.startsWith('/') || props.apiEndpoint.startsWith('http')
    ? props.apiEndpoint
    : '/' + props.apiEndpoint
  
  const params = {
    limit: limit.value,
    offset: offset.value,
    sortField: sortField.value,
    sortOrder: sortOrder.value
  }

  if (props.searchParameter && filters.value.global.value) {
    params[props.searchParameter] = filters.value.global.value
  }
  
  const response = await api.get(endpoint, { params })
  totalRecords.value = response.data.totalItems ?? response.data['hydra:totalItems'] ?? (Array.isArray(response.data) ? response.data.length : 0)
  const rawMember = response.data.member ?? response.data['hydra:member'] ?? (Array.isArray(response.data) ? response.data : [])
  data.value = await rawMember
  loading.value = false
}

onMounted(fetchData)

watch(() => props.refreshKey, async (newValue, oldValue) => {
  await fetchData()
});

let searchTimeout = null
watch(() => filters.value.global.value, (newVal) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    page.value = 0
    await fetchData()
  }, 400)
})


const onPageChange = async (event) => {
  page.value = event.page
  await fetchData()
}

const onSortChange = async (event) => {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  await fetchData()
}

function getNestedValue(obj, path) {
  return path.split('.').reduce((acc, part) => acc && acc[part], obj);
}
</script>

<template>
  <DataTable :value="data" lazy stripedRows paginator :first="offset" :rows="limit" :rowsPerPageOptions="rowOptions"
    :totalRecords="totalRecords" dataKey="id" :loading="loading" v-model:filterModel="filters" @page="onPageChange"
    @sort="onSortChange" @update:rows="limit = $event" :globalFilterFields="columns.map(col => col.field)">
    <template #header>
      <div class="flex justify-end">
        <Button label="Ajouter" icon="pi pi-plus" class="mr-2" @click="actionAdd.handler()" v-if="actionAdd" />
        <IconField>
          <InputIcon>
            <i class="pi pi-search" />
          </InputIcon>
          <InputText v-model="filters.global.value" placeholder="Rechercher..." />
        </IconField>
      </div>
    </template>
    <template #empty> Aucun enregistrement trouvé.</template>
    <template #loading> Chargement des données. Patientez.</template>
    <Column v-for="col in columns" :key="col.field" :field="col.field" :header="col.header" :style="col.style"
      :sortable="col.sortable">
      <template #body="slotProps" v-if="col.type === undefined">
        {{ getNestedValue(slotProps.data, col.field) }}
      </template>
      <template #body="slotProps" v-else-if="col.type === 'date'">
        {{ new Date(getNestedValue(slotProps.data, col.field)).toLocaleDateString() }}
      </template>
      <template #body="slotProps" v-else-if="col.type === 'boolean'">
        <ToggleSwitch @change="col.handler(slotProps.data)" v-model="slotProps.data[col.field]" />
      </template>
      <!--      <template #filter="slotProps">-->
      <!--        <InputText v-model="slotProps.filterModel.value" type="text" @input="slotProps.filterCallback()" :placeholder="`Filter by ${col.header}`"/>-->
      <!--      </template>-->
    </Column>
    <Column v-if="actions.length" header="Actions" :style="{ minWidth: '10rem' }">
      <template #body="slotProps">
        <template v-for="action in actions">
          <ButtonInfo tooltip="Détails" v-if="action.type === 'show'" @click="action.handler(slotProps.data)" />
          <ButtonEdit v-if="action.type === 'edit'" tooltip="Modifier" @click="action.handler(slotProps.data)" />
          <ButtonDelete v-if="action.type === 'delete'" tooltip="Supprimer"
            @confirm-delete="action.handler(slotProps.data)" />
        </template>
      </template>
    </Column>
    <Column v-if="extraActions.length" header="Actions" :style="{ minWidth: '10rem' }">
      <template #body="slotProps">
        <Button v-for="action in actions" :key="action.label" :label="action.label" :icon="action.icon"
          @click="action.handler(slotProps.data)" />
      </template>
    </Column>
    <template #footer> {{ totalRecords }} résultat(s).</template>
  </DataTable>
</template>

<style scoped>
/* Ajoutez vos styles ici */
</style>
