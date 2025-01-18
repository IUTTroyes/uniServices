import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@helpers/axios';
import { getEtudiantScolariteActif } from "@requests";
import { useAnneeUnivStore } from '@stores';

export const useUsersStore = defineStore('users', () => {
    const token = localStorage.getItem('token');
    const tokenParts = token?.split('.');
    const payload = tokenParts ? JSON.parse(atob(tokenParts[1])) : {};
    const userId = payload.userId;
    const userType = payload.type;
    const applications = ref([]);
    const user = ref(null);
    const userPhoto = ref([]);
    const departements = ref([]);
    const departementDefaut = ref({});
    const departementPersonnelDefaut = ref({});
    const departementsNotDefaut = ref({});
    const departementsPersonnelNotDefaut = ref({});
    const statuts = ref([]);
    const scolariteActif = ref({});
    const currentAnneeUniv = ref({});

    const isLoading = ref(false);
    const isLoaded = ref(false);

    const anneeUnivStore = useAnneeUnivStore();

    const getUser = async () => {
        if (isLoaded.value) {
            return;
        }
        isLoading.value = true;
        try {
            console.log('Fetching user');
            const response = await api.get(`/api/${userType}/${userId}`);
            userPhoto.value = "http://localhost:3001/intranet/src/assets/photos_etudiants/" + response.data.photoName;
            user.value = response.data;
            applications.value = response.data.applications;

            if (userType === 'personnels') {
                departements.value = response.data.structureDepartementPersonnels;
                if (!departements.value.find(departement => departement.defaut === true)) {
                    const response = await api.post(`/api/structure_departement_personnels/${departements.value[0].id}/change_departement`, {}, {
                        headers: {
                            'Content-Type': 'application/ld+json'
                        }
                    });
                    departements.value = response.data;
                }
                departementPersonnelDefaut.value = departements.value.find(departement => departement.defaut === true);
                departementDefaut.value = departementPersonnelDefaut.value.departement;
                departementsPersonnelNotDefaut.value = departements.value.filter(departement => departement.defaut === false);
                departementsNotDefaut.value = departementsPersonnelNotDefaut.value.map(departement => departement.departement);
            }
            if (userType === 'etudiants') {
                await anneeUnivStore.getCurrentAnneeUniv();
                currentAnneeUniv.value = anneeUnivStore.anneeUniv;

                scolariteActif.value = await getEtudiantScolariteActif(userId, currentAnneeUniv.value.id);
                departementDefaut.value = scolariteActif.value[0].departement;
            }
            isLoaded.value = true;
        } catch (error) {
            console.error('Error fetching user:', error);
        } finally {
            isLoading.value = false;
        }
    };

    const changeDepartement = async (departementId) => {
        try {
            const departementPersonnelId = departements.value.find(departement => departement.departement.id === departementId).id;
            const response = await api.post(`/api/structure_departement_personnels/${departementPersonnelId}/change_departement`, {
            }, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            });
            departements.value = response.data;
            // récupérer le département qui a defaut = true
            departementPersonnelDefaut.value = await departements.value.find(departement => departement.defaut === true);
            departementDefaut.value = departementPersonnelDefaut.value.departement;
            localStorage.setItem('departement', departementDefaut.value.id);
            // récupérer les départements qui n'ont pas defaut = true
            departementsPersonnelNotDefaut.value = await departements.value.filter(departement => departement.defaut === false);
            departementsNotDefaut.value = await departementsPersonnelNotDefaut.value.map(departement => departement.departement);
            window.location.reload();
        } catch (error) {
            console.error('Error changing department:', error);
        }
    };

    const updateUser = async (data) => {
        // si domaines n'est pas un tableau
        if (!Array.isArray(data.domaines)) {
            // séparer les domaines en utilisant la virgule comme séparateur
            data.domaines = data.domaines.split(',');
        }
        // convertir structureDepartementPersonnels en IRI
        if (data.structureDepartementPersonnels) {
            data.structureDepartementPersonnels = data.structureDepartementPersonnels.map(departement => `/api/structure_departement_personnels/${departement.id}`);
        }
        // récupérer uniquement le nom de la photo entre la fin de user.photoName et le dernier "/"
        // data.photoName = data.photoName.substring(data.photoName.lastIndexOf('/') + 1);
        console.log(data.photoName);

        try {
            const response = await api.patch(`/api/${userType}/${userId}`, data, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            });
            user.value = response.data;
        } catch (error) {
            console.error('Error updating user:', error);
        }
    };

    const getStatuts = async () => {
        try {
            const response = await api.get(`/api/statuts`);
            statuts.value = response.data;
        } catch (error) {
            console.error('Error fetching statuts:', error);
        }
    };

    return {
        user,
        userType,
        applications,
        departements,
        departementDefaut,
        departementsPersonnelNotDefaut,
        departementsNotDefaut,
        getUser,
        userPhoto,
        changeDepartement,
        updateUser,
        getStatuts,
        statuts,
        scolariteActif,
        isLoading,
        isLoaded
    };
});
