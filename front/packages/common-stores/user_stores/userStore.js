import {defineStore} from 'pinia';
import {computed, ref} from 'vue';
import {
    changeDepartementActifService,
    getAllStatutsService,
    getEtudiantScolaritesService,
    getUserService,
    updateUserService
} from "@requests";
import {useAnneeUnivStore, useDepartementStore} from '@stores';
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
    const temporaryRole = ref([]);

    const isLoading = ref(false);
    const isLoaded = ref(false);

    const anneeUnivStore = useAnneeUnivStore();

    const getUser = async (force = false) => {
        if (isLoaded.value && !force) {
            return user.value;
        }
        isLoading.value = true;
        try {
            console.log('Fetching user : ' + userType + ' - ' + userId);
            user.value = await getUserService(userType, userId);

            userPhoto.value = noImage;
            applications.value = user.value.applications;

            if (userType === 'personnels') {
                const departementStore = useDepartementStore();
                // Utiliser les départements en cache si disponibles, ou forcer le rechargement si nécessaire
                departements.value = await departementStore.getDepartementsPersonnel(userId, force);

                // Traiter les départements pour extraire les informations spécifiques au personnel
                departements.value = departements.value.map(departement => {
                    if (!departement.departementPersonnels) return departement;

                    const personnelDepartements = departement.departementPersonnels.filter(dp => dp.personnel.id === userId);
                    departement.departementPersonnel = personnelDepartements.length > 0 ? personnelDepartements[0] : null;

                    delete departement.departementPersonnels;
                    return departement;
                });

                // Définir le département par défaut si aucun n'est défini
                if (!departements.value.find(departement => departement.departementPersonnel?.defaut === true)) {
                    if (departements.value.length > 0) {
                        const firstDepartement = departements.value[0];
                        const response = await changeDepartementActifService(firstDepartement.departementPersonnel.id);
                        departements.value = response.data;
                    }
                }

                // Mettre à jour les références des départements
                departementDefaut.value = departements.value.find(departement => departement.departementPersonnel?.defaut === true) || {};
                departementsNotDefaut.value = departements.value.filter(departement => departement.departementPersonnel?.defaut === false) || [];
            }
            if (userType === 'etudiants') {
                scolariteActif.value = (await getEtudiantScolaritesService(userId, true))[0];
                departementDefaut.value = scolariteActif.value.departement;
            }
            isLoaded.value = true;
            return user.value;
        } catch (error) {
            console.error('Error fetching user:', error);
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    const changeDepartement = async (departementId) => {
        try {
            console.log('Changing departement : ' + departementId);
            const departement = await departements.value.find(d => d.id === departementId);
            console.log('departementchange', departement);
            departements.value = await changeDepartementActifService(departement.departementPersonnel.id);
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
        // convertir departementPersonnels en IRI
        if (data.departementPersonnels) {
            data.departementPersonnels = data.departementPersonnels.map(departement => `/api/structure_departement_personnels/${departement.id}`);
        }
        try {
            user.value = await updateUserService(userType, userId, data);
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

    // Méthode pour définir temporairement un rôle utilisateur
    const setTemporaryRole = (roleName) => {
        // Convertir le nom du rôle au format ROLE_ correspondant
        const roleMap = {
            'Personnel': 'ROLE_PERSONNEL',
            'Etudiant': 'ROLE_ETUDIANT',
            'Assistant': 'ROLE_ASSISTANT',
            'Qualite': 'ROLE_QUALITE',
            'Compta': 'ROLE_COMPTA',
            'Scolarite': 'ROLE_SCOLARITE',
            'Direction': 'ROLE_DIRECTION',
            'Chef Departement': 'ROLE_CHEF_DEPARTEMENT',
            'Responsable Parcours': 'ROLE_RESP_PARCOURS',
            'Directeur Etudes': 'ROLE_DIRECTEUR_ETUDES',
            'Absence': 'ROLE_ABSENCE',
            'Note': 'ROLE_NOTE',
            'EDT': 'ROLE_EDT',
            'Stage': 'ROLE_STAGE',
            'Relai Communication': 'ROLE_RELAI_COMM',
            'Edusign': 'ROLE_EDUSIGN',
            'Super Admin': 'ROLE_SUPER_ADMIN'
        };

        temporaryRole.value = roleMap[roleName] || null;
        console.log(`Temporary role set to: ${temporaryRole.value}`);
    };

    // Méthode pour effacer le rôle temporaire
    const clearTemporaryRole = () => {
        temporaryRole.value = null;
        console.log('Temporary role cleared');
    };

    // Vérifier si un rôle spécifique est actif
    // Si temporaryRole est défini, vérifier uniquement ce rôle (mode exclusif)
    // Si temporaryRole n'est pas défini, vérifier les rôles originaux
    const hasRole = (role) => {
        if (temporaryRole.value) {
            return temporaryRole.value === role;
        }
        return user.value?.roles.includes(role);
    };

    const isPersonnel = computed(() => userType === 'personnels');
    const isEtudiant = computed(() => userType === 'etudiants');
    const isAssistant = computed(() => hasRole('ROLE_ASSISTANT'));
    const isQualite = computed(() => hasRole('ROLE_QUALITE'));
    const isCompta = computed(() => hasRole('ROLE_COMPTA'));
    const isScolarite = computed(() => hasRole('ROLE_SCOLARITE'));
    const isDirection = computed(() => hasRole('ROLE_DIRECTION'));
    const isChefDepartement = computed(() => hasRole('ROLE_CHEF_DEPARTEMENT'));
    const isRespParcours = computed(() => hasRole('ROLE_RESP_PARCOURS'));
    const isDirecteurEtudes = computed(() => hasRole('ROLE_DIRECTEUR_ETUDES'));
    const isAbsence = computed(() => hasRole('ROLE_ABSENCE'));
    const isNote = computed(() => hasRole('ROLE_NOTE'));
    const isEdt = computed(() => hasRole('ROLE_EDT'));
    const isStage = computed(() => hasRole('ROLE_STAGE'));
    const isRelaiComm = computed(() => hasRole('ROLE_RELAI_COMM'));
    const isEdusign = computed(() => hasRole('ROLE_EDUSIGN'));
    const isSuperAdmin = computed(() => hasRole('ROLE_SUPER_ADMIN'));

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
        isRespParcours,
        isDirecteurEtudes,
        isAbsence,
        isNote,
        isEdt,
        isStage,
        isRelaiComm,
        isEdusign,
        isSuperAdmin,
        setTemporaryRole,
        clearTemporaryRole,
        temporaryRole,
    };
});
