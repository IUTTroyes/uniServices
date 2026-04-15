<script setup>
import { ref } from 'vue'
import CustomDataTable from '@components/components/CustomDataTable.vue'
import apiCall from '@helpers/apiCall.js'
import ModalCrud from '@components/components/ModalCrud.vue'
import fields from '@/forms/stagePeriodeForm.js'

import createApiService from '@requests/apiService'
const stagePeriodeService = createApiService('/api/stage_periodes')

const selectedPeriode = ref(null)
const updateDialog = ref(false)
const newDialog = ref(false)
const showDialog = ref(false)
const refreshKey = ref(0)
const departement = localStorage.getItem('departement')

const hideDialog = () => {
  updateDialog.value = false
  showDialog.value = false
  newDialog.value = false
  selectedPeriode.value = {}
}

const openModalUpdate = (diplome) => {
  selectedPeriode.value = diplome
  updateDialog.value = true
}

const openModalNew = () => {
  selectedPeriode.value = {}
  newDialog.value = true
}

const openModalShow = (diplome) => {
  selectedPeriode.value = diplome
  showDialog.value = true
}

const handleSuccess = () => {
  refreshKey.value++
}

const deleteDiplome = async (id) => {
  await apiCall(stagePeriodeService.delete, [id.id], 'Période de stage supprimée', 'Une erreur est survenue lors de la suppression de la période de stage')
  refreshKey.value++
}
</script>

<template>
  <CustomDataTable
      :actionAdd="{ handler: openModalNew }"
      :columns="[
      { field: 'libelle', header: 'Libellé', sortable: true },
      { field: 'structureAnneeUniversitaire.libelle', header: 'Année Universitaire', sortable: true },
      { field: 'dateDebut', header: 'Début', type: 'date', dateFormat: 'dd/MM/yyyy' },
      { field: 'dateFin', header: 'Fin', type: 'date', dateFormat: 'dd/MM/yyyy'}
    ]"
      :actions="[
      { type: 'edit', handler: openModalUpdate },
      { type: 'delete', handler: deleteDiplome },
      { type: 'show', handler: openModalShow },
    ]"
      apiEndpoint="api/stage_periodes"
      :refreshKey="refreshKey"
  />

  <ModalCrud
      :visible="updateDialog"
      :data="selectedPeriode"
      mode="edit"
      :fields="fields"
      :params="{
        departement: departement
      }"
      :serviceMethod="(data) => apiCall(stagePeriodeService.update, [selectedPeriode.id, data], 'Péride de stage mise à jour', 'Une erreur est survenue lors de la mise à jour de la période de stage')"
      :onClose="hideDialog"
      :onSuccess="handleSuccess"
  >
  </ModalCrud>

  <ModalCrud
      :visible="newDialog"
      :data="selectedPeriode"
      :fields="fields"
      :params="{
        departement: departement
      }"
      mode="new"
      :serviceMethod="(data) => apiCall(stagePeriodeService.create, [data], 'Période de stage créée', 'Une erreur est survenue lors de la création de la période de stage')"
      :onClose="hideDialog"
      :onSuccess="handleSuccess"
      @hide="hideDialog"
  >
  </ModalCrud>
</template>
