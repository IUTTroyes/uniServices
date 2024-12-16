import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/axios';

export const useSemestreStore = defineStore('semestre', () => {

  const semestre = ref({});
  const getSemestre = async (semestreId) => {
    try {
      const response = await api.get(`/api/structure_semestres/${semestreId}`);
      semestre.value = response.data;

      return semestre.value
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getSemestre,
    semestre
  };
})
