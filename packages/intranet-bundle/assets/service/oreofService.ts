import api from '@helpers/axios';

const synchronisationCompetencesOreofService = async (params: {
    selectedDiplome: string,
    anneeUniversitaire: string,
    selectedAnneeRef: string,
    oreofId: string,
}) => {
    const response = await api.post(`/api/oreof/ref-competences/synchronisation`, { params});
    return response.data;
}

const synchronisationProgrameOreofService = async (params: {
    selectedDiplome: string,
    anneeUniversitaire: string,
    selectedAnneeRef: string,
    oreofId: string,
}) => {
    const response = await api.post(`/api/oreof/ref-formation/synchronisation`, { ...params});
    return response.data;
}

export { synchronisationCompetencesOreofService, synchronisationProgrameOreofService };
