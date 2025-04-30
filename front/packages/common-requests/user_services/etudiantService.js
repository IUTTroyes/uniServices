import api from '@helpers/axios';

const getEtudiantScolariteActifService = async (etudiant, anneeUniv) => {
    const response = await api.get(`/api/etudiant_scolarites/etudiant/${etudiant}/structureAnneeUniversitaire/${anneeUniv}`);
    return response.data;
}

const updateEtudiant = async (etudiant) => {
    const response = await api.put(`/api/etudiants/${etudiant.id}`, etudiant);
    return response.data;
}

export { updateEtudiant, getEtudiantScolariteActifService };
