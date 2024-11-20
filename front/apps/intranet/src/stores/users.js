import { defineStore } from 'pinia'
import { ref } from 'vue'

const token = document.cookie.split('; ').find(row => row.startsWith('token'))?.split('=')[1];
if (token) localStorage.setItem('token', token);

const tokenParts = token?.split('.');
const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
const userId = payload.userId;
const type = payload.type;

export const useUsersStore = defineStore('users', () => {
    const user = ref([])
    // récupérer DB_URL dans le .env
    const baseUrl = import.meta.env.VITE_BASE_URL

    const fetchUser = async () => {
        try {
            const response = await fetch(baseUrl + '/api/' + type + '/' + userId).then((res) => res.json())
            console.log(response)

            user.value = response

        } catch (error) {
            console.error('Error fetching user:', error)
        }
    }

    return {
        user,
        fetchUser
    }
})
