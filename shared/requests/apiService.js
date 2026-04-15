// src/services/apiService.js
import api from '@helpers/axios'

export default function createApiService(baseUrl) {
  return {
    async getAll() {
      return await api.get(baseUrl)
    },
    async get(id) {
      return await api.get(`${baseUrl}/${id}`)
    },
    async create(data) {
      return await api.post(baseUrl, data, { headers: { 'Content-Type': 'application/ld+json' } })
    },
    async update(id, data) {
      return await api.patch(`${baseUrl}/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } })
    },
    async delete(id) {
      return await api.delete(`${baseUrl}/${id}`)
    }
  }
}
