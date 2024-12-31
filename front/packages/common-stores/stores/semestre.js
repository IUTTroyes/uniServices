import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@helpers/axios';

export const useSemestreStore = defineStore('semestre', () => {

  const semestre = ref({});
  const semestres = ref({});
  const getSemestre = async (semestreId) => {
    try {
      const response = await api.get(`/api/structure_semestres/${semestreId}`);
      semestre.value = response.data;

      return semestre.value
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestres = async () => {
    try {
      const response = await api.get(`/api/structure_semestres/`);
      semestres.value = response.data;

      return semestres.value
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestresByDiplome = async (diplomeId, onlyActif = true) => {
    try {
      const response = await api.get(`/api/structure_semestres/`);
      semestres.value = await response.data;
      return semestres.value
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestresByDepartement = async (departementId, onlyActif = true) => {
    try {
      const response = await api.get(`/api/structure_semestres/`);
      semestres.value = await response.data;

      return semestres.value
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getSemestre,
    getSemestres,
    semestre,
    semestres
  };
})
