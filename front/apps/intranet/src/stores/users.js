import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/axios';

const token = localStorage.getItem('token');
const tokenParts = token?.split('.');
const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
const userId = payload.userId;
const type = payload.type;

export const useUsersStore = defineStore('users', () => {
    const user = ref([]);

    const fetchUser = async () => {
        try {
            const response = await api.get(`/api/${type}/${userId}`);
            // transformer user.photo_name en chemin vers l'image : "@/assets/photos_etudiants/" + user.photo_name
            response.data.photo_name = "http://localhost:3001/intranet/src/assets/photos_etudiants/" + response.data.photo_name;
            user.value = response.data;
        } catch (error) {
            console.error('Error fetching user:', error);
        }
    };

    return {
        user,
        fetchUser
    };
});
