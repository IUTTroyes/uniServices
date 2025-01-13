import api from '@helpers/axios';

const getEtudiantScolariteActif = async (etudiantId) => {
    const response = await api.get(`/api/etudiant_scolarites/by_etudiant/${etudiantId}?actif=true`);
    return response.data['member'];
}

export { getEtudiantScolariteActif };
