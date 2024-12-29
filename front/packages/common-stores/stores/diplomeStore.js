import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getServiceDepartementDiplomesActifs } from '@requests'

export const useDiplomeStore = defineStore('diplome', () => {

  const diplomes = ref({});

  const getDepartementDiplomesActifs = async (departementId) => {
    try {
      return await getServiceDepartementDiplomesActifs(departementId);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getDepartementDiplomesActifs,
    diplomes
  };
})
