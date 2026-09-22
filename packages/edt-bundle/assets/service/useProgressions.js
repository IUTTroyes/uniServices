import { ref } from 'vue'
import api from '@helpers/axios.js'

export default function useProgressions () {
  const progressions = ref([])

  const fetchProgressions = async () => {
    console.log('fetching progressions')
    try {
      const departementId = localStorage.getItem('departement')
      const response = await api.get(`/api/previsionnels/?departement=${departementId}`)
      progressions.value = response.data['member']
    } catch (error) {
      console.error('Error fetching progressions:', error)
    }
  }

  const updateProgression = async (progression) => {
    console.log('updating progression')
    console.log(progression)
    try {
      await api.put(`/api/edt_progressions/${progression.progression.id}`, progression.progression, { headers: { 'Content-Type': 'application/ld+json' } })
    } catch (error) {
      console.error('Error updating progression:', error)
    }
  }

  const deleteProgression = async (progression) => {
    console.log('deleting progression')
    try {
      await api.delete(`/api/edt_progressions/${progression.id}`)
      progressions.value = progressions.value.filter(p => p.id !== progression.id)
    } catch (error) {
      console.error('Error deleting progression:', error)
    }
  }

  return {
    progressions,
    fetchProgressions,
    updateProgression,
    deleteProgression
  }
}
