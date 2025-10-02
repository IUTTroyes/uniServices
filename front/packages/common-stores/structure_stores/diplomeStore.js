import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getDiplomesService } from '@requests'

export const useDiplomeStore = defineStore('diplome', () => {

  const diplomes = ref({});

  const getDiplomesDepartement = async (departementId) => {
    try {
      const params = {
        departement: departementId,
      };
      diplomes.value = await getDiplomesService(params, '/maxi');
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getDiplomesDepartement,
    diplomes
  };
})
