<script setup>
import InputBlock from '@components/components/InputBlock.vue'
import SelectBlock from '@components/components/SelectBlock.vue'
import CardUser from '@/components/User/CardUser.vue'

import { onMounted, ref } from 'vue'

import { getDepartementSemestresService } from '@requests/structure_services/semestreService.js'

const semestres = ref()
onMounted(async () => {
  console.log('mounted')
  semestres.value = await getDepartementSemestresService(localStorage.getItem('departement'))
  console.log(semestres.value)
})

const users = [
  {
    id: 1,
    nom: 'Doe',
    prenom: 'John',
    email: 'mon.mail@mail.com',
    image: 'https://placehold.co/200x200'
  },
  {
    id: 1,
    nom: 'Doe',
    prenom: 'John',
    email: 'mon.mail@mail.com',
    image: 'https://placehold.co/200x200'
  },
  {
    id: 1,
    nom: 'Doe',
    prenom: 'John',
    email: 'mon.mail@mail.com',
    image: 'https://placehold.co/200x200'
  },
  {
    id: 1,
    nom: 'Doe',
    prenom: 'John',
    email: 'mon.mail@mail.com',
    image: 'https://placehold.co/200x200'
  },
  {
    id: 1,
    nom: 'Doe',
    prenom: 'John',
    email: 'mon.mail@mail.com',
    image: 'https://placehold.co/200x200'
  },
]
</script>

<template>
  <div class="card">
    <div class="card-body flex flex-col gap-10">
      <div class="row">
        <div class="col-3 p-2">
          <SelectBlock
              label="Type"
              :data="[
                  { value: '1', label: 'Type 1' },
                  { value: '2', label: 'Type 2' },
                  { value: '3', label: 'Type 3' },
              ]"
          ></SelectBlock>

        </div>
        <div class="col-3 p-2">
          <SelectBlock
              label="Semestre"
              :data="semestres"
              optionLabel="libelle"
          ></SelectBlock>
        </div>
        <div class="col-3 p-2">
          <SelectBlock
              label="Groupe"></SelectBlock>
        </div>
        <div class="col-3 p-2">
          <InputBlock
              label="Recherche libre"></InputBlock>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-3 p-2" v-for="user in users">
      <CardUser :user="user"
                :key="user.id">
      </CardUser>
    </div>
  </div>
</template>

<style scoped>

</style>
