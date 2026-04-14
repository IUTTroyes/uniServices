import { useUsersStore } from '@stores';
import { watch } from 'vue';

/**
 * Utilitaire de permissions pour le contrôle d'accès basé sur les rôles
 *
 * Cet utilitaire fournit des fonctions pour vérifier si un utilisateur possède des permissions spécifiques
 * en fonction de son rôle et de son type. Il est conçu pour être utilisé dans toute l'application
 * pour un contrôle d'accès cohérent.
 */

/**
 * Définition des permissions composites
 * Chaque clé est un nom de permission composite, et sa valeur est un tableau des rôles qui possèdent cette permission
 */
const compositePermissions = {
  canViewAdministration: [
    'isChefDepartement',
    'isRespParcours',
    'isDirecteurEtudes',
    'isAssistant',
    'isScolarite',
    'isNote',

  ],
  canViewEtudiantDetails: [
    'isScolarite',
    'isDirection',
    'isChefDepartement',
    'isRespParcours',
    'isDirecteurEtudes'
  ],
  canEditEtudiantDetails: [
    'isScolarite',
    'isDirection',
    'isAssistant',
    'isChefDepartement',
    'isDirecteurEtudes'
  ],
  canViewPersonnelDetails: [
    'isDirection',
    'isAssistant',
    'isChefDepartement',
    'isDirecteurEtudes'
  ],
  canEditPersonnelDetails: [
    'isDirection'
  ],
  canViewNotes: [
    'isNote',
    'isScolarite',
    'isDirection',
    'isChefDepartement',
    'isRespParcours',
    'isDirecteurEtudes'
  ],
  canEditAbsences: [
    'isAbsence',
  ]
};

// Handlers de permissions contextuelles nommées
// Permet d'écrire: hasPermission({ permission: 'canManageEvaluation', context: { evaluation } })
const contextualHandlers = {
  canManageEvaluation: ({ userStore, context }) => {
    const evaluation = context?.evaluation;
    if (!evaluation) return false;

    if (userStore.isSuperAdmin) return true;
    if (userStore.isNote) return true;

    const currentIds = [
      userStore?.personnel?.id,
      userStore?.user?.id,
      userStore?.id,
      userStore?.uid,
    ].filter(Boolean);

    const currentEmails = [
      userStore?.email,
      userStore?.user?.email,
      userStore?.personnel?.email,
    ].filter(Boolean);

    const currentLogins = [
      userStore?.username,
      userStore?.login,
      userStore?.user?.username,
    ].filter(Boolean);

    const list = Array.isArray(evaluation.personnelAutorise) ? evaluation.personnelAutorise : [];
    return list.some(p => p && typeof p === 'object' && (
        (p.id && currentIds.includes(p.id)) ||
        (p.userId && currentIds.includes(p.userId)) ||
        (p.uid && currentIds.includes(p.uid)) ||
        (p.email && currentEmails.includes(p.email)) ||
        (p.login && currentLogins.includes(p.login)) ||
        (p.username && currentLogins.includes(p.username))
    ));
  },
};

/**
 * Vérifie si l'utilisateur actuel a la permission d'accéder à une fonctionnalité spécifique
 * @param {string|Array} requiredPermission - La/les permission(s) requise(s) pour accéder à la fonctionnalité
 * @param {Object} options - Options supplémentaires
 * @param {boolean} options.requireAll - Si vrai, l'utilisateur doit avoir toutes les permissions du tableau (par défaut: false)
 * @returns {boolean} - Vrai si l'utilisateur possède la/les permission(s) requise(s)
 */
export function hasPermission(requiredPermission, options = {}) {
  const userStore = useUsersStore();
  const { requireAll = false } = options;

  // Si aucun utilisateur n'est encore chargé, refuser l'accès
  if (!userStore.isLoaded) {
    return false;
  }

  // 1) Tableau de permissions (OR par défaut, AND si requireAll)
  // Supporte les éléments de types variés: string, fonction, objet composite, objet contextuel
  if (Array.isArray(requiredPermission)) {
    return requireAll
        ? requiredPermission.every(permission => hasPermission(permission))
        : requiredPermission.some(permission => hasPermission(permission));
  }

  // 2) Objet contextuel { permission: string, context?: any }
  if (
      requiredPermission &&
      typeof requiredPermission === 'object' &&
      !Array.isArray(requiredPermission) &&
      Object.prototype.hasOwnProperty.call(requiredPermission, 'permission') &&
      typeof requiredPermission.permission === 'string'
  ) {
    const name = requiredPermission.permission;
    const handler = contextualHandlers[name];
    if (typeof handler === 'function') {
      try {
        return !!handler({ userStore, context: requiredPermission.context });
      } catch (e) {
        console.warn('Erreur handler permission contextuelle:', name, e);
        return false;
      }
    }
    // Aucun handler contextuel: retomber sur la vérification standard (rôles/permissions composites)
    return checkSinglePermission(name, userStore);
  }

  // 3) Ancien objet composite { permissions, requireAll }
  if (
      requiredPermission &&
      typeof requiredPermission === 'object' &&
      !Array.isArray(requiredPermission) &&
      Object.prototype.hasOwnProperty.call(requiredPermission, 'permissions')
  ) {
    const perms = requiredPermission.permissions;
    const all = !!requiredPermission.requireAll;
    if (Array.isArray(perms)) {
      return all
          ? perms.every(p => hasPermission(p))
          : perms.some(p => hasPermission(p));
    }
    return hasPermission(perms);
  }

  // 4) String / fonction / booléen: traités dans checkSinglePermission
  return checkSinglePermission(requiredPermission, userStore);
}

export const ROLE_MAP = {
  isAssistant: 'ROLE_ASSISTANT',
  isQualite: 'ROLE_QUALITE',
  isCompta: 'ROLE_COMPTA',
  isScolarite: 'ROLE_SCOLARITE',
  isDirection: 'ROLE_DIRECTION',
  isChefDepartement: 'ROLE_CHEF_DEPARTEMENT',
  isRespParcours: 'ROLE_RESP_PARCOURS',
  isDirecteurEtudes: 'ROLE_DIRECTEUR_ETUDES',
  isAbsence: 'ROLE_ABSENCE',
  isNote: 'ROLE_NOTE',
  isEdt: 'ROLE_EDT',
  isStage: 'ROLE_STAGE',
  isRelaiComm: 'ROLE_RELAI_COMM',
  isEdusign: 'ROLE_EDUSIGN',
  isReferent: 'ROLE_REFERENT',
  isSuperAdmin: 'ROLE_SUPER_ADMIN'
};

export const AVAILABLE_ROLES = [
  { property: 'isPersonnel', label: 'Personnel', role: 'ROLE_PERSONNEL' },
  { property: 'isEtudiant', label: 'Etudiant', role: 'ROLE_ETUDIANT' },
  { property: 'isAssistant', label: 'Assistant', role: 'ROLE_ASSISTANT' },
  { property: 'isQualite', label: 'Qualité', role: 'ROLE_QUALITE' },
  { property: 'isCompta', label: 'Compta', role: 'ROLE_COMPTA' },
  { property: 'isScolarite', label: 'Scolarité', role: 'ROLE_SCOLARITE' },
  { property: 'isDirection', label: 'Direction', role: 'ROLE_DIRECTION' },
  { property: 'isChefDepartement', label: 'Chef Département', role: 'ROLE_CHEF_DEPARTEMENT' },
  { property: 'isRespParcours', label: 'Responsable Parcours', role: 'ROLE_RESP_PARCOURS' },
  { property: 'isDirecteurEtudes', label: 'Directeur Etudes', role: 'ROLE_DIRECTEUR_ETUDES' },
  { property: 'isAbsence', label: 'Absence', role: 'ROLE_ABSENCE' },
  { property: 'isNote', label: 'Note', role: 'ROLE_NOTE' },
  { property: 'isEdt', label: 'EDT', role: 'ROLE_EDT' },
  { property: 'isStage', label: 'Stage', role: 'ROLE_STAGE' },
  { property: 'isRelaiComm', label: 'Relai Communication', role: 'ROLE_RELAI_COMM' },
  { property: 'isEdusign', label: 'Edusign', role: 'ROLE_EDUSIGN' },
  { property: 'isReferent', label: 'Referent', role: 'ROLE_REFERENT' },
  { property: 'isSuperAdmin', label: 'Super Admin', role: 'ROLE_SUPER_ADMIN' }
];

/**
 * Vérifie si l'utilisateur actuel possède une permission spécifique
 * @param {string} permission - La permission à vérifier
 * @param {Object} userStore - L'instance du store utilisateur
 * @returns {boolean} - Vrai si l'utilisateur possède la permission
 */
function checkSinglePermission(permission, userStore) {
  // Si un rôle temporaire est défini, on ignore le statut SuperAdmin pour permettre une impersonnalisation réelle
  // Sauf si le rôle temporaire lui-même est ROLE_SUPER_ADMIN
  const isImpersonating = !!userStore.temporaryRole && userStore.temporaryRole !== 'ROLE_SUPER_ADMIN';

  // SuperAdmin a accès à tout (sauf en mode impersonnalisation)
  if (userStore.isSuperAdmin && !isImpersonating) {
    return true;
  }

  // Vérifier les types d'utilisateurs
  if (permission === 'isPersonnel') {
    return userStore.isPersonnel;
  }
  if (permission === 'isEtudiant') {
    return userStore.isEtudiant;
  }

  // Vérifier les permissions basées sur les rôles via la map
  if (permission in ROLE_MAP) {
    return userStore[permission];
  }

  // Vérifier les permissions composites
  if (permission in compositePermissions) {
    // Vérifier si l'utilisateur possède l'un des rôles qui accordent cette permission
    return compositePermissions[permission].some(role => userStore[role]);
  }

  // si la permission n'est pas reconnue, on vérifie si c'est un test qui renvoie true ou false
  if (permission === true) {
    return true;
  } else if (permission === false) {
    return false;
  }

  // Support d'un prédicat fonctionnel pour des contrôles contextuels
  // La fonction peut recevoir le userStore en argument (optionnel)
  if (typeof permission === 'function') {
    try {
      const result = permission(userStore);
      return !!result;
    } catch (e) {
      console.warn('Erreur lors de l\'évaluation de la permission fonctionnelle:', e);
      return false;
    }
  }

  // Permission inconnue, refuser l'accès
  console.warn(`Unknown permission: ${permission}`);
  return false;
}

/**
 * Directive Vue pour le rendu conditionnel basé sur les permissions
 *
 * Utilisation:
 * <div v-permission="'isScolarite'">Visible uniquement pour les utilisateurs de la scolarité</div>
 * <div v-permission="['isScolarite', 'isDirection']">Visible pour les utilisateurs de la scolarité ou de la direction</div>
 * <div v-permission="{permissions: ['isScolarite', 'isDirection'], requireAll: true}">Visible uniquement si l'utilisateur possède les deux permissions</div>
 */
export const permissionDirective = {
  mounted(el, binding) {
    // On garde une trace du watch pour pouvoir le nettoyer
    el._permissionWatch = watch(
      () => {
        const store = useUsersStore();
        return [store.isLoaded, store.temporaryRole, store.user?.roles];
      },
      () => {
        updatePermission(el, binding);
      },
      { deep: true, immediate: true }
    );
  },
  unmounted(el) {
    if (el._permissionWatch) {
      el._permissionWatch();
      delete el._permissionWatch;
    }
  }
};

function updatePermission(el, binding) {
  const value = binding.value;
  let requiredPermission;
  let options = {};

  if (typeof value === 'object' && !Array.isArray(value)) {
    requiredPermission = value.permissions;
    options = { requireAll: value.requireAll || false };
  } else {
    requiredPermission = value;
  }

  const hasPerm = hasPermission(requiredPermission, options);

  if (!hasPerm) {
    // On cache l'élément au lieu de le supprimer définitivement du DOM
    // car le store peut être chargé plus tard ou le rôle peut changer (impersonnalisation)
    el.style.setProperty('display', 'none', 'important');
  } else {
    // On réaffiche l'élément si la permission est accordée
    el.style.removeProperty('display');
  }
}

/**
 * Register the permission directive with Vue
 * @param {Object} app - Vue app instance
 */
export function registerPermissionDirective(app) {
  app.directive('permission', permissionDirective);
}
