import api from '@helpers/axios';

const getEtudiantScolariteActifService = async (etudiant, anneeUniv) => {
    const response = await api.get(`/api/etudiant_scolarites/etudiant/${etudiant}/structureAnneeUniversitaire/${anneeUniv}`);
    return response.data;
}

const getEtudiantsDepartementService = async (departement, anneeUniv, limit, page) => {
    const response = await api.get(`/api/etudiants?departement=${departement}&anneeUniversitaire=${anneeUniv}&page=${page}&itemsPerPage=${limit}`);
    return response.data;
}

export { getEtudiantScolariteActifService, getEtudiantsDepartementService };
