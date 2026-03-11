import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getDiplomesService } from '@requests'

export const useDiplomeStore = defineStore('diplome', () => {

  const diplomes = ref({});

  const getDiplomesDepartement = async (departementId, anneeUniversitaireId = null) => {
    try {
      const params = {
        departement: departementId,
      };
      if (anneeUniversitaireId) {
        params.anneeUniversitaire = anneeUniversitaireId;
      }
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
