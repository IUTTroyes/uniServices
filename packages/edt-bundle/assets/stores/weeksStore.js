// src/stores/enseignementStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@helpers/axios';

export const useWeeksStore = defineStore('weeks', () => {
  const weeks = ref([])

  const fetchWeeks = async () => {
    console.log('fetching weeks')
    try {
      const response = await api.get('/api/structure_calendriers')
      weeks.value = response.data
    } catch (error) {
      console.error('Error fetching weeks:', error)
    }
  }

  return {
    weeks,
    fetchWeeks,
  }
})
