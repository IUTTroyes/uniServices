import {defineStore} from 'pinia';
import {ref, computed} from 'vue';
import {
    changeDepartementActifService,
    getAllStatutsService,
    getEtudiantScolariteActifService,
    getUserService,
    updateUserService
} from "@requests";
import {useAnneeUnivStore} from '@stores';
import noImage from "@images/photos_etudiants/noimage.png";

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

            userPhoto.value = noImage;
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

    const isPersonnel = computed(() => userType === 'personnels');
    const isEtudiant = computed(() => userType === 'etudiants');
    const isAssistant = computed(() => user.value?.roles.includes('ROLE_ASSISTANT'));
    const isQualite = computed(() => user.value?.roles.includes('ROLE_QUALITE'));
    const isCompta = computed(() => user.value?.roles.includes('ROLE_COMPTA'));
    const isScolarite = computed(() => user.value?.roles.includes('ROLE_SCOLARITE'));
    const isDirection = computed(() => user.value?.roles.includes('ROLE_DIRECTION'));
    const isChefDepartement = computed(() => user.value?.roles.includes('ROLE_CHEF_DEPARTEMENT'));
    const isChefParcours = computed(() => user.value?.roles.includes('ROLE_CHEF_PARCOURS'));
    const isDirecteurEtudes = computed(() => user.value?.roles.includes('ROLE_DIRECTEUR_ETUDES'));
    const isAbsence = computed(() => user.value?.roles.includes('ROLE_ABSENCE'));
    const isNote = computed(() => user.value?.roles.includes('ROLE_NOTE'));
    const isEdt = computed(() => user.value?.roles.includes('ROLE_EDT'));
    const isStage = computed(() => user.value?.roles.includes('ROLE_STAGE'));
    const isRelaiComm = computed(() => user.value?.roles.includes('ROLE_RELAI_COMM'));
    const isEdusign = computed(() => user.value?.roles.includes('ROLE_EDUSIGN'));
    const isSuperAdmin = computed(() => user.value?.roles.includes('ROLE_SUPER_ADMIN'));

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
        isLoaded,
        isPersonnel,
        isEtudiant,
        isAssistant,
        isQualite,
        isCompta,
        isScolarite,
        isDirection,
        isChefDepartement,
        isChefParcours,
        isDirecteurEtudes,
        isAbsence,
        isNote,
        isEdt,
        isStage,
        isRelaiComm,
        isEdusign,
        isSuperAdmin,
    };
});
