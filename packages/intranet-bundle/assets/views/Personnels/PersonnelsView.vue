<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import api from '@helpers/axios.js'
import { statuts } from '@config/uniServices.js'
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue'
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue'
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue'
import createApiService from '@requests/apiService'
import apiCall from '@helpers/apiCall'

import ViewPersonnelDialog from '@/dialogs/personnels/ViewPersonnelDialog.vue'
import EditPersonnelDialog from '@/dialogs/personnels/EditPersonnelDialog.vue'
import AccessPersonnelDialog from '@/dialogs/personnels/AccessPersonnelDialog.vue'

const personnelsService = createApiService('api/personnels')

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
  statut: { value: null, matchMode: FilterMatchMode.EQUALS },
  numeroHarpege: { value: null, matchMode: FilterMatchMode.EQUALS },
  mailUniv: { value: null, matchMode: FilterMatchMode.EQUALS },
})

const showViewDialog = ref(false)
const showEditDialog = ref(false)
const showAccessEditDialog = ref(false)
const selectedPersonnel = ref(null)

onMounted(async () => {
  loading.value = true
  try {
    const data = await apiCall(
      personnelsService.getAll,
      [],
      '', // Empty success message to not show toast
      'Erreur lors de la récupération des personnels'
    )
    nbPersonnels.value = data['totalItems']
    personnels.value = data['member']
  } catch (error) {
    console.error('Erreur dans onMounted:', error)
  } finally {
    loading.value = false
  }
})

async function onPageChange (event) {
  console.log(event)
  loading.value = true
  page.value = event.page
  try {
    const data = await apiCall(
      api.get,
      [`api/personnels?page=${parseInt(page.value) + 1}`, { params: { ...filters.value } }],
      '', // Empty success message to not show toast
      'Erreur lors de la récupération des personnels'
    )
    personnels.value = data['member']
  } catch (error) {
    console.error('Erreur dans onPageChange:', error)
  } finally {
    loading.value = false
  }
}

const viewPersonnel = (personnel) => {
  selectedPersonnel.value = personnel
  showViewDialog.value = true
}

const editPersonnel = (personnel) => {
  selectedPersonnel.value = personnel
  showEditDialog.value = true
}

const deletePersonnel = (personnel) => {
  console.log(personnel)
}

const editAccessPersonnel = (personnel) => {
  selectedPersonnel.value = personnel
  showAccessEditDialog.value = true
}

//watch filters
watch(filters, async () => {
  loading.value = true
  try {
    const data = await apiCall(
      api.get,
      ['api/personnels', { params: { ...filters.value } }],
      '', // Empty success message to not show toast
      'Erreur lors de la récupération des personnels'
    )
    nbPersonnels.value = data['totalItems']
    personnels.value = data['member']
  } catch (error) {
    console.error('Erreur dans watch filters:', error)
  } finally {
    loading.value = false
  }
})


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
    <Column field="nom" :showFilterMenu="false" header="Nom" style="min-width: 12rem">
      <template #body="{ data }">
        {{ data.nom }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom"/>
      </template>
    </Column>
    <Column field="prenom" :showFilterMenu="false" header="Prénom" style="min-width: 12rem">
      <template #body="{ data }">
        {{ data.prenom }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par prénom"/>
      </template>
    </Column>
    <Column field="statut" header="Statut" :showFilterMenu="false" style="min-width: 12rem">
      <template #body="{ data }">
        <Tag :value="data.statut" :severity="data.statutSeverity"/>
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <Select v-model="filterModel.value" @change="filterCallback()" :options="statuts"
                placeholder="Filtrer"
                style="min-width: 12rem"
                :showClear="true">
          <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-center">
              <Tag :value="slotProps.value.label" :severity="slotProps.value.severity"/>
            </div>
            <span v-else>
                    {{ slotProps.placeholder }}
                </span>
          </template>
          <template #option="slotProps">
            <Tag
                :value="slotProps.option.value"
                :severity="slotProps.option.severity"/>
          </template>
        </Select>
      </template>
    </Column>
    <Column field="numeroHarpege" :showFilterMenu="false" header="N° Admin." style="min-width: 12rem">
      <template #body="{ data }">
        {{ data.numeroHarpege }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()"
                   placeholder="Filtrer par N° Admin."/>
      </template>
    </Column>
    <Column field="mailUniv" :showFilterMenu="false" header="Email" style="min-width: 12rem">
      <template #body="{ data }">
        {{ data.mailUniv }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par email"/>
      </template>
    </Column>
    <Column :showFilterMenu="false" style="min-width: 12rem">
      <template #body="slotProps">
        <ButtonInfo tooltip="Voir les détails" @click="viewPersonnel(slotProps.data)"/>
        <Button icon="pi pi-key"
                v-tooltip.bottom="'Gérer les droits'"
                outlined severity="warn" rounded class="mr-2" @click="editAccessPersonnel(slotProps.data)"/>
        <ButtonEdit
            tooltip="Modifier le personnel"
            @click="editPersonnel(slotProps.data)"/>
        <ButtonDelete
            tooltip="Supprimer le personnel du département"
            @confirm-delete="deletePersonnel(slotProps.data)"/>
      </template>
    </Column>
    <template #footer> {{ nbPersonnels }} résultat(s).</template>

  </DataTable>

  <ViewPersonnelDialog
      :isVisible="showViewDialog"
      :personnel="selectedPersonnel"
      @update:visible="showViewDialog = $event"/>
  <EditPersonnelDialog
      :isVisible="showEditDialog"
      :personnel="selectedPersonnel"
      @update:visible="showEditDialog = $event"/>
  <AccessPersonnelDialog
      :isVisible="showAccessEditDialog"
      :personnel="selectedPersonnel"
      @update:visible="showAccessEditDialog = $event"/>
</template>

<style scoped>

</style>
