import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getReferentielCompetencesComplet =  (async (referentielId, showToast = false) => {
  try {
    const response = await apiCall(
      api.get,
      [`/api/apc_competences-referentiel?referentiel=${referentielId}`],
      'Référentiel de compétencess du diplôme récupéré avec succès',
      'Erreur lors de la récupération du référentiel de compétences',
      showToast
    );
    return await response.member;
  } catch (error) {
    console.error('Erreur dans getReferentielCompetencesComplet:', error);
    throw error;
  }
})

const getReferentiels =  (async (departementId, showToast = false) => {
  console.log(departementId)
  try {
    const response = await apiCall(
      api.get,
      [`/api/apc_referentiels?departement=${departementId}`],
      'Référentiels de compétences du département récupérés avec succès',
      'Erreur lors de la récupération des référentiels de compétences',
      showToast
    );
    return await response.member;
  } catch (error) {
    console.error('Erreur dans getReferentiels:', error);
    throw error;
  }
})

export { getReferentielCompetencesComplet, getReferentiels };
