<script setup>
import { ref, onMounted } from 'vue'
import typeDiplomeService from '@requests/structure_services/typeDiplomeService.js'
import CustomDataTable from '@components/components/CustomDataTable.vue'

const diplomes = ref([])
const selectedDiplome = ref(null)
const updateDiplomeDialog = ref(false)
const showDiplomeDialog = ref(false)
const submitted = ref(false)

const hideDialog = () => {
  updateDiplomeDialog.value = false
  showDiplomeDialog.value = false
}

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

const openModalUpdateDiplome = (diplome) => {
  console.log('update')
  selectedDiplome.value = diplome
  updateDiplomeDialog.value = true
}

const openModalShowDiplome = (diplome) => {
  console.log('show')
  selectedDiplome.value = diplome
  showDiplomeDialog.value = true
}

const updateDiplome = async () => {
  submitted.value = true
  if (selectedDiplome.value.libelle) {

    await typeDiplomeService.update(selectedDiplome.value.id, { name: selectedDiplome.value.libelle })
    updateDiplomeDialog.value = false
    submitted.value = false
  }
}

const deleteDiplome = async (id) => {
  await typeDiplomeService.delete(id.id)
}
</script>

<template>
  <CustomDataTable

      :columns="[
      { field: 'libelle', header: 'Libellé', style: 'min-width: 12rem' },
      { field: 'sigle', header: 'Sigle', style: 'min-width: 12rem' },
      // { field: 'prenom', header: 'Prénom', style: 'min-width: 12rem' }
    ]"
      :actions="[
      { type: 'edit', handler: openModalUpdateDiplome },
      { type: 'delete', handler: deleteDiplome },
      { type: 'show', handler: openModalShowDiplome },
    ]"
      apiEndpoint="api/structure_type_diplomes"
  />

  <Dialog v-model:visible="updateDiplomeDialog" :style="{ width: '450px' }" header="Product Details" :modal="true">
    <div class="flex flex-col gap-6">
      <div>
        <label for="name" class="block font-bold mb-3">Name</label>
        <InputText id="name" v-model.trim="selectedDiplome.libelle" required="true" autofocus :invalid="submitted && !selectedDiplome.libelle" fluid />
        <small v-if="submitted && !selectedDiplome.libelle" class="text-red-500">Le libellé est requis.</small>
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
      <Button label="Save" icon="pi pi-check" @click="updateDiplome" />
    </template>
  </Dialog>
</template>

<style scoped>

</style>
