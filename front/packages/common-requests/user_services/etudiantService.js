import api from '@helpers/axios';

const getEtudiantScolariteActif = async (etudiant, anneeUniv) => {
    const response = await api.get(`/api/etudiant_scolarites/etudiant/${etudiant}/structureAnneeUniversitaire/${anneeUniv}`);
    return response.data;
}

export { getEtudiantScolariteActif };
