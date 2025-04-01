<script setup>
import InputBlock from '@components/components/InputBlock.vue'
import SelectBlock from '@components/components/SelectBlock.vue'
import CardUser from '@/components/User/CardUser.vue'
import Alert from '@components/components/Alert.vue'
import { onMounted, ref, watch } from 'vue'

import { getDepartementSemestresService } from '@requests/structure_services/semestreService.js'
import { getDepartementGroupesService } from '@requests/structure_services/groupeService.js'

const semestres = ref()
const groupesSemestre = ref()
const groupes = ref()
const selectedTypeUser = ref('etudiant')
const selectedSemestre = ref()
const selectedGroupe = ref()
const selectedSearch = ref()
const users = ref([])

onMounted(async () => {
  const departementId = localStorage.getItem('departement')
  semestres.value = await getDepartementSemestresService(departementId)
  groupes.value = await getDepartementGroupesService(departementId)
})

const typeUser = [
  { value: 'etudiant', label: 'Etudiants' },
  { value: 'permanent', label: 'Permanents' },
  { value: 'vacataire', label: 'Vacataires' },
  { value: 'adm', label: 'Administratifs/techniciens' },
]

// const users = [
//   {
//     id: 1,
//     nom: 'Doe',
//     prenom: 'John',
//     email: 'mon.mail@mail.com',
//     image: 'https://placehold.co/200x200'
//   },
//   {
//     id: 1,
//     nom: 'Doe',
//     prenom: 'John',
//     email: 'mon.mail@mail.com',
//     image: 'https://placehold.co/200x200'
//   },
//   {
//     id: 1,
//     nom: 'Doe',
//     prenom: 'John',
//     email: 'mon.mail@mail.com',
//     image: 'https://placehold.co/200x200'
//   },
//   {
//     id: 1,
//     nom: 'Doe',
//     prenom: 'John',
//     email: 'mon.mail@mail.com',
//     image: 'https://placehold.co/200x200'
//   },
//   {
//     id: 1,
//     nom: 'Doe',
//     prenom: 'John',
//     email: 'mon.mail@mail.com',
//     image: 'https://placehold.co/200x200'
//   },
// ]
const display = ref(false)

const updateSelects = ((valeur) => {
  console.log(valeur)
  if (valeur?.value !== 'etudiant') {
    groupesSemestre.value = []
    display.value = false
  }

  if (valeur?.value === 'etudiant') {
    display.value = true
  }
})

watch(() => (selectedTypeUser.value), () => {
  updateSelects(selectedTypeUser.value)
})

watch(() => (selectedSemestre.value), () => {
  if (!selectedSemestre.value) {
    groupesSemestre.value = groupes.value
    return
  }

  groupesSemestre.value = groupes.value.filter(groupe => groupe.semestre_id === selectedSemestre.value.id)

  updateListeUsers()
})

watch(() => (selectedGroupe.value), () => {
  updateListeUsers()
})

const updateListeUsers = () => {

}

</script>

<template>
  <div class="card">
    <div class="card-body flex flex-col gap-10">
      <div class="row">
        <div class="col-3 p-2">
          <SelectBlock
              v-model="selectedTypeUser"
              id="typeUser"
              label="Type"
              :data="typeUser"
          ></SelectBlock>

        </div>
        <div class="col-3 p-2">
          <SelectBlock
              :disabled="!display"
              id="semestre"
              label="Semestre"
              v-model="selectedSemestre"
              :data="semestres"
              optionLabel="libelle"
          ></SelectBlock>
        </div>
        <div class="col-3 p-2">
          <SelectBlock
              :disabled="!display"
              id="groupe"
              v-model="selectedGroupe"
              label="Groupe"
              :data="groupesSemestre"
          ></SelectBlock>
        </div>
        <div class="col-3 p-2">
          <InputBlock
              id="search"
              v-model="selectedSearch"
              label="Recherche libre"></InputBlock>
        </div>
      </div>
    </div>
  </div>

  <div class="row" v-if="users.length > 0">
    <div class="col-3 p-2" v-for="user in users">
      <CardUser :user="user"
                :key="user.id">
      </CardUser>
    </div>
  </div>
  <div v-else>
    <Alert severity="info">Aucun utilisateur trouvé ou correspondant aux critères</Alert>
  </div>
</template>

<style scoped>

</style>
