import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getDepartementDiplomesService } from '@requests'

export const useDiplomeStore = defineStore('diplome', () => {

  const diplomes = ref({});

  const getDiplomesDepartement = async (departementId) => {
    try {
      diplomes.value = await getDepartementDiplomesService(departementId);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getDiplomesDepartement,
    diplomes
  };
})
