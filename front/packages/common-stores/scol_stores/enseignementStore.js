// src/stores/enseignementStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@helpers/axios';
import { getAllEnseignementsService, getEnseignementsSemestreService } from "@requests";

export const useEnseignementsStore = defineStore('enseignements', () => {
  const enseignements = ref([])

  const getMatieres = async () => {
    try {
      const response = await getAllEnseignementsService();
      enseignements.value = response.data
    } catch (error) {
      console.error('Error fetching enseignements:', error)
    }
  }

  const getMatieresSemestre = async (semestreId) => {
    try {
      enseignements.value = await getEnseignementsSemestreService(semestreId);
    } catch (error) {
      console.error('Error fetching enseignements:', error)
    }
  }

  // const addMatiere = async (matiere) => {
  //   try {
  //     const response = await apit.post('/api/enseignements', {
  //       method: 'POST',
  //       headers: {
  //         'Content-Type': 'application/ld+json'
  //       },
  //       body: JSON.stringify(matiere)
  //     }).then((res) => res.json())
  //     console.log(response)
  //     enseignements.value.push(response.data)
  //   } catch (error) {
  //     console.error('Error adding matiere:', error)
  //   }
  // }
  //
  // const deleteMatiere = async (id) => {
  //   try {
  //     await fetch(baseUrl + `/api/enseignements/${id}`, {
  //       method: 'DELETE'
  //     })
  //     enseignements.value = enseignements.value.filter((prof) => prof.id !== id)
  //   } catch (error) {
  //     console.error('Error deleting matiere:', error)
  //   }
  // }
  //
  // const updateMatiere = async (matiere) => {
  //   try {
  //     const response = await fetch(`/api/enseignements/${matiere.id}`, matiere)
  //     const index = enseignements.value.findIndex((prof) => prof.id === matiere.id)
  //     if (index !== -1) {
  //       enseignements.value[index] = response.data
  //     }
  //   } catch (error) {
  //     console.error('Error updating matiere:', error)
  //   }
  // }

  return {
    enseignements,
    getMatieres,
    getMatieresSemestre,
    // addMatiere,
    // deleteMatiere,
    // updateMatiere
  }
})
