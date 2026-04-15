<script setup>
import CustomDataTable from '@components/components/CustomDataTable.vue'
import { ref } from 'vue'

const refreshKey = ref(0)
</script>

<template>
  <CustomDataTable
      :columns="[
      { field: 'personnel.prenom', header: 'Prénom', sortable: true },
      { field: 'personnel.nom', header: 'Nom', sortable: true },
      { field: 'departement.libelle', header: 'Département', sortable: true },
      // { field: 'sigle', header: 'Sigle', sortable: true },
      // { field: 'apc',
      // header: 'Démarche APC', sortable: true, type: 'boolean', handler: updateApc},
    ]"
      :actions="[
      // { type: 'edit', handler: openModalUpdate },
      // { type: 'delete', handler: deleteDiplome },
      // { type: 'show', handler: openModalShow },
    ]"
      apiEndpoint="api/structure_departement_personnels"
      :refreshKey="refreshKey"
  />
</template>

<style scoped>

</style>
