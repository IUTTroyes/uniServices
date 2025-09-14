import { useUsersStore } from '@stores';

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
    'isDirection',
    'isChefDepartement',
    'isRespParcours',
    'isDirecteurEtudes',
    'isAssistant',
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
    'isChefDepartement'
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
  ]
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

  // Gérer un tableau de permissions
  if (Array.isArray(requiredPermission)) {
    if (requireAll) {
      // L'utilisateur doit avoir TOUTES les permissions du tableau
      return requiredPermission.every(permission => checkSinglePermission(permission, userStore));
    } else {
      // L'utilisateur doit avoir AU MOINS UNE permission du tableau
      return requiredPermission.some(permission => checkSinglePermission(permission, userStore));
    }
  }

  // Gérer une permission unique
  return checkSinglePermission(requiredPermission, userStore);
}

/**
 * Vérifie si l'utilisateur actuel possède une permission spécifique
 * @param {string} permission - La permission à vérifier
 * @param {Object} userStore - L'instance du store utilisateur
 * @returns {boolean} - Vrai si l'utilisateur possède la permission
 */
function checkSinglePermission(permission, userStore) {
  // SuperAdmin a accès à tout
  if (userStore.isSuperAdmin) {
    return true;
  }
  // Vérifier les permissions de type d'utilisateur
  if (permission === 'isPersonnel') {
    return userStore.isPersonnel;
  }
  if (permission === 'isEtudiant') {
    return userStore.isEtudiant;
  }

  // Vérifier les permissions basées sur les rôles
  if (permission === 'isAssistant') {
    return userStore.isAssistant;
  }
  if (permission === 'isQualite') {
    return userStore.isQualite;
  }
  if (permission === 'isCompta') {
    return userStore.isCompta;
  }
  if (permission === 'isScolarite') {
    return userStore.isScolarite;
  }
  if (permission === 'isDirection') {
    return userStore.isDirection;
  }
  if (permission === 'isChefDepartement') {
    return userStore.isChefDepartement;
  }
  if (permission === 'isRespParcours') {
    return userStore.isRespParcours;
  }
  if (permission === 'isDirecteurEtudes') {
    return userStore.isDirecteurEtudes;
  }
  if (permission === 'isAbsence') {
    return userStore.isAbsence;
  }
  if (permission === 'isNote') {
    return userStore.isNote;
  }
  if (permission === 'isEdt') {
    return userStore.isEdt;
  }
  if (permission === 'isStage') {
    return userStore.isStage;
  }
  if (permission === 'isRelaiComm') {
    return userStore.isRelaiComm;
  }
  if (permission === 'isEdusign') {
    return userStore.isEdusign;
  }
  if (permission === 'isSuperAdmin') {
    return userStore.isSuperAdmin;
  }

  // Vérifier les permissions composites
  if (permission in compositePermissions) {
    // Vérifier si l'utilisateur possède l'un des rôles qui accordent cette permission
    return compositePermissions[permission].some(role => userStore[role]) || userStore.isSuperAdmin;
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
    const value = binding.value;
    let requiredPermission;
    let options = {};

    if (typeof value === 'object' && !Array.isArray(value)) {
      requiredPermission = value.permissions;
      options = { requireAll: value.requireAll || false };
    } else {
      requiredPermission = value;
    }

    if (!hasPermission(requiredPermission, options)) {
      el.parentNode && el.parentNode.removeChild(el);
    }
  }
};

/**
 * Register the permission directive with Vue
 * @param {Object} app - Vue app instance
 */
export function registerPermissionDirective(app) {
  app.directive('permission', permissionDirective);
}
