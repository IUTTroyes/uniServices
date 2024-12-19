import api from 'common-helpers/axios';

const getServiceSemestres = async () => {
    const response = await api.get(`/api/structure_semestres`);
    return response.data['member'];
}

const getServiceDepartementSemestresActifs = async (departementId) => {

}

export { getServiceSemestres, getServiceDepartementSemestresActifs };
