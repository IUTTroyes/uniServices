<script setup>

import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue'
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue'

import { computed, onMounted, ref, watch } from 'vue'

import { FilterMatchMode } from '@primevue/core/api'

import { getAllQuestionnaires } from '@/requests/questionnaire_services/questionnaireService.js'
import SurveyPreviewModal from '@/components/Questionnaire/SurveyPreviewModal.vue'

const statuts = [
  { label: 'Publié', value: 'published', severity: 'success' },
  { label: 'Brouillon', value: 'draft', severity: 'warn' },
  { label: 'Fermé', value: 'closed', severity: 'danger' },
]
const questionnaires = ref()
const nbQuestionnaires = ref()
const loading = ref(true)
const showPreviewDialog = ref(false)
const selectedQuestionnaire = ref(null)
const page = ref(0)
const rowOptions = [30, 60, 120]

const limit = ref(rowOptions[0])
const offset = computed(() => Number(limit.value * page.value))

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  titre: { value: null, matchMode: FilterMatchMode.CONTAINS },
  statut: { value: null, matchMode: FilterMatchMode.EQUALS }
})

const fetchQuestionnaires = async () => {
  loading.value = true
  try {
    const data = await getAllQuestionnaires(page.value + 1, filters.value)
    nbQuestionnaires.value = data['totalItems']
    questionnaires.value = data['member']
  } catch (error) {
    console.error('Erreur lors du chargement des questionnaires:', error)
  } finally {
    loading.value = false
  }
}

onMounted(fetchQuestionnaires)

async function onPageChange (event) {
  console.log(event)
  page.value = event.page
  await fetchQuestionnaires()
}

//watch filters
watch(filters, async () => {
  page.value = 0
  await fetchQuestionnaires()
}, { deep: true })

const viewQuestionnaire = (questionnaire) => {
  selectedQuestionnaire.value = questionnaire
  showPreviewDialog.value = true
}
</script>

<template>

  <DataTable v-model:filters="filters" :value="questionnaires"
             lazy
             stripedRows
             paginator
             :first="offset"
             :rows="limit"
             :rowsPerPageOptions="rowOptions"
             :totalRecords="nbQuestionnaires"
             dataKey="id" filterDisplay="row" :loading="loading"
             @page="onPageChange($event)"
             @update:rows="limit = $event"
             :globalFilterFields="['titre']">
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
    <Column field="titre" :showFilterMenu="false" header="Titre" style="min-width: 12rem">
      <template #body="{ data }">
        {{ data.titre }}
      </template>
      <template #filter="{ filterModel, filterCallback }">
        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par titre"/>
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


    <Column :showFilterMenu="false" style="min-width: 12rem">
      <template #body="slotProps">
        <ButtonInfo tooltip="Aperçu du questionnaire"
                    @click="viewQuestionnaire(slotProps.data)"
        />
        <ButtonEdit
            as="a" :href="`/administration/qualite/enquetes/builder/${slotProps.data.uuid}`"
            tooltip="Modifier le questionnaire"
        />
        <template v-if="slotProps.data.published">
          <Button
              as="a"
              :to="`/responses/${slotProps.data.uuid}`"
              class="mr-2"
              icon="pi pi-users"
              tooltip="Voir les réponses"
              outlined
              severity="info"
              rounded
          >
          </Button>
          <Button @click="" class="mr-2"
          icon="pi pi-download"
                  tooltip="Exporter le questionnaire"
                  outlined
                  severity="success"
                  rounded
          >
          </Button>
        </template>
      </template>
    </Column>
    <template #footer> {{ nbQuestionnaires }} résultat(s).</template>

  </DataTable>

  <SurveyPreviewModal
      v-if="showPreviewDialog"
      :survey="selectedQuestionnaire"
      @close="showPreviewDialog = false"
  />

</template>

<style scoped>

</style>
