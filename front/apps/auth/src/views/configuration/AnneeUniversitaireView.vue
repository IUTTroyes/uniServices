<script setup>
import { ref } from 'vue'
import CustomDataTable from '@components/components/CustomDataTable.vue'
import apiCall from '@helpers/apiCall.js'
import ModalCrud from '@components/components/ModalCrud.vue'
import fields from '@/forms/structureAnneeUniversitaireForm.js'

import createApiService from '@requests/apiService'
const structureAnneeUniversitaireService = createApiService('/api/structure_annee_universitaires')

const selectedAnnee = ref(null)
const updateDialog = ref(false)
const newDialog = ref(false)
const showDialog = ref(false)
const refreshKey = ref(0)

const hideDialog = () => {
  updateDialog.value = false
  showDialog.value = false
  newDialog.value = false
  selectedAnnee.value = {}
}

const openModalUpdate = (anneeUniversitaire) => {
  selectedAnnee.value = anneeUniversitaire
  updateDialog.value = true
}

const openModalNew = () => {
  selectedAnnee.value = {}
  newDialog.value = true
}

const openModalShow = (anneeUniversitaire) => {
  selectedAnnee.value = anneeUniversitaire
  showDialog.value = true
}

const handleSuccess = () => {
  refreshKey.value++
}

const deleteAnneeUniversitaire = async (id) => {
  await apiCall(structureAnneeUniversitaireService.delete, [id.id], 'Année universitaire supprimée', 'Une erreur est survenue lors de la suppression de l\'année universitaire')
  refreshKey.value++
}

const updateActive = async (anneeUniversitaire) => {
  await apiCall(structureAnneeUniversitaireService.update, [anneeUniversitaire.id, anneeUniversitaire], 'Année universitaire mise à jour', 'Une erreur est survenue lors de la mise à jour de l\'année universitaire')
  refreshKey.value++
}
</script>

<template>
  <CustomDataTable
      :actionAdd="{ handler: openModalNew }"
      :columns="[
      { field: 'libelle', header: 'Libellé', sortable: true },
      { field: 'annee', header: 'Année', sortable: true },
      { field: 'commentaire', header: 'Commentaire', sortable: false },
      { field: 'actif',
      header: 'Année active', sortable: true, type: 'boolean', handler: updateActive},
    ]"
      :actions="[
      { type: 'edit', handler: openModalUpdate },
      { type: 'delete', handler: deleteAnneeUniversitaire },
      { type: 'show', handler: openModalShow },
    ]"
      apiEndpoint="api/structure_annee_universitaires"
      :refreshKey="refreshKey"
  />

  <ModalCrud
      :visible="updateDialog"
      :data="selectedAnnee"
      mode="edit"
      :fields="fields"
      :serviceMethod="(data) => apiCall(structureAnneeUniversitaireService.update, [selectedAnnee.id, data], 'Année universitaire mise à jour', 'Une erreur est survenue lors de la mise à jour de l\'année universitaire')"
      :onClose="hideDialog"
      :onSuccess="handleSuccess"
  >
  </ModalCrud>

  <ModalCrud
      :visible="newDialog"
      :data="selectedAnnee"
      :fields="fields"
      mode="new"
      :serviceMethod="(data) => apiCall(structureAnneeUniversitaireService.create, [data], 'Année universitaire créée', 'Une erreur est survenue lors de la création de l\'année universitaire')"
      :onClose="hideDialog"
      :onSuccess="handleSuccess"
      @hide="hideDialog"
  >
  </ModalCrud>
</template>
