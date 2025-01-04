<script setup>

import { onMounted, ref } from 'vue'
import { useWeeksStore } from '@/stores/weeksStore.js'
import { formatDateCourt } from '@helpers/date.js'
import api from '@helpers/axios.js'
import { useSemestreStore } from '@stores'
import Card from '@components/components/Card.vue'

const weeks = ref([]) // This will hold the available weeks
const semestres = ref([]) // This will hold the available semestres
const weeksStore = useWeeksStore()
const semestreStore = useSemestreStore()

const selectedValues = ref({}) // This will hold the selected values for each cell

onMounted(async () => {
  const departementId = localStorage.getItem('departement')
  await weeksStore.fetchWeeks()
  weeks.value = weeksStore.weeks['member']
  await semestreStore.getSemestresByDepartement(departementId)
  semestres.value = semestreStore.semestres['member']

  //parcourir les semestres et les contraintes pour les mettre dans selectedValues
  semestres.value.forEach(semestre => {
    semestre.edtContraintesSemestres.forEach(data => {
      const contraintes = data.contraintes
      Object.entries(contraintes).forEach(([week, contrainte]) => {
        selectedValues.value[`${week}-${semestre.id}`] = contrainte.type
      })
    })
  })

  console.log(selectedValues.value)

})

const getCellClass = (weekId, semestre) => {
  const value = selectedValues.value[`${weekId}-${semestre}`]
  switch (value) {
    case 'SAE':
      return 'bg-sae'
    case 'vacances':
      return 'bg-vacances'
    case 'entreprise':
      return 'bg-entreprise'
    case 'contraintes':
      return 'bg-contraintes'
    default:
      return ''
  }
}

const updateContrainte = async (weekId, semestreId) => {
  const value = selectedValues.value[`${weekId}-${semestreId}`]
  console.log(value)

  // envoyer les données au serveur

  const response = await api.post('/api/edt-calendrier-contraintes', {
    weekId,
    semestreId,
    contrainte: value
  })

}
</script>

<template>
  <Card
      title="Calendrier"
      class="mb-4"
  >
    // tableau avec en colonne tous les semestres et en ligne les semaines. Dans chaque case possibilité de mettre si
    c'est vacances, semaine en entreprise, contraintes particulières, etc.
    <table class="w-full table-auto border-collapse border border-slate-500">
      <thead>
      <tr>
        <th colspan="2" class="border border-slate-600">Semaines</th>
        <th
            v-for="semestre in semestres"
            :key="semestre.id"
            class="border border-slate-600">{{ semestre.libelle }}</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="week in weeks" :key="week.id">
        <td class="border border-slate-700">{{ week.id }}</td>
        <td class="border border-slate-700">{{ formatDateCourt(week.dateLundi) }}</td>
        <td
            v-for="semestre in semestres"
            :key="semestre.id"
            :class="`border border-slate-700 ${getCellClass(week.id, semestre.id)}`">
          <select
              v-model="selectedValues[`${week.id}-${semestre.id}`]"
              @change="updateContrainte(week.id, semestre.id)">
            >
            <option value=""></option>
            <option value="SAE">SAE ?</option>
            <option value="vacances">Vacances</option>
            <option value="entreprise">Semaine en entreprise</option>
            <option value="contraintes">Contraintes particulières</option>
          </select>
        </td>
      </tr>
      </tbody>
    </table>
  </Card>
</template>

<style scoped>
.bg-sae {
  background-color: #ffcccc; /* Red for SAE */
}

.bg-vacances {
  background-color: #ccffcc; /* Green for Vacances */
}

.bg-entreprise {
  background-color: #ccccff; /* Blue for Semaine en entreprise */
}

.bg-contraintes {
  background-color: #ffffcc; /* Yellow for Contraintes particulières */
}
</style>
