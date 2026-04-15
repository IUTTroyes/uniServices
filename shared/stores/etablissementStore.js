import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getEtablissementService} from '@requests'

export const useEtablissementStore = defineStore('etablissement', () => {

  const etablissement = ref(null);

const getEtablissement = async () => {
  try {
    etablissement.value = await getEtablissementService();
  } catch (error) {
    console.error('Error fetching user:', error);
  }
}

  return {
    getEtablissement,
    etablissement,
  };
})
