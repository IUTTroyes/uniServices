import {defineStore} from 'pinia';
import {ref} from 'vue';
import {
    changeDepartementActifService,
    getAllStatutsService,
    getEtudiantScolariteActifService,
    getUserService,
    updateUserService
} from "@requests";
import {useAnneeUnivStore} from '@stores';

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
            console.log('Fetching user : ' + userType + ' - ' + userId);
            user.value = await getUserService(userType, userId);
            console.log(user.value)

            userPhoto.value = "common-images/photos_etudiants/" + user.value.photoName;
            applications.value = user.value.applications;

            if (userType === 'personnels') {
                departements.value = user.value.structureDepartementPersonnels;
                if (!departements.value.find(departement => departement.defaut === true)) {
                    const firstDepartement = departements.value[0];
                    const response = await changeDepartementActifService(firstDepartement.id);
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

                scolariteActif.value = await getEtudiantScolariteActifService(userId, currentAnneeUniv.value.id);
                departementDefaut.value = scolariteActif.value.departement;
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
            const departementPersonnelId = await departements.value.find(departement => departement.departement.id === departementId).id;
            departements.value = await changeDepartementActifService(departementPersonnelId);
            // récupérer le département qui a defaut = true
            console.log(departements.value);
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
        isLoading.value = true;
        // si domaines n'est pas un tableau
        if (!Array.isArray(data.domaines)) {
            // séparer les domaines en utilisant la virgule comme séparateur
            data.domaines = data.domaines.split(',');
        }
        // convertir structureDepartementPersonnels en IRI
        if (data.structureDepartementPersonnels) {
            data.structureDepartementPersonnels = data.structureDepartementPersonnels.map(departement => `/api/structure_departement_personnels/${departement.id}`);
        }
        try {
            const updatedUser = await updateUserService(userType, userId, data);
            user.value = updatedUser;
        } catch (error) {
            console.error('Error updating user:', error);
        } finally {
            isLoading.value = false;
        }
    };

    const getStatuts = async () => {
        try {
            statuts.value = await getAllStatutsService();
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
