import { defineStore } from 'pinia'
import { ref } from 'vue'

const token = document.cookie.split('; ').find(row => row.startsWith('token'))?.split('=')[1];
console.log(token)
if (token) localStorage.setItem('token', token);

const tokenParts = token?.split('.');
const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
const userId = payload.user_id;
const type = payload.type;

export const useUsersStore = defineStore('users', () => {
    const users = ref([])
    const baseUrl = import.meta.env.DB_URL

    const fetchUser = async () => {
        try {
            const response = await fetch(baseUrl + '/api/' + type + '/' + userId).then((res) => res.json())
            console.log(response)
            matieres.value = response['member']
        } catch (error) {
            console.error('Error fetching matieres:', error)
        }
    }

    return {
        users,
        fetchUser
    }
})
