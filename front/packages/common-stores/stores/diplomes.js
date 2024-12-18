import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/axios';

export const useDiplomeStore = defineStore('diplome', () => {

  const diplomes = ref({});

  const getDiplomesActifs = async (departementId) => {
    try {
      const response = await api.get(`/api/diplomes-par-departement/${departementId}`);
      diplomes.value = response.data['member']
      // filter diplomes to keep only the active ones
      diplomes.value = diplomes.value.filter(diplome => diplome.actif === true)

      return diplomes.value
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getDiplomesActifs,
    diplomes
  };
})
