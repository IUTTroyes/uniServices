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
    const temporaryRole = ref(null);

    const isLoading = ref(false);
    const isLoaded = ref(false);
    const isAuthInitialized = ref(false);

    const anneeUnivStore = useAnneeUnivStore();

    const normalizePackageSlug = (value) => {
        if (typeof value !== 'string') {
            return null;
        }

        const normalized = value.trim().toLowerCase();
        if (!normalized) {
            return null;
        }

        if (normalized === 'unitranet') {
            return 'intranet';
        }

        return normalized;
    };

    const applications = computed(() => {
        if (userType.value === 'personnels') {
            const packages = departementDefaut.value?.departementPersonnel?.packages;
            if (!Array.isArray(packages)) {
                return [];
            }

            const normalizedPackages = packages
                .map(normalizePackageSlug)
                .filter(Boolean);

            return Array.from(new Set(normalizedPackages));
        }

        if (!Array.isArray(user.value?.applications)) {
            return [];
        }

        return Array.from(new Set(user.value.applications
            .map(normalizePackageSlug)
            .filter(Boolean)));
    });

    const normalizePersonnelDepartements = (items) => {
        if (!Array.isArray(items)) {
            return [];
        }

        // Format déjà normalisé côté front : [{ ..., departementPersonnel: {...} }]
        if (items.every(item => item?.departementPersonnel)) {
            return items;
        }

        // Format retour endpoint change_departement : [{ defaut, permissions, departement: {...} }]
        if (items.every(item => item?.departement)) {
            return items
                .filter(item => item.departement)
                .map(item => ({
                    ...item.departement,
                    departementPersonnel: item
                }));
        }

        return items;
    };

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
            // En cas d'échec d'authentification, on s'assure que les valeurs sont nulles
            userId.value = null;
            userType.value = null;
            isAuthInitialized.value = true; // On a tenté l'init, donc c'est fait
            return null;
        } catch (error) {
            console.error('Error initializing auth:', error);
            isAuthInitialized.value = true;
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

                departements.value = normalizePersonnelDepartements(departements.value);

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

            departements.value = normalizePersonnelDepartements(await changeDepartementActifService(departement.departementPersonnel.id));
            // récupérer le département qui a defaut = true
            departementPersonnelDefaut.value = Array.isArray(departements.value) ?
                await departements.value.find(departement => departement.departementPersonnel?.defaut === true)?.departementPersonnel : null;

            if (departementPersonnelDefaut.value && departementPersonnelDefaut.value.departement) {
                // Conserver le lien vers la structure département-personnel active
                // (permissions/packages/roles), nécessaire pour les contrôles SUPER_ADMIN
                departementDefaut.value = {
                    ...departementPersonnelDefaut.value.departement,
                    departementPersonnel: departementPersonnelDefaut.value
                };
                if (departementDefaut.value.id) {
                    localStorage.setItem('departement', departementDefaut.value.id);
                }
            } else {
                departementDefaut.value = {};
            }
            // récupérer les départements qui n'ont pas defaut = true
            departementsPersonnelNotDefaut.value = Array.isArray(departements.value) ?
                await departements.value.filter(departement => departement.departementPersonnel?.defaut === false).map(departement => departement.departementPersonnel) : [];
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
    const setTemporaryRole = (role) => {
        temporaryRole.value = role;
    };

    // Méthode pour effacer le rôle temporaire
    const clearTemporaryRole = () => {
        temporaryRole.value = null;
    };

    // Clé applicative déduite dynamiquement depuis l'URL active de la SPA
    // (ne pas utiliser useRouter/useRoute dans un store)
    const currentAppKey = computed(() => {
        const pathname = window.location.pathname || '/';
        const segments = pathname.split('/').filter(Boolean);
        if (segments.length > 0) {
            const first = segments[0];
            if (first === 'app') {
                return segments[1] || null;
            }
            return first;
        }
        return null;
    });

    // Rôles actifs pour l'application courante dans le département par défaut
    // Source principale : departementPersonnel.permissions (format actuel)
    // Compatibilité legacy : departementPersonnel.roles indexé par clé applicative
    const rolesActifs = computed(() => {
        const dp = departementDefaut.value?.departementPersonnel;
        const appKey = currentAppKey.value;

        if (!dp) {
            return userType.value === 'etudiants' ? ['ROLE_ETUDIANT'] : [];
        }

        if (Array.isArray(dp.permissions)) {
            return dp.permissions;
        }

        if (appKey && dp?.roles && typeof dp.roles === 'object') {
            const appRoles = dp.roles[appKey];
            // Format tableau : { "intranet": ["ROLE_X", "ROLE_Y"] }
            if (Array.isArray(appRoles)) {
                return appRoles;
            }
            // Format string unique : { "intranet": "ROLE_X" }
            if (typeof appRoles === 'string' && appRoles.trim().length > 0) {
                return [appRoles.trim()];
            }
        }

        return [];
    });

    // Vérifie si un rôle spécifique est actif.
    // Priorité :
    //   1. Mode impersonnalisation (temporaryRole) → comportement inchangé
    //   2. SUPER_ADMIN → lu depuis les permissions du département actif
    //   3. ROLE_PERSONNEL/ROLE_ETUDIANT → déduit du type d'utilisateur
    //   4. Rôles métier → lus depuis rolesActifs (département actif)
    const hasRole = (role) => {
        if (temporaryRole.value) {
            // Correspondance exacte
            if (temporaryRole.value === role) {
                return true;
            }

            // Hiérarchie : Si on a un rôle spécifique, on est aussi Personnel (sauf pour ROLE_ETUDIANT)
            if (role === 'ROLE_PERSONNEL' && temporaryRole.value !== 'ROLE_ETUDIANT') {
                return true;
            }

            // Hiérarchie inverse : Si on est en mode ROLE_PERSONNEL, on n'a pas les sous-rôles spécifiques (déjà géré par le premier if)
            return false;
        }

        // SUPER_ADMIN vient des permissions du département actif
        if (role === 'SUPER_ADMIN') {
            const permissions = departementDefaut.value?.departementPersonnel?.permissions;
            return Array.isArray(permissions) ? permissions.includes('SUPER_ADMIN') : false;
        }

        // Rôle structurel déduit du type d'utilisateur
        if (role === 'ROLE_PERSONNEL') {
            return userType.value === 'personnels';
        }

        if (role === 'ROLE_ETUDIANT') {
            return userType.value === 'etudiants';
        }

        // Rôles métier : lus depuis le département actif
        return rolesActifs.value.includes(role);
    };

    const isPersonnel = computed(() => {
        if (temporaryRole.value) {
            return temporaryRole.value !== 'ROLE_ETUDIANT';
        }
        return userType.value === 'personnels';
    });
    const isEtudiant = computed(() => {
        if (temporaryRole.value) {
            return temporaryRole.value === 'ROLE_ETUDIANT';
        }
        return userType.value === 'etudiants';
    });

    //todo: sand doute à revoir ? Les rôles ne devrait pas être en dur ici
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
    const isAdmin = computed(() => hasRole('SUPER_ADMIN') || hasRole('ROLE_DIRECTION') || hasRole('ROLE_SCOLARITE') || hasRole('ROLE_ASSISTANT') || hasRole('ROLE_CHEF_DEPARTEMENT') || hasRole('ROLE_DIRECTEUR_ETUDES'));
    const isReferent = computed(() => hasRole('ROLE_REFERENT'));
    const isSuperAdmin = computed(() => hasRole('SUPER_ADMIN'));

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
        isReferent,
        isAdmin,
        hasRole,
        setTemporaryRole,
        clearTemporaryRole,
        temporaryRole,
        currentAppKey,
        rolesActifs,
    };
});
