<script setup>
import { ref, onMounted, computed } from 'vue'
import {
  useSemestreStore,
  useMatieresStore} from '@stores'
import useProgressions from '@/service/useProgressions.js'
import { useWeeksStore } from '@/stores/weeksStore.js'
import { formatDateCourt } from '@helpers/date.js'
import { getPersonnelsDepartement } from '@requests/user_services/personnelService.js'

const matieresStore = useMatieresStore()
const { progressions, fetchProgressions, updateProgression } = useProgressions()
const weeksStore = useWeeksStore()
const semestreStore = useSemestreStore()
const personnels = ref([])
const weeks = ref([])
const semestres = ref([])
const semesterFilter = ref('')
const parcoursFilter = ref('')
const searchFilter = ref('')
const professorFilter = ref('')
const subjectFilter = ref('')
const isLoaded = ref(false)
const sortColumn = ref('matiere')
const sortOrder = ref('asc')

onMounted(async () => {
  try {
    // Load data
    await matieresStore.getMatieres()
    await fetchProgressions()
    await weeksStore.fetchWeeks()
    const departementId = localStorage.getItem('departement')
    personnels.value = await getPersonnelsDepartement(departementId)
    await semestreStore.getSemestresByDepartement(departementId)
    semestres.value = semestreStore.semestres['member']
    weeks.value = weeksStore.weeks['member']
    console.log(weeks.value)
    sortProgressions() // Apply default sort
    isLoaded.value = true
  } catch (error) {
    console.error('Error loading data:', error)
  }
})

const sortByColumn = (column) => {
  if (sortColumn.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    sortOrder.value = 'asc'
  }
  sortProgressions()
}

const sortProgressions = () => {
  progressions.value.sort((a, b) => {
    let result = 0
    if (a[sortColumn.value] < b[sortColumn.value]) {
      result = -1
    } else if (a[sortColumn.value] > b[sortColumn.value]) {
      result = 1
    }
    return sortOrder.value === 'asc' ? result : -result
  })
}

const isWeekRestricted = (semestre, week) => {
  // semestre est une IRI, récupérer l'objet depuis semestresStores.semestres
  semestre = semestres.value.find((s) => s['@id'] === semestre)

  let restrictedSlots = {}
  restrictedSlots = week.edtCreneauxInterditsSemaines

  if (!semestre || !restrictedSlots[semestre.nom]) return false
  return true
}

// const updateProgression = async (row) => {
//   try {
//     await updateProgression(row)
//   } catch (error) {
//     console.error('Error updating progression:', error)
//   }
// }

const isOkRow = (row) => {
  if (!row.progression) return false
  if (row.nbCm === 0 && row.nbTd === 0 && row.nbTp === 0) return false

  // vérifier si sur les semaines, il y a l'ensemble des séances de CM, TD et TP de planifiées
  let cmSessions = 0
  let tdSessions = 0
  let tpSessions = 0

  let sessionsCM = []
  let sessionsTD = []
  let sessionsTP = []

  // Iterate over each session in the progression array
  row.progression.forEach((session) => {
    if (session) {
      session.split(' ').forEach((type) => {
        if (type.includes('CM')) {
          sessionsCM.push(type)
          cmSessions++
        }
        if (type.includes('TD')) {
          sessionsTD.push(type)
          tdSessions++
        }

        if (type.includes('TP')) {
          sessionsTP.push(type)
          tpSessions++
        }
      })
    }
  })

  // vérifier si les séances sont consécutives
  if (!isSequential(sessionsCM) || !isSequential(sessionsTD) || !isSequential(sessionsTP))
    return false

  // vérification
  return cmSessions === row.nbCm && tdSessions === row.nbTd && tpSessions === row.nbTp
}

const isSequential = (sessions) => {
  // Extract numeric part and sort
  const numbers = sessions
      .map((session) => parseInt(session.replace(/\D/g, '')))
      .sort((a, b) => a - b)

  // Check if each number is one more than the previous
  for (let i = 1; i < numbers.length; i++) {
    if (numbers[i] !== numbers[i - 1] + 1) {
      return false
    }
  }
  return true
}

const sumColumn = (type) => {
  return progressions.value.reduce((sum, row) => sum + row[`nb${type}`], 0)
}

const toUpperCase = (row, week) => {
  // uniquement si row.progression[week] est non null
  if (row.progression[week]) {
    row.progression[week] = row.progression[week].toUpperCase()
  }
}

const clearFilters = () => {
  semesterFilter.value = ''
  parcoursFilter.value = ''
  professorFilter.value = ''
  subjectFilter.value = ''
  searchFilter.value = ''
}

const filteredMatieres = (semestre) => {
  if (!semestre) return []
  return matieresStore.matieres.filter((matiere) => matiere.semestre === semestre)
}

const filteredProgressions = computed(() => {
  return progressions.value.filter((row) => {
    const matchesSemester =
        semesterFilter.value === '' || row.semestre.includes(semesterFilter.value)
    const matchesParcours =
        parcoursFilter.value === '' || row.parcours.includes(parcoursFilter.value)
    const matchesProfessor =
        professorFilter.value === '' || row.personnel.includes(professorFilter.value)
    const matchesSubject =
        subjectFilter.value === '' ||
        (typeof row.matiere !== 'undefined' && row.matiere.includes(subjectFilter.value))
    const matchesSearch =
        searchFilter.value === '' ||
        Object.values(row).some((value) => value.toString().includes(searchFilter.value))
    return matchesSemester && matchesParcours && matchesProfessor && matchesSubject && matchesSearch
  })
})

const generateSlots = async () => {
  if (confirm('Vous êtes sûr de vouloir générer les créneaux ?')) {
    try {
      await useProgressions.generateSlots()
      alert('Les créneaux ont été générés avec succès.')
    } catch (error) {
      console.error('Error generating slots:', error)
      alert('Une erreur est survenue lors de la génération des créneaux.')
    }
  }
}
</script>

<template>
  <div class="row" v-if="isLoaded">
    <div class="col-2">
      <label for="semesterFilter">Semestre</label><br />
      <Select
        v-model="semesterFilter"
        filter
        :options="semestres"
        optionLabel="libelle"
        optionValue="id"
        placeholder="Choisir un semestre"
        class="w-full md:w-56"
      />
    </div>
    <div class="col-2">
      <label for="parcoursFilter">Parcours</label><br />
      <InputText id="parcoursFilter" v-model="parcoursFilter" placeholder="Filtrer par parcours" />
    </div>
    <div class="col-2">
      <label for="professorFilter">Professeur</label><br />
      <InputText
        id="professorFilter"
        v-model="professorFilter"
        placeholder="Filtrer par professeur"
      />
    </div>
    <div class="col-2">
      <label for="matiereFilter">Matières</label><br />
      <Select
        v-model="subjectFilter"
        filter
        id="matiereFilter"
        :options="matieresStore.matieres"
        optionLabel="code"
        optionValue="@id"
        placeholder="Choisir une matière"
        class="w-full md:w-56"
      />
    </div>
    <div class="col-3">
      <label for="searchFilter">Recherche</label><br />
      <InputText id="searchFilter" v-model="searchFilter" placeholder="Recherche libre" />
    </div>
    <div class="col-1">
      <Button label="Annuler" icon="pi pi-times" @click="clearFilters" />
    </div>
  </div>
  <div class="table-container">
    <!-- Header Row with Filters -->
    <table>
      <thead>
        <tr>
          <th
            class="fixed-column filterable-column"
            @click="sortByColumn('semestre')"
            :class="{
              'sort-active': sortColumn === 'semestre',
              'sort-asc': sortColumn === 'semestre' && sortOrder === 'asc',
              'sort-desc': sortColumn === 'semestre' && sortOrder === 'desc'
            }"
          >
            Semestre
          </th>
          <th
            class="fixed-column filterable-column"
            @click="sortByColumn('matiere')"
            :class="{
              'sort-active': sortColumn === 'matiere',
              'sort-asc': sortColumn === 'matiere' && sortOrder === 'asc',
              'sort-desc': sortColumn === 'matiere' && sortOrder === 'desc'
            }"
          >
            Matière
          </th>
          <th
            class="fixed-column filterable-column"
            @click="sortByColumn('professeur')"
            :class="{
              'sort-active': sortColumn === 'professeur',
              'sort-asc': sortColumn === 'professeur' && sortOrder === 'asc',
              'sort-desc': sortColumn === 'professeur' && sortOrder === 'desc'
            }"
          >
            Professeur
          </th>
          <th class="fixed-column" style="width: 50px">nb CM</th>
          <th class="fixed-column" style="width: 50px">nb TD</th>
          <th class="fixed-column">gr TD</th>
          <th class="fixed-column" style="width: 50px">nb TP</th>
          <th class="fixed-column">gr TP</th>
          <th class="fixed-column">Etat</th>
          <th v-for="week in weeks" :key="week.semaineFormation">
            Semaine {{ week.semaineFormation }} ({{ week.semaineReelle}})<br />
            {{ formatDateCourt(week.dateLundi) }}

          </th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="(row, index) in filteredProgressions" :key="index">
          <td class="fixed-column">
            <select v-model="row.semestre" @change="updateProgression(row)">
              <option value=""></option>
              <option
                :value="semestre['@id']"
                v-for="semestre in semestres"
                :key="semestre.id"
              >
                {{ semestre.libelle }}
              </option>
            </select>
          </td>
          <td class="fixed-column">
            <select
              v-model="row.matiere"
              :disabled="!row.semestre"
              @change="updateProgression(row)"
            >
              <option value=""></option>
              <option
                :value="matiere['@id']"
                v-for="matiere in filteredMatieres(row.semestre)"
                :key="matiere.code"
              >
                {{ matiere.code }}
              </option>
            </select>
          </td>
          <td class="fixed-column">
            <select v-model="row.personnel" @change="updateProgression(row)">
              <option value=""></option>
              <option
                :value="professor.personnel['@id']"
                v-for="professor in personnels"
                :key="professor.personnel.initiales"
              >
                {{ professor.personnel.initiales }}
              </option>
            </select>
          </td>
          <td class="fixed-column" style="width: 50px">
            <input type="number" v-model.lazy="row.nbCm" @change="updateProgression(row)" />
          </td>
          <td class="fixed-column" style="width: 100px">
            <input type="number" v-model.lazy="row.nbTd" @change="updateProgression(row)" />
          </td>
          <td class="fixed-column">
            <input type="text" v-model.lazy="row.grTd" @change="updateProgression(row)" />
          </td>
          <td class="fixed-column" style="width: 100px">
            <input type="number" v-model.lazy="row.nbTp" @change="updateProgression(row)" />
          </td>
          <td class="fixed-column">
            <input type="text" v-model.lazy="row.grTp" @change="updateProgression(row)" />
          </td>
          <td class="fixed-column">
            <Tag v-if="isOkRow(row)" severity="success" value="OK"></Tag>
            <Tag v-else severity="danger" value="KO"></Tag>
          </td>
          <td
            v-for="week in weeks"
            :key="week.semaineFormation"
            :class="{ 'restricted-cell': isWeekRestricted(row.semestre, week) }"
          >
            <input
              type="text"
              v-model.lazy="row.progression[week.semaineFormation]"
              v-if="!isWeekRestricted(row.semestre, week)"
              @input="toUpperCase(row, week.semaineFormation)"
              @change="updateProgression(row)"
            />
          </td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td class="fixed-column"></td>
          <td class="fixed-column"></td>
          <td class="fixed-column"></td>
          <td class="fixed-column">Total</td>
          <td class="fixed-column">{{ sumColumn('Cm') }}</td>
          <td class="fixed-column">{{ sumColumn('Td') }}</td>
          <td class="fixed-column"></td>
          <td class="fixed-column">{{ sumColumn('Tp') }}</td>
          <td class="fixed-column"></td>
          <td class="fixed-column"></td>
          <td v-for="week in weeks" :key="week.week"></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div class="row mt-2">
    <Button label="Générer les créneaux" icon="pi pi-check" @click="generateSlots" />
  </div>
</template>


<style scoped>
.table-container {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  border: 1px solid #ccc;
  padding: 1px;
  text-align: center;
}

.fixed-column {
  width: 100px;
  background: white;
}

.restricted-cell {
  background-color: #ffcccc; /* Light red color for restricted cells */
}

.filterable-column {
  background-color: #e0f7fa; /* Light blue color for filterable columns */
}

.sort-active {
  font-weight: bold;
}

.sort-asc::after {
  content: ' ▲';
}

.sort-desc::after {
  content: ' ▼';
}
</style>
