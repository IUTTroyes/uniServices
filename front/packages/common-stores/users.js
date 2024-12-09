import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/axios';

export const useUsersStore = defineStore('users', () => {
    const token = localStorage.getItem('token');
    const tokenParts = token?.split('.');
    const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
    const userId = payload.userId;
    const type = payload.type;
    const user = ref([]);
    const departements = ref([]);
    const departementDefaut = ref({});

    const fetchUser = async () => {
        try {
            const response = await api.get(`/api/${type}/${userId}`);
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

    const changeDepartement = async (departementId) => {
        try {
            // récupérer les départements du personnel
            const response = await api.get(`/api/structure_departement_personnels/by_personnel/${user.value.id}`);
            const departementPersonnels = response.data['member'];
            // récupérer le département sélectionné
            const departementPersonnel = departementPersonnels.find(departementPersonnel => departementPersonnel.departement.id === departementId);
            // récupérer le département qui a defaut = true
            const departementDefautCurrent = departementPersonnels.find(departementPersonnel => departementPersonnel.defaut === true);

            // on met à jour le département qui a defaut = true en le passant à false
            if (departementDefautCurrent) {
                await api.patch(`/api/structure_departement_personnels/${departementDefautCurrent.id}`, {
                    defaut: false,
                }, {
                    headers: {
                        'Content-Type': 'application/merge-patch+json'
                    }
                });
            }
            // on met à jour le département sélectionné en le passant à true
            await api.patch(`/api/structure_departement_personnels/${departementPersonnel.id}`, {
                defaut: true,
            }, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            });

            console.log('departementDefaut 1', departementDefaut.value);
            departementDefaut.value = departementPersonnel;
            console.log('departementDefaut 2', departementDefaut.value);
        } catch (error) {
            console.error('Error changing department:', error);
        }
    };

    return {
        user,
        departements,
        departementDefaut,
        fetchUser,
        changeDepartement
    };
});
