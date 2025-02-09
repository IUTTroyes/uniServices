<script setup>
import { ref, onMounted, computed } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import api from '@helpers/axios.js'
import { statuts } from '@config/uniServices.js'

const personnels = ref()
const nbPersonnels = ref()
const loading = ref(true)
const page = ref(0)
const rowOptions = [30, 60, 120]

const limit = ref(rowOptions[0])
const offset = computed(() => Number(limit.value * page.value))

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  nom: { value: null, matchMode: FilterMatchMode.CONTAINS },
  prenom: { value: null, matchMode: FilterMatchMode.CONTAINS },
  'country.name': { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  representative: { value: null, matchMode: FilterMatchMode.IN },
  statut: { value: null, matchMode: FilterMatchMode.EQUALS },
  verified: { value: null, matchMode: FilterMatchMode.EQUALS }
})

onMounted(async () => {
  loading.value = true
  const response = await api.get('api/personnels') //todo sur PersonnelDepartement ici
  nbPersonnels.value = await response.data['totalItems']
  personnels.value = await response.data['member']
  loading.value = false
})

async function onPageChange (event) {
  console.log(event)
  loading.value = true
  page.value = event.page
  const response = await api.get(`api/personnels?page=${parseInt(page.value) + 1}`, {
    params: {
      ...filters.value
    }
  })
  personnels.value = await response.data['member']
  loading.value = false
}


</script>

<template>
  <DataTable v-model:filters="filters" :value="personnels"
             lazy
             stripedRows
             paginator
             :first="offset"
             :rows="limit"
             :rowsPerPageOptions="rowOptions"
             :totalRecords="nbPersonnels"
             dataKey="id" filterDisplay="row" :loading="loading"
             @page="onPageChange($event)"
             @update:rows="limit = $event"
             :globalFilterFields="['nom', 'prenom']">
    <template #header>
      <div class="flex justify-end">
        <IconField>
          <InputIcon>
            <i class="pi pi-search"/>
          </InputIcon>
          <InputText v-model="filters['global'].value" placeholder="Keyword Search"/>
        </IconField>
      </div>
    </template>
    <template #empty> No customers found.</template>
    <template #loading> Loading customers data. Please wait.</template>
    <Column field="nom" header="Nom" style="min-width: 12rem">
      <template #body="{ data }">
        {{ data.nom }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom"/>
      </template>
    </Column>
    <Column field="prenom" header="Prénom" style="min-width: 12rem">
      <template #body="{ data }">
        {{ data.prenom }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par prénom"/>
      </template>
    </Column>
    <!--    <Column header="Country" filterField="country.name" style="min-width: 12rem">-->
    <!--      <template #body="{ data }">-->
    <!--        <div class="flex items-center gap-2">-->
    <!--          <img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`flag flag-${data.country.code}`" style="width: 24px" />-->
    <!--          <span>{{ data.country.name }}</span>-->
    <!--        </div>-->
    <!--      </template>-->
    <!--      <template #filter="{ filterModel, filterCallback }">-->
    <!--        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Search by country" />-->
    <!--      </template>-->
    <!--    </Column>-->
    <!--    <Column header="Agent" filterField="representative" :showFilterMenu="false" style="min-width: 14rem">-->
    <!--      <template #body="{ data }">-->
    <!--        <div class="flex items-center gap-2">-->
    <!--          <img :alt="data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${data.representative.image}`" style="width: 32px" />-->
    <!--          <span>{{ data.representative.name }}</span>-->
    <!--        </div>-->
    <!--      </template>-->
    <!--      <template #filter="{ filterModel, filterCallback }">-->
    <!--        <MultiSelect v-model="filterModel.value" @change="filterCallback()" :options="representatives" optionLabel="name" placeholder="Any" style="min-width: 14rem" :maxSelectedLabels="1">-->
    <!--          <template #option="slotProps">-->
    <!--            <div class="flex items-center gap-2">-->
    <!--              <img :alt="slotProps.option.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.option.image}`" style="width: 32px" />-->
    <!--              <span>{{ slotProps.option.name }}</span>-->
    <!--            </div>-->
    <!--          </template>-->
    <!--        </MultiSelect>-->
    <!--      </template>-->
    <!--    </Column>-->
    <Column field="statut" header="Statut" :showFilterMenu="false" style="min-width: 12rem">
      <template #body="{ data }">
        <Tag :value="data.statut" :severity="data.statutSeverity"/>
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <Select v-model="filterModel.value" @change="filterCallback()" :options="statuts" placeholder="Filtrer"
                style="min-width: 12rem" :showClear="true">
          <template #option="slotProps">
            <Tag :value="slotProps.option.value" :severity="slotProps.option.severity"/>
          </template>
        </Select>
      </template>
    </Column>
    <!--    <Column field="verified" header="Verified" dataType="boolean" style="min-width: 6rem">-->
    <!--      <template #body="{ data }">-->
    <!--        <i class="pi" :class="{ 'pi-check-circle text-green-500': data.verified, 'pi-times-circle text-red-400': !data.verified }"></i>-->
    <!--      </template>-->
    <!--      <template #filter="{ filterModel, filterCallback }">-->
    <!--        <Checkbox v-model="filterModel.value" :indeterminate="filterModel.value === null" binary @change="filterCallback()" />-->
    <!--      </template>-->
    <!--    </Column>-->
    <template #footer> {{ nbPersonnels }} résultat(s).</template>

  </DataTable>
</template>

<style scoped>

</style>
