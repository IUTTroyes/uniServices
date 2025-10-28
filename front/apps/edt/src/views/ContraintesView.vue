<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import SelectWeek from '@/components/SelectWeek.vue'
import Alert from '@components/components/Alert.vue'
import Card from '@components/components/Card.vue'
import { useWeeksStore } from '@/stores/weeksStore.js'
import api from '@helpers/axios.js'
import { getPersonnelsService } from '@requests/user_services/personnelService.js'
import { formatDateCourt } from '@helpers/date.js'

const calendrierGeneral = ref(false)
const isProfesseurContraintes = computed(() => !calendrierGeneral.value) ?? true
const personnels = ref([])
const selectedProfessor = ref(null)
const selectedWeek = ref(null)
const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi']
const timeSlots = ref(['8h00', '9h30', '11h00', '14h00', '15h30', '17h00'])
const constraints = ref(null)
const weeks = ref([]) // This will hold the available weeks
const showConfigPanel = ref(false)
const selectedCell = ref('') // Track the selected cell
const configDetails = ref({
  day: '',
  time: '',
  week: '',
  weeks: [],
  type: 'mandatory',
  duration: 'slot'
})

const weeksStore = useWeeksStore()

const handleWeekUpdate = (week) => {
  selectedWeek.value = week
}

const isSelectionValid = computed(() => selectedWeek.value)

const availableCount = ref(0)
const mandatoryCount = ref(0)
const optionalCount = ref(0)

onMounted(async () => {
  const departementId = localStorage.getItem('departement')
  const params = {
    departement: departementId
  }
  personnels.value = await getPersonnelsService(params)
  await weeksStore.fetchWeeks()
  weeks.value = weeksStore.weeks['member']
  selectedWeek.value = weeks.value[0]
  updateCounts()
})

const updateCounts = () => {
  mandatoryCount.value = 0
  optionalCount.value = 0
  console.log(constraints.value)
  if (!constraints.value) {
    availableCount.value = days.length * timeSlots.value.length
    return
  }

  Object.values(constraints.value).forEach((value) => {
    if (value.type === 'mandatory') {
      mandatoryCount.value++
    } else if (value.type === 'optional') {
      optionalCount.value++
    }
  })

  availableCount.value = (days.length * timeSlots.value.length) - mandatoryCount.value - optionalCount.value
}

const toggleConstraint = (day, time) => {
  configDetails.value.day = day
  configDetails.value.time = time
  configDetails.value.week = selectedWeek.value
  configDetails.value.weeks = []
  selectedCell.value = `${day}_${time}` // Set the selected cell
  showConfigPanel.value = true
}

const saveConstraint = () => {
  const key = `${configDetails.value.day}_${configDetails.value.time}`
  constraints.value[key] = {
    type: configDetails.value.type,
    duration: configDetails.value.duration,
    week: configDetails.value.week,
    weeks: configDetails.value.weeks
  }

  console.log(constraints.value[key])
  showConfigPanel.value = false
  updateCounts()
}

const fetchConstraints = async (professorId, week) => {
  try {
    constraints.value = {}
    const response = await api.get(`/api/edt/personnels-contraintes/${selectedWeek.value.semaineFormation}?personnel=${professorId}`)
    const data = response.data
    constraints.value = data.contraintes

    updateCounts()
  } catch (error) {
    console.error('Error fetching constraints:', error)
  }
}

const toggleAllWeeks = () => {
  if (configDetails.value.weeks.length === Object.values(weeks.value).length) {
    configDetails.value.weeks = []
  } else {
    configDetails.value.weeks = Object.values(weeks.value).map(week => week.id)
  }
}

watch([selectedProfessor, selectedWeek], ([newProfessor, newWeek]) => {
  if (newProfessor && newWeek) {
    fetchConstraints(newProfessor.personnel.id, newWeek.semaineFormation)
  } else {
    constraints.value = {}
    updateCounts()
  }
})

watch([selectedWeek], ([newWeek]) => {
  if (newWeek) {
    fetchConstraints(selectedProfessor.value ? selectedProfessor.value.personnel.id : null, newWeek.semaineFormation)
  } else {
    constraints.value = {}
    updateCounts()
  }
})

const displayJour = (day) => {
  // affiche le jour à partir de la date 2025-03-12 en Lundi 12/03 par exemple
  const date = new Date(day)
  return days[date.getDay() - 1] + ' ' + date.toLocaleDateString('fr-FR').slice(0, -5)
}

const changeCalendrierGeneral = () => {
  if (calendrierGeneral.value) {
    selectedProfessor.value = ''
    if (selectedWeek.value) {
      fetchConstraints(null, selectedWeek.value.semaineFormation)
    } else {
      constraints.value = {}
      updateCounts()
    }

  }
}

</script>

<template>
  <div>
    <Card
        title="Contraintes de disponibilité"
    >
      <div class="row">
        <div class="col-6 p-4">
          <div class="flex flex-col gap-2">
            <label for="professor-select">Choisir un professeur :</label>
            <Select class="form-select d-block" v-model="selectedProfessor"
                    :disabled="!isProfesseurContraintes"
                    :options="personnels"
                    optionLabel="personnel.display">
            </Select>
          </div>
        </div>

        <div class="col-6 p-4">
          <div class="flex flex-col gap-2">
            <SelectWeek @update:selectedWeek="handleWeekUpdate" :current-week="selectedWeek"/>
          </div>
        </div>
      </div>
    </Card>
{{selectedProfessor}} - {{selectedWeek}}
    <Card
        :title="`Saisir les contraintes de disponibilité pour ${selectedProfessor?.personnel.display}, semaine ${selectedWeek?.semaineFormation}`"
    >
      <Alert v-if="!isSelectionValid"
             severity="warn">
        Veuillez sélectionner un professeur et une semaine pour afficher la grille.
      </Alert>
      <div v-else>
        <div class="row mt-2">
          <div class="col-4">
            <p>Créneaux disponibles: {{ availableCount }}</p>
          </div>
          <div class="col-4">
            <p>Indisponible strict: {{ mandatoryCount }}</p>
          </div>
          <div class="col-4">
            <p>Indisponible facultatif: {{ optionalCount }}</p>
          </div>
        </div>
        <div class="grid-container">
          <div class="grid-header"></div>
          <div v-for="day in selectedWeek.jours" :key="day" class="grid-header">{{ displayJour(day) }}</div>
          <div v-for="time in timeSlots" :key="time" class="grid-row">
            <div class="grid-time">{{ time }}</div>
            <div
                v-for="day in days"
                :key="day"
                :class="[
              'grid-cell',
              constraints[`${day}_${time}`]?.type,
              { selected: selectedCell === `${day}_${time}` }
            ]"
                @click="toggleConstraint(day, time)"
            >
              <span v-if="constraints[`${day}_${time}`]?.type === 'mandatory'">Indispo. strict</span>
              <span v-if="constraints[`${day}_${time}`]?.type === 'optional'"
              >Indispo. facultatif</span
              >
            </div>
          </div>
        </div>

        <div v-if="showConfigPanel" class="config-panel">
          <h3>Configurer la contrainte</h3>
          <p>Semaine courante: {{ configDetails.week.semaineFormation }} - {{ configDetails.week.semaineReelle }}</p>
          <Button @click="toggleAllWeeks" severity="info">Cocher/Décocher toutes les semaines</Button>

          <div class="row">
            Semaines :
            <div v-for="week in weeks" :key="week.week" class="col-3">
              <input
                  type="checkbox"
                  :id="`week-${week.id}`"
                  :value="week.semaineFormation"
                  v-model="configDetails.weeks"
              />
              <label :for="`week-${week.id}`">Semaine {{ week.semaineFormation }}
                ({{ formatDateCourt(week.dateLundi) }})</label>
            </div>
          </div>
          <p>Jour: {{ configDetails.day }}</p>
          <p>Créneau: {{ configDetails.time }}</p>
          <div class="flex flex-col gap-2">
            <label for="cheese">Indiquez le type de contrainte</label>
            <RadioButtonGroup name="ingredient" class="flex flex-wrap gap-4">
              <div class="flex items-center gap-2">
                <RadioButton inputId="mandatory" value="mandatory"/>
                <label for="mandatory">Obligatoire</label>
              </div>
              <div class="flex items-center gap-2">
                <RadioButton inputId="optional" value="optional"/>
                <label for="optional">Facultatif</label>
              </div>
            </RadioButtonGroup>
          </div>

          <label for="duration-select">Durée:</label>
          <select v-model="configDetails.duration">
            <option value="slot">Créneau ({{ configDetails.time }})</option>
            <option value="half-day">Demi-journée (matin)</option>
            <option value="half-day">Demi-journée (après-midi)</option>
            <option value="full-day">Journée entière</option>
          </select>
          <Button
              class="mt-2"
              severity="primary"
              @click="saveConstraint"
          >Enregistrer
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<style scoped>
.grid-container {
  display: grid;
  grid-template-columns: 100px repeat(5, 1fr);
  gap: 0;
  width: 100%;
  border: 1px solid #000;
}

.grid-header {
  background-color: #f2f2f2;
  text-align: center;
  padding: 8px;
  font-weight: bold;
  border: 1px solid #000;
  grid-column: span 1;
}

.grid-time {
  background-color: #e19797;
  text-align: center;
  font-weight: bold;
  border: 1px solid #000;
  grid-column: span 1;
}

.grid-row {
  display: contents;
}

.grid-cell {
  text-align: center;
  padding: 3px;
  border: 1px solid #000;
  cursor: pointer;
  height: 30px;
}

.grid-cell.mandatory {
  background-color: #ffcccc; /* Rouge pour les contraintes obligatoires */
}

.grid-cell.optional {
  background-color: #ffffcc; /* Jaune pour les contraintes facultatives */
}

.grid-cell.selected {
  border: 2px solid #0000ff; /* Blue border for selected cell */
}

.config-panel {
  margin-top: 20px;
  padding: 10px;
  border: 1px solid #000;
  background-color: #f9f9f9;
}

.warning {
  color: red;
  font-weight: bold;
}
</style>
