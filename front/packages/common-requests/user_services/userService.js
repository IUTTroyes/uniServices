import api from '@helpers/axios';

const getUserService = async (type, id) => {
    const response = await api.get(`/api/${type}/${id}`)
    return response.data;
}

const updateUserService = async (type, id, data) => {
    const response = await api.patch(`/api/${type}/${id}`, data, {
        headers: {
            'Content-Type': 'application/merge-patch+json'
        }
    });
    return response.data;
}

const getAllStatutsService = async () => {
    const response = await api.get('/api/statuts');
    return response.data;
}

export { getUserService, updateUserService, getAllStatutsService };
