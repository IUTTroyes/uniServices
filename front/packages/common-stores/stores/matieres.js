// src/stores/matieres.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@helpers/axios';

export const useMatieresStore = defineStore('matieres', () => {
  const matieres = ref([])

  const getMatieres = async () => {
    try {
      const response = await api.get('/api/scol_enseignements')
      matieres.value = response.data['member']
    } catch (error) {
      console.error('Error fetching matieres:', error)
    }
  }


  // const addMatiere = async (matiere) => {
  //   try {
  //     const response = await apit.post('/api/matieres', {
  //       method: 'POST',
  //       headers: {
  //         'Content-Type': 'application/ld+json'
  //       },
  //       body: JSON.stringify(matiere)
  //     }).then((res) => res.json())
  //     console.log(response)
  //     matieres.value.push(response.data)
  //   } catch (error) {
  //     console.error('Error adding matiere:', error)
  //   }
  // }
  //
  // const deleteMatiere = async (id) => {
  //   try {
  //     await fetch(baseUrl + `/api/matieres/${id}`, {
  //       method: 'DELETE'
  //     })
  //     matieres.value = matieres.value.filter((prof) => prof.id !== id)
  //   } catch (error) {
  //     console.error('Error deleting matiere:', error)
  //   }
  // }
  //
  // const updateMatiere = async (matiere) => {
  //   try {
  //     const response = await fetch(`/api/matieres/${matiere.id}`, matiere)
  //     const index = matieres.value.findIndex((prof) => prof.id === matiere.id)
  //     if (index !== -1) {
  //       matieres.value[index] = response.data
  //     }
  //   } catch (error) {
  //     console.error('Error updating matiere:', error)
  //   }
  // }

  return {
    matieres,
    getMatieres,
    // addMatiere,
    // deleteMatiere,
    // updateMatiere
  }
})
