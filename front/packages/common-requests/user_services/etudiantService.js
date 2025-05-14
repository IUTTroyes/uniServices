import api from '@helpers/axios';

const getEtudiantScolariteActifService = async (etudiant, anneeUniv) => {
    const response = await api.get(`/api/etudiant_scolarites/etudiant/${etudiant}/structureAnneeUniversitaire/${anneeUniv}`);
    return response.data;
}

const updateEtudiant = async (etudiant) => {
    const response = await api.patch(`/api/etudiants/${etudiant.id}`, etudiant, {
        headers: {
            'Content-Type': 'application/merge-patch+json',
        },
    });
    return response.data;
}

export { updateEtudiant, getEtudiantScolariteActifService };
