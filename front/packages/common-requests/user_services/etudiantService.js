import api from '@helpers/axios';

const getEtudiantScolariteActifService = async (etudiant, anneeUniv) => {
    const response = await api.get(`/api/etudiant_scolarites/etudiant/${etudiant}/structureAnneeUniversitaire/${anneeUniv}`);
    return response.data;
}

const getEtudiantsDepartementService = async (departement, anneeUniv, limit, page, filters) => {
    const params = {
        departement,
        anneeUniversitaire: anneeUniv,
        page,
        itemsPerPage: limit,
    };

    if (filters.nom.value) {
        params['nom'] = filters.nom.value;
    }
    if (filters.prenom.value) {
        params['prenom'] = filters.prenom.value;
    }
    if (filters.mailUniv.value) {
        params['mailUniv'] = filters.mailUniv.value;
    }
    if (filters.semestre.value) {
        params['semestre'] = filters.semestre.value.id;
    }

    const response = await api.get(`/api/etudiants`, { params });
    return response.data;
}

export { getEtudiantScolariteActifService, getEtudiantsDepartementService };
