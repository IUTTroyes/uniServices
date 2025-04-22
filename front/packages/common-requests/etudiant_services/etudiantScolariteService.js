import api from '@helpers/axios';

const getEtudiantsScolaritesDepartementService = async (departement, anneeUniv, limit, page, filters) => {
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
    if (filters.annee.value) {
        params['annee'] = filters.annee.value;
    }

    const response = await api.get(`/api/etudiant_scolarites`, { params });
    return response.data;
}

export { getEtudiantsScolaritesDepartementService };
