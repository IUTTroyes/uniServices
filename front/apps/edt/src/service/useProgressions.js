import { ref } from 'vue'
import api from '@helpers/axios.js'

export default function useProgressions () {
  const progressions = ref([])

  const fetchProgressions = async () => {
    console.log('fetching progressions')
    try {
      const response = await api.get('/api/edt_progressions')
      progressions.value = response.data['member']
    } catch (error) {
      console.error('Error fetching progressions:', error)
    }
  }

  const addProgression = async (progression) => {
    console.log('adding progression')
    try {
      const response = await api.post(
        '/api/edt_progressions',
        progression,
        { headers: { 'Content-Type': 'application/ld+json' } })
      progressions.value.push(response.data)
    } catch (error) {
      console.error('Error adding progression:', error)
    }
  }

  const duplicateProgression = async (progression) => {
    try {
      const response = await api.post(
        `/api/edt_progressions/${progression.id}/duplicate`,
        {},)
      progressions.value.push(response.data)
    } catch (error) {
      console.error('Error adding progression:', error)
    }
  }

  const updateProgression = async (progression) => {
    console.log('updating progression')
    try {
      await api.put(`/api/edt_progressions/${progression.id}`, progression)
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
    addProgression,
    updateProgression,
    deleteProgression
  }
}
