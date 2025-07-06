<script setup>
import { typesGroupes } from '@config/uniServices.js'
import { ref, computed } from 'vue'

const selectedTypeGroupe = ref(null)
//const listeGroupes = ref([])

//todo : récupérer la liste des types de groupes depuis le service selon le type de groupe et le semestre
//todo: récupérer la liste des étudiants du semestre sélectionné
// mettre à jour les groupes

//je veux une liste de types de groupes pour tester
const listeGroupes = ref([
  { label: 'Cours Magistral', value: 'AB' },
  { label: 'Travaux Dirigés', value: 'CD' },
  { label: 'Travaux Pratiques', value: 'EF' }
])

//je veux une liste de quelques étudiants pour tester
const etudiants = ref([
  { libelle: 'Étudiant 1', numEtudiant: '123456', type: 'AB' },
  { libelle: 'Étudiant 2', numEtudiant: '654321', type: 'CD' },
  { libelle: 'Étudiant 3', numEtudiant: '789012', type: 'EF' },
  { libelle: 'Étudiant 4', numEtudiant: '210987', type: 'EF' },
  { libelle: 'Étudiant 5', numEtudiant: '345678', type: 'AB' },
])

</script>

<template>
  <label for="typeGroupe" class="block mb-2">Choisir un type de groupe</label>
<Select
  id="typeGroupe"
  v-model="selectedTypeGroupe"
  :options="typesGroupes"
  option-label="label"
  option-value="value"
  placeholder="Sélectionnez un type de groupe"
  class="w-full"></Select>


<div v-if="selectedTypeGroupe && listeGroupes.length > 0">
  <DataTable
      scrollable scrollHeight="500px"
    :value="etudiants"
    class="mt-4">
    <Column field="libelle" header="Etudiant" />
    <Column field="numEtudiant" header="Numéro étudiant" />
    <Column header="Sans groupe">
      <template #body="{ data }">
        <input
            type="radio"
            :name="`groupe-${data.numEtudiant}`"
            value="sans-groupe"
            :checked="!listeGroupes.some(groupe => groupe.value === data.type)"
            @change="data.type = 'sans-groupe'"
        />
      </template>
    </Column>
    <Column v-for="groupe of listeGroupes" :key="groupe.field" :field="groupe.field" :header="groupe.value">
      <template #body="{ data }">
        <input
          type="radio"
          :name="`groupe-${data.numEtudiant}`"
          :value="groupe.value"
          :checked="data.type === groupe.value"
          @change="data.type = groupe.value"
        />
      </template>
    </Column>
  </DataTable>
</div>
</template>
<style scoped>

</style>
