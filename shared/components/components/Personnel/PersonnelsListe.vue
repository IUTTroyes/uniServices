<script setup>
import {ref, onMounted, computed, onUnmounted} from 'vue'
import { statuts } from '@config/uniServices.js'
import {getPersonnelsService} from '@requests'
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue'
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue'
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue'
import { useUsersStore, useAnneeUnivStore } from '@stores';
import ViewPersonnelDialog from '@/dialogs/Personnels/ViewPersonnelDialog.vue'
import EditPersonnelDialog from '@/dialogs/Personnels/EditPersonnelDialog.vue'
import AccessPersonnelDialog from '@/dialogs/Personnels/AccessPersonnelDialog.vue'
import { usePersonnelFilters } from '@composables/filters/usersFilters/usePersonnelFilters.ts';
import { PhotoUser } from "@components";

const departementId = computed(() => usersStore.departementDefaut ? usersStore.departementDefaut.id : null);
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);
const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const personnels = ref()
const nbPersonnels = ref()
const isLoading = ref(true)
const page = ref(0)
const rowOptions = [30, 60, 120]

const limit = ref(rowOptions[0])
const offset = computed(() => Number(limit.value * page.value))

const showViewDialog = ref(false)
const showEditDialog = ref(false)
const showAccessEditDialog = ref(false)
const selectedPersonnel = ref(null)

const FILTERS_DEBOUNCE_MS = 250;
let filtersDebounceTimeout = null;

//watch filters
const {filters, watchChanges} = usePersonnelFilters();
watchChanges(async() => {
  page.value = 0;
  if (filtersDebounceTimeout) {
    clearTimeout(filtersDebounceTimeout);
  }
  filtersDebounceTimeout = setTimeout(() => {
    getPersonnels();
  }, FILTERS_DEBOUNCE_MS);
});

onMounted(async () => {
  isLoading.value = true
  try {
    await getPersonnels();
  } catch (error) {
    console.error('Erreur dans onMounted:', error)
  } finally {
    isLoading.value = false
  }
})

onUnmounted(() => {
  if (filtersDebounceTimeout) {
    clearTimeout(filtersDebounceTimeout);
  }
});

const getPersonnels = async () => {
  try {
    const paramsListe = {
      departement: departementId.value,
      anneeUniversitaire: selectedAnneeUniversitaireId.value,
      itemsPerPage: limit.value,
      page: page.value + 1,
      filters: filters.value,
    }
    const paramsCount = {
      departement: departementId.value,
      anneeUniversitaire: selectedAnneeUniversitaireId,
      filters: filters.value
    }
    personnels.value = await getPersonnelsService(paramsListe, '/liste')
    nbPersonnels.value = await getPersonnelsService(paramsCount, '/count')
    nbPersonnels.value = Number.parseInt(String(nbPersonnels.value), 10)
  } catch(error) {
    console.error('Erreur lors du chargement des personnels:', error)
  } finally {
    isLoading.value = false
  }
}

async function onPageChange (event) {
  limit.value = event.rows;
  page.value = event.page;
  await getPersonnels();
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
}

const editAccessPersonnel = (personnel) => {
  selectedPersonnel.value = personnel
  showAccessEditDialog.value = true
}
</script>

<template>
  <DataTable
      :value="personnels"
      v-model:filters="filters"
      lazy
      scrollHeight="800px"
      scrollable
      stripedRows
      paginator
      :first="offset"
      :rows="limit"
      :rowsPerPageOptions="rowOptions"
      :totalRecords="nbPersonnels"
      dataKey="id" filterDisplay="row" :loading="isLoading"
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
    <template #isLoading> Loading customers data. Please wait.</template>
    <Column field="photo" :showFilterMenu="false" header="Nom" style="min-width: 6rem">
      <template #body="{ data }">
        <PhotoUser :user-photo="data.photoName" class="rounded-full !w-14 h-auto border-4 border-gray-300 border-opacity-60 mx-auto"/>
      </template>
    </Column>
    <Column field="nom" :showFilterMenu="false" header="Nom" style="min-width: 6rem">
      <template #body="{ data }">
        {{ data.nom }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom"/>
      </template>
    </Column>
    <Column field="prenom" :showFilterMenu="false" header="Prénom" style="min-width: 6rem">
      <template #body="{ data }">
        {{ data.prenom }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par prénom"/>
      </template>
    </Column>
    <Column field="statut" header="Statut" :showFilterMenu="false" style="min-width: 6rem">
      <template #body="{ data }">
        <Tag :value="data.statut" :severity="data.statutSeverity"/>
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <Select v-model="filterModel.value" @change="filterCallback()" :options="statuts"
                placeholder="Filtrer"
                style="min-width: 6rem"
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
    <Column field="numeroHarpege" :showFilterMenu="false" header="N° Harpège." style="min-width: 12rem">
      <template #body="{ data }">
        {{ data.numeroHarpege }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()"
                   placeholder="Filtrer par N° Harpège."/>
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
