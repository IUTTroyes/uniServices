import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@helpers/axios';
import { getServiceSemestre, getServiceSemestres, getServiceDepartementSemestres } from "@requests";

export const useSemestreStore = defineStore('semestre', () => {

  const semestre = ref({});
  const semestres = ref({});

  const getSemestre = async (semestreId) => {
    try {
      semestre.value = await getServiceSemestre(semestreId);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestres = async () => {
    try {
      semestres.value = await getServiceSemestres();
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestresByDiplome = async (diplomeId, onlyActif = true) => {
    try {
      const response = await api.get(`/api/structure_semestres?diplome=${diplomeId}`);
      semestres.value = await response.data;
      return semestres.value
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestresByDepartement = async (departementId, onlyActif) => {
    try {
      semestres.value = await getServiceDepartementSemestres(departementId, onlyActif);
      console.log(semestres.value);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getSemestre,
    getSemestres,
    getSemestresByDepartement,
    getSemestresByDiplome,
    semestre,
    semestres
  };
})
