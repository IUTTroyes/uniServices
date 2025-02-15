<script setup>
import { ref, onMounted } from 'vue'
import typeDiplomeService from '@requests/structure_services/typeDiplomeService.js'
import CustomDataTable from '@components/components/CustomDataTable.vue'

const diplomes = ref([])
const fetchDiplomes = async () => {
  diplomes.value = await typeDiplomeService.getAll()
}

const addDiplome = async () => {
  if (newDiplome.value) {
    await typeDiplomeService.create({ name: newDiplome.value })
    newDiplome.value = ''
    fetchDiplomes()
  }
}

const updateDiplome = async (diplome) => {
  if (diplome.name) {
    await typeDiplomeService.update(diplome.id, { name: diplome.name })
    fetchDiplomes()
  }
}

const deleteDiplome = async (id) => {
  await typeDiplomeService.delete(id)
  fetchDiplomes()
}

onMounted(fetchDiplomes)

</script>

<template>
  <CustomDataTable
      :columns="[
      { field: 'libelle', header: 'Libellé', style: 'min-width: 12rem' },
      { field: 'sigle', header: 'Sigle', style: 'min-width: 12rem' },
      // { field: 'prenom', header: 'Prénom', style: 'min-width: 12rem' }
    ]"
      :actions="[
      { type: 'edit', handler: updateDiplome },
      { type: 'delete', handler: deleteDiplome },
      { type: 'show', handler: updateDiplome },
    ]"
      apiEndpoint="api/structure_type_diplomes"
  />
</template>

<style scoped>

</style>
