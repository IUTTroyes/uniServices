<script setup>
import { ref } from 'vue'
import CustomDataTable from '@components/components/CustomDataTable.vue'
import apiCall from '@helpers/apiCall.js'
import ModalCrud from '@components/components/ModalCrud.vue'
import fields from '@/forms/typeDiplomeForm.js'

import createApiService from '@requests/apiService'
const typeDiplomeService = createApiService('/api/structure_type_diplomes')

const selectedDiplome = ref(null)
const updateDialog = ref(false)
const newDialog = ref(false)
const showDialog = ref(false)
const refreshKey = ref(0)

const hideDialog = () => {
  updateDialog.value = false
  showDialog.value = false
  newDialog.value = false
  selectedDiplome.value = {}
}

const openModalUpdate = (diplome) => {
  selectedDiplome.value = diplome
  updateDialog.value = true
}

const openModalNew = () => {
  selectedDiplome.value = {}
  newDialog.value = true
}

const openModalShow = (diplome) => {
  selectedDiplome.value = diplome
  showDialog.value = true
}

const handleSuccess = () => {
  refreshKey.value++
}

const deleteDiplome = async (id) => {
  await apiCall(typeDiplomeService.delete, [id.id], 'Type de diplôme supprimé', 'Une erreur est survenue lors de la suppression du type de diplôme')
  refreshKey.value++
}

const updateApc = async (diplome) => {
  await apiCall(typeDiplomeService.update, [diplome.id, diplome], 'Type de diplôme mis à jour', 'Une erreur est survenue lors de la mise à jour du type de diplôme')
  refreshKey.value++
}
</script>

<template>
  <CustomDataTable
      :actionAdd="{ handler: openModalNew }"
      :columns="[
      { field: 'libelle', header: 'Libellé', sortable: true },
      { field: 'sigle', header: 'Sigle', sortable: true },
      { field: 'apc',
      header: 'Démarche APC', sortable: true, type: 'boolean', handler: updateApc},
    ]"
      :actions="[
      { type: 'edit', handler: openModalUpdate },
      { type: 'delete', handler: deleteDiplome },
      { type: 'show', handler: openModalShow },
    ]"
      apiEndpoint="api/structure_type_diplomes"
      :refreshKey="refreshKey"
  />

  <ModalCrud
      :visible="updateDialog"
      :data="selectedDiplome"
      mode="edit"
      :fields="fields"
      :serviceMethod="(data) => apiCall(typeDiplomeService.update, [selectedDiplome.id, data], 'Type de diplôme mis à jour', 'Une erreur est survenue lors de la mise à jour du type de diplôme')"
      :onClose="hideDialog"
      :onSuccess="handleSuccess"
  >
  </ModalCrud>

  <ModalCrud
      :visible="newDialog"
      :data="selectedDiplome"
      :fields="fields"
      mode="new"
      :serviceMethod="(data) => apiCall(typeDiplomeService.create, [data], 'Type de diplôme créé', 'Une erreur est survenue lors de la création du type de diplôme')"
      :onClose="hideDialog"
      :onSuccess="handleSuccess"
      @hide="hideDialog"
  >
  </ModalCrud>
</template>
