import api from '@helpers/axios';

const getEtudiantScolariteActifService = async (etudiant, anneeUniv) => {
    const response = await api.get(`/api/etudiant_scolarites/etudiant/${etudiant}/structureAnneeUniversitaire/${anneeUniv}`);
    return response.data;
}

const getEtudiantsDepartementService = async (departement, anneeUniv, limit, offset, page) => {
    const response = await api.get(`/api/etudiants?departement=${departement}&anneeUniversitaire=${anneeUniv}&limit=${limit}&offset=${offset}`);
    return response.data;
}

export { getEtudiantScolariteActifService, getEtudiantsDepartementService };
