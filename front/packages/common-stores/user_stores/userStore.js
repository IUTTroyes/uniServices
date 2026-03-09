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
import { getAuthenticatedUser, logout as authLogout } from "@helpers/authService";

export const useUsersStore = defineStore('users', () => {
    // Les informations utilisateur seront récupérées depuis le serveur
    const userId = ref(null);
    const userType = ref(null);
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
    const isAuthInitialized = ref(false);

    const anneeUnivStore = useAnneeUnivStore();

    // Initialiser les informations d'authentification depuis le serveur
    const initAuth = async () => {
        if (isAuthInitialized.value) {
            return { userId: userId.value, userType: userType.value };
        }

        try {
            const authInfo = await getAuthenticatedUser();
            if (authInfo && authInfo.authenticated) {
                userId.value = authInfo.userId;
                userType.value = authInfo.type;
                isAuthInitialized.value = true;
                return { userId: userId.value, userType: userType.value };
            }
            return null;
        } catch (error) {
            console.error('Error initializing auth:', error);
            return null;
        }
    };

    const getUser = async (force = false) => {
        if (isLoaded.value && !force) {
            return user.value;
        }

        // S'assurer que l'authentification est initialisée
        const authInfo = await initAuth();
        if (!authInfo) {
            console.error('User not authenticated');
            return null;
        }

        isLoading.value = true;
        try {
            user.value = await getUserService(userType.value, userId.value);

            userPhoto.value = noImage;
            applications.value = user.value.applications || [];

            if (userType.value === 'personnels') {
                const departementStore = useDepartementStore();
                // Utiliser les départements en cache si disponibles, ou forcer le rechargement si nécessaire
                departements.value = await departementStore.getDepartementsPersonnel(userId.value, force);

                // Traiter les départements pour extraire les informations spécifiques au personnel
                if (Array.isArray(departements.value)) {
                    departements.value = departements.value.map(departement => {
                        if (!departement.departementPersonnels) return departement;

                        const personnelDepartements = departement.departementPersonnels.filter(dp => dp.personnel.id === userId.value);
                        departement.departementPersonnel = personnelDepartements.length > 0 ? personnelDepartements[0] : null;

                        delete departement.departementPersonnels;
                        return departement;
                    });
                } else {
                    departements.value = [];
                }

                // Définir le département par défaut si aucun n'est défini
                if (!departements.value.find(departement => departement.departementPersonnel?.defaut === true)) {
                    if (departements.value.length > 0) {
                        const firstDepartement = departements.value[0];
                        if (firstDepartement.departementPersonnel && firstDepartement.departementPersonnel.id) {
                            const response = await changeDepartementActifService(firstDepartement.departementPersonnel.id);
                            if (response && response.data) {
                                departements.value = response.data;
                            }
                        }
                    }
                }

                // Mettre à jour les références des départements
                departementDefaut.value = departements.value.find(departement => departement.departementPersonnel?.defaut === true) || {};
                departementsNotDefaut.value = departements.value.filter(departement => departement.departementPersonnel?.defaut === false) || [];
            }
            if (userType.value === 'etudiants') {
                try {
                    const scolarites = await getEtudiantScolaritesService(userId.value, true);
                    scolariteActif.value = Array.isArray(scolarites) && scolarites.length > 0 ? scolarites[0] : null;
                    departementDefaut.value = scolariteActif.value?.departement || {};
                } catch (error) {
                    console.error('Error fetching student scolarites:', error);
                    scolariteActif.value = null;
                    departementDefaut.value = {};
                }
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
            if (!Array.isArray(departements.value)) {
                console.error('departements.value is not an array');
                return;
            }

            const departement = await departements.value.find(d => d.id === departementId);
            if (!departement) {
                console.error('Departement not found with id:', departementId);
                return;
            }

            if (!departement.departementPersonnel || !departement.departementPersonnel.id) {
                console.error('Departement has no valid departementPersonnel:', departement);
                return;
            }

            departements.value = await changeDepartementActifService(departement.departementPersonnel.id);
            // récupérer le département qui a defaut = true
            departementPersonnelDefaut.value = Array.isArray(departements.value) ?
                await departements.value.find(departement => departement.defaut === true) : null;

            if (departementPersonnelDefaut.value && departementPersonnelDefaut.value.departement) {
                departementDefaut.value = departementPersonnelDefaut.value.departement;
                if (departementDefaut.value.id) {
                    localStorage.setItem('departement', departementDefaut.value.id);
                }
            } else {
                departementDefaut.value = {};
            }
            // récupérer les départements qui n'ont pas defaut = true
            departementsPersonnelNotDefaut.value = Array.isArray(departements.value) ?
                await departements.value.filter(departement => departement.defaut === false) : [];
            departementsNotDefaut.value = Array.isArray(departementsPersonnelNotDefaut.value) ?
                await departementsPersonnelNotDefaut.value.map(departement => departement.departement) : [];
            // renvoyer vers la page d'accueil après le changement de département
            window.location.href = '/';
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
        if (data.departementPersonnels && Array.isArray(data.departementPersonnels)) {
            data.departementPersonnels = data.departementPersonnels.map(departement =>
                departement && departement.id ? `/api/structure_departement_personnels/${departement.id}` : null
            ).filter(Boolean);
        }
        try {
            user.value = await updateUserService(userType.value, userId.value, data);
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
    };

    // Méthode pour effacer le rôle temporaire
    const clearTemporaryRole = () => {
        temporaryRole.value = null;
    };

    // Vérifier si un rôle spécifique est actif
    // Si temporaryRole est défini, vérifier uniquement ce rôle (mode exclusif)
    // Si temporaryRole n'est pas défini, vérifier les rôles originaux
    const hasRole = (role) => {
        if (temporaryRole.value && temporaryRole.value.length > 0) {
            return temporaryRole.value === role;
        }
        return user.value && user.value.roles && Array.isArray(user.value.roles) ? user.value.roles.includes(role) : false;
    };

    const isPersonnel = computed(() => userType.value === 'personnels');
    const isEtudiant = computed(() => userType.value === 'etudiants');
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
    const isAdmin = computed(() => hasRole('ROLE_SUPER_ADMIN') || hasRole('ROLE_DIRECTION') || hasRole('ROLE_SCOLARITE') || hasRole('ROLE_ASSISTANT') || hasRole('ROLE_CHEF_DEPARTEMENT') || hasRole('ROLE_DIRECTEUR_ETUDES'));
    const isSuperAdmin = computed(() => hasRole('ROLE_SUPER_ADMIN'));

    // Fonction de déconnexion
    const logout = async () => {
        await authLogout();
    };

    return {
        user,
        userId,
        userType,
        applications,
        departements,
        departementDefaut,
        departementsPersonnelNotDefaut,
        departementsNotDefaut,
        getUser,
        initAuth,
        logout,
        userPhoto,
        changeDepartement,
        updateUser,
        getStatuts,
        statuts,
        scolariteActif,
        isLoading,
        isLoaded,
        isAuthInitialized,
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
        isAdmin,
        setTemporaryRole,
        clearTemporaryRole,
        temporaryRole,
    };
});
