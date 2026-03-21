<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSemestreStore } from '@stores'
import { getDepartementSemestresService } from '@requests'

const route = useRoute()
const router = useRouter()

const semestres = ref([])

onMounted(async () => {
  const departement = localStorage.getItem('departement')// Remplacez 'defaultDepartement' par une valeur par défaut appropriée
  semestres.value = await getDepartementSemestresService(departement, true)
  console.log(semestres.value)
})
const selectedSemestre = ref(route.params.semestreId || semestres.value['member'][0]?.id)

const onSemestreChange = (() => {
  console.log(route)
  console.log(selectedSemestre.value)
  router.push({
    name: route.name,
    params: { ...route.params, semestreId: selectedSemestre.value }
  })
})
</script>

<template>
  <div v-if="semestres">
    <select v-model="selectedSemestre" @change="onSemestreChange">
      <option v-for="sem in semestres" :key="sem.id" :value="sem.id">
        {{ sem.libelle }}
      </option>
    </select>
  </div>
</template>
