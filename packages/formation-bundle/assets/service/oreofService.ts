import api from '@helpers/axios';

const synchronisationProgrameOreofService = async (params: {
    selectedDiplome: string,
    anneeUniversitaire: string,
    selectedAnneeRef: string,
    oreofId: string,
}) => {
    const response = await api.post(`/api/oreof/ref-formation/synchronisation`, { ...params});
    return response.data;
}

export { synchronisationProgrameOreofService };
