<script setup>
import { ref, onMounted, computed } from 'vue';
import { DataTable, Column, Button } from 'primevue';
import { FilterMatchMode } from '@primevue/core/api';
import api from '@helpers/axios.js';

const props = defineProps({
  columns: {
    type: Array,
    required: true
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
  }
});

const data = ref([]);
const totalRecords = ref(0);
const loading = ref(true);
const page = ref(0);
const rowOptions = [30, 60, 120];
const limit = ref(rowOptions[0]);
const offset = computed(() => Number(limit.value * page.value));

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const fetchData = async () => {
  console.log('fetching data');
  loading.value = true;
  const response = await api.get(props.apiEndpoint, {
    params: {
      ...filters.value,
      limit: limit.value,
      offset: offset.value
    }
  });
  console.log(response)
  totalRecords.value = response.data.totalItems;
  data.value = await response.data.member;
  loading.value = false;
};

onMounted(fetchData);

const onPageChange = async (event) => {
  page.value = event.page;
  await fetchData();
};
</script>

<template>
  {{ data}}
  <DataTable :value="data"
             lazy
             stripedRows
             paginator
             :first="offset"
             :rows="limit"
             :rowsPerPageOptions="rowOptions"
             :totalRecords="totalRecords"
             dataKey="id"
             filterDisplay="row"
             :loading="loading"
             v-model:filterModel="filters"
             @page="onPageChange"
             @update:rows="limit = $event"
             :globalFilterFields="columns.map(col => col.field)">
    <template #header>
      <div class="flex justify-end">
        <IconField>
          <InputIcon>
            <i class="pi pi-search"/>
          </InputIcon>
          <InputText v-model="filters.global.value" placeholder="Keyword Search"/>
        </IconField>
      </div>
    </template>
    <template #empty> No records found.</template>
    <template #loading> Loading data. Please wait.</template>
    <Column v-for="col in columns" :key="col.field" :field="col.field" :header="col.header" :style="col.style">
      <template #body="slotProps">
        {{ slotProps.data[col.field] }}
      </template>
<!--      <template #filter="slotProps">-->
<!--        <InputText v-model="slotProps.filterModel.value" type="text" @input="slotProps.filterCallback()" :placeholder="`Filter by ${col.header}`"/>-->
<!--      </template>-->
    </Column>
    <Column v-if="actions.length" header="Actions" :style="{ minWidth: '10rem' }">
      <template #body="slotProps">
        <ButtonInfo>
      </template>
    </Column>
    <Column v-if="extraActions.length" header="Actions" :style="{ minWidth: '10rem' }">
      <template #body="slotProps">
        <Button v-for="action in actions" :key="action.label" :label="action.label" :icon="action.icon" @click="action.handler(slotProps.data)"/>
      </template>
    </Column>
    <template #footer> {{ totalRecords }} résultat(s).</template>
  </DataTable>
</template>

<style scoped>
/* Ajoutez vos styles ici */
</style>
