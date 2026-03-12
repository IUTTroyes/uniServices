import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getDiplomesService } from '@requests'

export const useDiplomeStore = defineStore('diplome', () => {

  const diplomes = ref({});

  const getDiplomesDepartement = async (params) => {
    try {
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
