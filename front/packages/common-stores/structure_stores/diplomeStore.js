import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getDiplomesActifsDepartementService } from '@requests'

export const useDiplomeStore = defineStore('diplome', () => {

  const diplomes = ref({});

  const getDiplomesActifsDepartement = async (departementId) => {
    try {
      diplomes.value = await getDiplomesActifsDepartementService(departementId);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getDiplomesActifsDepartement,
    diplomes
  };
})
