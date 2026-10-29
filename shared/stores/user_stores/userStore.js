import {defineStore} from 'pinia';
import {computed, ref} from 'vue';
import { IUT_DIRECTION_ROLES, AVAILABLE_ROLES } from '../../utils/roleCatalog';
import {
    changeDepartementActifService,
    getAllStatutsService,
    getEtudiantScolaritesService,
    getUserService,
    updateUserService
} from "@requests";
import {useDepartementStore, useSecurity} from '@stores';
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
    const scolariteActif = ref(null);
    // null = droits réels ; [] = simulation explicite sans rôle métier.
    const temporaryRoles = ref(null);
    const isSimulatingRoles = computed(() => temporaryRoles.value !== null);
    const temporaryRole = computed(() => temporaryRoles.value?.[0] ?? null);

    const isLoading = ref(false);
    const isLoaded = ref(false);
    const isAuthInitialized = ref(false);

    const normalizePackageSlug = (value) => {
        if (typeof value !== 'string') {
            return null;
        }

        const normalized = value.trim().toLowerCase();
        if (!normalized) {
            return null;
        }

        if (normalized === 'intranet') {
            return 'intranet';
        }

        return normalized;
    };

    const applications = computed(() => {
        if (userType.value === 'personnels') {
            const packages = departementDefaut.value?.departementPersonnel?.packages;
            if (!Array.isArray(packages)) return [];

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

    const extractEtudiantScolariteActif = (userData, scolarites = []) => {
        const actifFromUser = userData?.etudiant?.etudiantScolarite?.actif;
        if (actifFromUser) {
            return actifFromUser;
        }

        if (!Array.isArray(scolarites) || scolarites.length === 0) {
            return null;
        }

        const actifFromList = scolarites.find(scolarite => scolarite?.actif === true);
        return actifFromList || scolarites[0] || null;
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
                scolariteActif.value = null;
                const departementStore = useDepartementStore();
                // Utiliser les départements en cache si disponibles, ou forcer le rechargement si nécessaire
                departements.value = await departementStore.getDepartementsPersonnel(userId.value, force);

                // Traiter les départements pour extraire les informations spécifiques au personnel
                if (Array.isArray(departements.value)) {
                    departements.value = departements.value.map(departement => {
                        if (!departement.departementPersonnels) return departement;

                        const targetUserId = Number(userId.value);
                        const personnelDepartements = departement.departementPersonnels.filter(dp => 
                            dp?.personnel && (
                                Number(dp.personnel.id) === targetUserId ||
                                dp.personnel['@id'] === `/api/personnels/${targetUserId}`
                            )
                        );
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
                departementPersonnelDefaut.value = departementDefaut.value?.departementPersonnel || {};
                departementsPersonnelNotDefaut.value = departementsNotDefaut.value
                    .map(departement => departement?.departementPersonnel)
                    .filter(Boolean);
            }
            if (userType.value === 'etudiants') {
                try {
                    const params = {
                        actif: true,
                        etudiant: userId.value
                    };
                    const scolaritesActives = await getEtudiantScolaritesService(params, '/user');
                    const activeFromList = Array.isArray(scolaritesActives)
                        ? (scolaritesActives.find(s => s.actif === true) || scolaritesActives.find(s => s.anneeUniversitaire?.actif === true) || scolaritesActives[0])
                        : null;
                    const activeFromUser = Array.isArray(user.value?.scolarites)
                        ? (user.value.scolarites.find(s => s.actif === true) || user.value.scolarites.find(s => s.anneeUniversitaire?.actif === true))
                        : null;

                    scolariteActif.value = {
                        ...(activeFromUser || {}),
                        ...(activeFromList || {}),
                        scolariteSemestre: (activeFromList?.scolariteSemestre && activeFromList.scolariteSemestre.length > 0)
                            ? activeFromList.scolariteSemestre
                            : (activeFromUser?.scolariteSemestre || []),
                        departement: activeFromList?.departement || activeFromUser?.departement || {}
                    };
                    departementDefaut.value = scolariteActif.value?.departement || {};
                    departements.value = departementDefaut.value?.id ? [departementDefaut.value] : [];
                    departementsNotDefaut.value = [];
                    departementPersonnelDefaut.value = {};
                    departementsPersonnelNotDefaut.value = [];
                } catch (error) {
                    console.error('Error fetching student scolarites:', error);
                    scolariteActif.value = null;
                    departementDefaut.value = {};
                    departements.value = [];
                    departementsNotDefaut.value = [];
                    departementPersonnelDefaut.value = {};
                    departementsPersonnelNotDefaut.value = [];
                }
            }
            isLoaded.value = true;
            await useSecurity().loadSecurityContext();
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

    const setTemporaryRoles = (roles) => {
        const knownRoles = new Set(AVAILABLE_ROLES.map(item => item.role));
        temporaryRoles.value = userType.value === 'personnels' && Array.isArray(roles)
            ? [...new Set(roles.filter(role => knownRoles.has(role)))] : null;
    };

    // Compatibilité avec les appels à une simulation de rôle unique.
    const setTemporaryRole = (role) => {
        if (!AVAILABLE_ROLES.some(item => item.role === role)) clearTemporaryRole();
        else setTemporaryRoles([role]);
    };
    const clearTemporaryRole = () => { temporaryRoles.value = null; };

    // Le contexte résolu par le serveur est la source des rôles métier.
    const rolesActifs = computed(() => {
        const security = useSecurity();
        return security.isLoaded && String(security.currentDepartment?.id) === String(departementDefaut.value?.id)
            ? security.resolvedPermissions : [];
    });

    const isResponsableParcours = (diplome) => {
        const security = useSecurity();
        const id = typeof diplome === 'object' ? diplome?.id : diplome;
        const scope = security.scopedPermissions?.ROLE_RESP_PARCOURS;
        return isPersonnel.value
            && security.isLoaded
            && String(security.currentDepartment?.id) === String(departementDefaut.value?.id)
            && (!isSimulatingRoles.value || temporaryRoles.value.includes('ROLE_RESP_PARCOURS'))
            && Array.isArray(scope?.diplomeIds) && id != null
            && scope.diplomeIds.some(diplomeId => String(id) === String(diplomeId));
    };

    // Types come from account identity; only personnel can hold business roles.
    const hasRole = (role, diplome = null) => {
        if (userType.value !== 'personnels' || ['ROLE_PERSONNEL', 'ROLE_ETUDIANT'].includes(role)) return false;
        if (role === 'ROLE_RESP_PARCOURS') return isResponsableParcours(diplome);
        if (isSimulatingRoles.value) return temporaryRoles.value.includes(role);
        return rolesActifs.value.includes(role);
    };

    const isPersonnel = computed(() => userType.value === 'personnels');
    const isEtudiant = computed(() => userType.value === 'etudiants');

    const isEnseignant = computed(() => isPersonnel.value && hasRole('ROLE_TEACHER'));
    const isBiatss = computed(() => isPersonnel.value && hasRole('ROLE_BIATSS'));

    const isAssistant = computed(() => hasRole('ROLE_ASSISTANT'));
    const isQualite = computed(() => hasRole('ROLE_QUALITE'));
    const isCompta = computed(() => hasRole('ROLE_COMPTA'));
    const isScolarite = computed(() => hasRole('ROLE_SCOLARITE'));
    const isDirecteurIut = computed(() => hasRole('ROLE_DIRECTEUR_IUT'));
    const isDirecteurAdjointIut = computed(() => hasRole('ROLE_DIRECTEUR_ADJOINT_IUT'));
    const isChefServicesAdministratifs = computed(() => hasRole('ROLE_CHEF_SERVICES_ADMINISTRATIFS'));
    const isDirectionIut = computed(() => isPersonnel.value && IUT_DIRECTION_ROLES.some(role => hasRole(role)));
    const isDirection = computed(() => hasRole('ROLE_DIRECTION'));
    const isChefDepartement = computed(() => hasRole('ROLE_CHEF_DEPARTEMENT'));
    const isRespParcours = computed(() => isResponsableParcours(useSecurity().scopedPermissions?.ROLE_RESP_PARCOURS?.diplomeIds?.[0]));
    const isDirecteurEtudes = computed(() => hasRole('ROLE_DIRECTEUR_ETUDES'));
    const isAbsence = computed(() => hasRole('ROLE_ABSENCE'));
    const isNote = computed(() => hasRole('ROLE_NOTES_MANAGER'));
    const isEdt = computed(() => hasRole('ROLE_EDT'));
    const isStage = computed(() => hasRole('ROLE_STAGE_MANAGER'));
    const isRelaiComm = computed(() => hasRole('ROLE_RELAI_COMM'));
    const isEdusign = computed(() => hasRole('ROLE_EDUSIGN'));
    const isReferentIntranet = computed(() => hasRole('ROLE_REFERENT_INTRANET'));
    const isReferentPortfolio = computed(() => hasRole('ROLE_REFERENT_PORTFOLIO'));
    const isReferentProjet = computed(() => hasRole('ROLE_REFERENT_PROJET'));
    const isInformatique = computed(() => hasRole('ROLE_INFORMATIQUE'));
    const isAdmin = computed(() => hasRole('SUPER_ADMIN') || hasRole('ROLE_DIRECTION') || hasRole('ROLE_SCOLARITE') || hasRole('ROLE_ASSISTANT') || hasRole('ROLE_CHEF_DEPARTEMENT') || hasRole('ROLE_DIRECTEUR_ETUDES'));
    const isReferent = computed(() => hasRole('ROLE_REFERENT'));
    const isSuperAdmin = computed(() => hasRole('SUPER_ADMIN'));

    // Fonction de déconnexion
    const logout = async () => {
        clearTemporaryRole();
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
        isEnseignant,
        isBiatss,
        isAssistant,
        isQualite,
        isCompta,
        isScolarite,
        isDirection,
        isDirectionIut,
        isDirecteurIut,
        isDirecteurAdjointIut,
        isChefServicesAdministratifs,
        isChefDepartement,
        isRespParcours,
        isResponsableParcours,
        isReferentIntranet,
        isReferentPortfolio,
        isReferentProjet,
        isInformatique,
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
        setTemporaryRoles,
        temporaryRoles,
        isSimulatingRoles,
        setTemporaryRole,
        clearTemporaryRole,
        temporaryRole,
        rolesActifs,
    };
});
