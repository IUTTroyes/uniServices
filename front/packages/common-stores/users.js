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
    const departementsNotDefaut = ref({});

    const fetchUser = async () => {
        try {
            const response = await api.get(`/api/${type}/${userId}`);
            // transformer user.photoName en chemin vers l'image : "@/assets/photos_etudiants/" + user.photoName
            response.data.photoName = "http://localhost:3001/intranet/src/assets/photos_etudiants/" + response.data.photoName;
            user.value = response.data;
            departements.value = response.data.structureDepartementPersonnels;
            // récupérer le département qui a defaut = true
            departementDefaut.value = departements.value.find(departement => departement.defaut === true);
            // récupérer les départements qui n'ont pas defaut = true
            departementsNotDefaut.value = departements.value.filter(departement => departement.defaut === false);
        } catch (error) {
            console.error('Error fetching user:', error);
        }
    };

    const changeDepartement = async (departementId) => {
        try {
            // récupérer les départements du personnel
            // const response = await api.get(`/api/structure_departement_personnels/by_personnel/${user.value.id}`);
            // const departementPersonnels = response.data['member'];
            // // récupérer le département sélectionné
            // const departementPersonnel = departementPersonnels.find(departementPersonnel => departementPersonnel.departement.id === departementId);
            // // récupérer le département qui a defaut = true
            // const departementDefautCurrent = departementPersonnels.find(departementPersonnel => departementPersonnel.defaut === true);
            //
            // // on met à jour le département qui a defaut = true en le passant à false
            // if (departementDefautCurrent) {
            //     await api.patch(`/api/structure_departement_personnels/${departementDefautCurrent.id}`, {
            //         defaut: false,
            //     }, {
            //         headers: {
            //             'Content-Type': 'application/merge-patch+json'
            //         }
            //     });
            //     departements.value = departements.value.map(departement => {
            //         if (departement.id === departementDefautCurrent.id) {
            //             return {
            //                 ...departement,
            //                 defaut: false
            //             };
            //         }
            //         return departement;
            //     });
            // }
            // // on met à jour le département sélectionné en le passant à true
            // await api.patch(`/api/structure_departement_personnels/${departementPersonnel.id}`, {
            //     defaut: true,
            // }, {
            //     headers: {
            //         'Content-Type': 'application/merge-patch+json'
            //     }
            // });
            //
            // departements.value = departements.value.map(departement => {
            //     if (departement.id === departementPersonnel.id) {
            //         return {
            //             ...departement,
            //             defaut: true
            //         };
            //     }
            //     return departement;
            // });

            const response = await api.post(`/api/structure_departement_personnels/${departementId}/change_departement`, {
            }, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            });
            departements.value = response.data;
            console.log(response.data);

            departementDefaut.value = departements.value.filter(departement => departement.defaut !== false);
            departementsNotDefaut.value = departements.value.filter(departement => departement.defaut === false);
        } catch (error) {
            console.error('Error changing department:', error);
        }
    };

    return {
        user,
        departements,
        departementDefaut,
        departementsNotDefaut,
        fetchUser,
        changeDepartement
    };
});
