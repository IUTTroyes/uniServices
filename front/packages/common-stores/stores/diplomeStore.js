import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getServiceDepartementDiplomesActifs } from 'common-requests'

export const useDiplomeStore = defineStore('diplome', () => {

  const diplomes = ref({});

  const getDepartementDiplomesActifs = async (departementId) => {
    try {
      const diplomes = await getServiceDepartementDiplomesActifs(departementId);
      return diplomes.value
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getDepartementDiplomesActifs,
    diplomes
  };
})
