import api from '@helpers/axios';

const getUserService = async (type, id) => {
    const response = await api.get(`/api/${type}/${id}`)
    return response.data;
}

export { getUserService };
