import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/axios';

export const useUsersStore = defineStore('users', () => {
    const token = localStorage.getItem('token');
    const tokenParts = token?.split('.');
    const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
    console.log('payload', payload);
    const userId = payload.userId;
    const type = payload.type;
    const user = ref([]);
    const departements = ref([]);
    const departementDefaut = ref({});

    const fetchUser = async () => {
        try {
            const response = await api.get(`/api/${type}/${userId}`);
            console.log('response', response.data);
            // transformer user.photoName en chemin vers l'image : "@/assets/photos_etudiants/" + user.photoName
            response.data.photoName = "http://localhost:3001/intranet/src/assets/photos_etudiants/" + response.data.photoName;
            user.value = response.data;
            departements.value = response.data.structureDepartementPersonnels;
            // récupérer le département qui a defaut = true
            departementDefaut.value = departements.value.find(departement => departement.defaut === true);
        } catch (error) {
            console.error('Error fetching user:', error);
        }
    };

    return {
        user,
        departements,
        departementDefaut,
        fetchUser
    };
});
