import api from '@helpers/axios';
export default {
    async getAll() {
        const response = await api.get('/api/structure_type_diplomes');
        return response.data;
    },
    async get(id) {
        const response = await api.get(`/api/structure_type_diplomes/${id}`);
        return response.data;
    },
    async create(data) {
        const response = await api.post('/api/structure_type_diplomes', data);
        return response.data;
    },
    async update(id, data) {
        const response = await api.put(`/api/structure_type_diplomes/${id}`, data);
        return response.data;
    },
    async delete(id) {
        const response = await api.delete(`/api/structure/type-diplomes/${id}`);
        return response.data;
    }
};


