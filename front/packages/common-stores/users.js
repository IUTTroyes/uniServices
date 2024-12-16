import { defineStore } from 'pinia';
import {ref} from 'vue';
import api from '@/axios';

export const useUsersStore = defineStore('users', () => {
    const token = localStorage.getItem('token');
    const tokenParts = token?.split('.');
    const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
    const userId = payload.userId;
    const userType = payload.type;
    const user = ref([]);
    const departements = ref([]);
    const departementDefaut = ref({});
    const departementsNotDefaut = ref({});

    const fetchUser = async () => {
        try {
            const response = await api.get(`/api/${userType}/${userId}`);
            // transformer user.photoName en chemin vers l'image : "@/assets/photos_etudiants/" + user.photoName
            response.data.photoName = "http://localhost:3001/intranet/src/assets/photos_etudiants/" + response.data.photoName;
            user.value = response.data;

            if (userType === 'personnels') {
                departements.value = response.data.structureDepartementPersonnels;

                //si il n'y a pas de département qui a defaut = true
                if (!departements.value.find(departement => departement.defaut === true)) {
                    // mettre le premier département par défaut
                    const response = await api.post(`/api/structure_departement_personnels/${departements.value[0].id}/change_departement`, {}, {
                        headers: {
                            'Content-Type': 'application/ld+json'
                        }
                    });
                    departements.value = response.data;
                }

                // récupérer le département qui a defaut = true
                departementDefaut.value = departements.value.find(departement => departement.defaut === true);
                localStorage.setItem('departement', departementDefaut.value.id);
                // récupérer les départements qui n'ont pas defaut = true
                departementsNotDefaut.value = departements.value.filter(departement => departement.defaut === false);
            }
        } catch (error) {
            console.error('Error fetching user:', error);
        }
    };

    const changeDepartement = async (departementId) => {
        try {
            const response = await api.post(`/api/structure_departement_personnels/${departementId}/change_departement`, {
            }, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            });
            departements.value = response.data;

            // récupérer le département qui a defaut = true
            departementDefaut.value = departements.value.find(departement => departement.defaut === true);
            // récupérer les départements qui n'ont pas defaut = true
            departementsNotDefaut.value = departements.value.filter(departement => departement.defaut === false);
        } catch (error) {
            console.error('Error changing department:', error);
        }
    };

    return {
        user,
        userType,
        departements,
        departementDefaut,
        departementsNotDefaut,
        fetchUser,
        changeDepartement
    };
});
