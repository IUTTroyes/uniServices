<script setup>
import { onMounted, ref, watch } from 'vue'
import { useWeeksStore } from '@/stores/weeksStore.js'
import { formatDateCourt } from '@helpers/date.js'

const props = defineProps({
  currentWeek: {
    type: Number,
    required: true
  }
})

const weeksStore = useWeeksStore()
const weeks = ref([])
const selectedWeek = ref(1)

onMounted(() => {
  fetchWeeks()
})

const fetchWeeks = async () => {
  try {
    await weeksStore.fetchWeeks()
    weeks.value = weeksStore.weeks
  } catch (error) {
    console.error('Error fetching weeks:', error)
  }
}

const emit = defineEmits(['update:selectedWeek'])

const loadWeek = () => {
  emit('update:selectedWeek', selectedWeek.value)
}

const display = (week) => {
  return week ? `Semaine ${week.semaineFormation} (${formatDateCourt(week.dateLundi)})` : ''
}

watch(() => props.currentWeek, (newWeek) => {
  selectedWeek.value = newWeek
})
</script>

<template>
  <label for="week-select">Choisir une semaine :</label>
  <Select class="form-select d-block"
          v-model="selectedWeek"
          @change="loadWeek"
          :options="weeks['member']"
          :optionLabel="display">
  </Select>
</template>

<style scoped></style>
