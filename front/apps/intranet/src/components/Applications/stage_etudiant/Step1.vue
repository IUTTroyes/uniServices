<script setup>
import Alert from '@components/components/Alert.vue'
import DynamicForm from '@components/components/forms/DynamicForm.vue'
import { step1Fields } from '@/forms/stageEtudiantForm.js'
import apiCall from '@helpers/apiCall.js'
import createApiService from '@requests/apiService.js'
import { ref } from 'vue'
const stagePeriodeService = createApiService('/api/stage_periodes')

console.log(step1Fields)
console.log(stagePeriodeService)
const formData = ref({})
</script>

<template>
  <h2 class="text-2xl font-bold mb-4">Vos informations</h2>
  <Alert severity="warn">Merci de vérifier et de compléter les informations ci-dessous.</Alert>

  <DynamicForm
      mode="new"
      :data="formData"
      :fields="step1Fields"
      :params="{}"
      :serviceMethod="(data) => apiCall(stagePeriodeService.update, [1, data], 'Péride de stage mise à jour', 'Une erreur est survenue lors de la mise à jour de la période de stage')"
  ></DynamicForm>

</template>

<style scoped>

</style>
