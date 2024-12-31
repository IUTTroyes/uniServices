// src/stores/professeurs.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@helpers/axios';

export const useProfesseursStore = defineStore('professeurs', () => {
  const professeurs = ref([])

    const getProfesseurs = async () => {
      try {
        const response = await api.get('/api/personnels')
        professeurs.value = response.data['member']
      } catch (error) {
        console.error('Error fetching professeurs:', error)
      }
    }


//   const addProfesseur = async (professor) => {
//     try {
//       const response = await api.post('/api/personnels', {
//         headers: {
//           'Content-Type': 'application/ld+json'
//         },
//         body: JSON.stringify(professor)
//       }).then((res) => res.json())
// console.log(response)
//       professeurs.value.push(response.data)
//     } catch (error) {
//       console.error('Error adding professor:', error)
//     }
//   }
//
//   const deleteProfesseur = async (id) => {
//     try {
//       await api.delete(`/api/professeurs/${id}`)
//       professeurs.value = professeurs.value.filter((prof) => prof.id !== id)
//     } catch (error) {
//       console.error('Error deleting professor:', error)
//     }
//   }
//
//   const updateProfesseur = async (professor) => {
//     try {
//       const response = await fetch(`/api/professeurs/${professor.id}`, professor)
//       const index = professeurs.value.findIndex((prof) => prof.id === professor.id)
//       if (index !== -1) {
//         professeurs.value[index] = response.data
//       }
//     } catch (error) {
//       console.error('Error updating professor:', error)
//     }
//   }

  return {
    professeurs,
    getProfesseurs,
    // addProfesseur,
    // deleteProfesseur,
    // updateProfesseur
  }
})
