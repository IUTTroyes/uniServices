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
    'isPersonnel',
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

export function canManageEvaluation(evaluation) {
    const userStore = useUsersStore();
    // si le user fait partie des personnelAutorise de l'évaluation ou est super admin ou chef de département
    if (userStore.isSuperAdmin) {
        return true;
    }
    if (userStore.isChefDepartement) {
        return true;
    }
    if (evaluation.personnelAutorise && Array.isArray(evaluation.personnelAutorise)) {
        return evaluation.personnelAutorise.includes(userStore.userId);
    }
}

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

  if (!userStore.isLoaded) {
    return false;
  }

  // Normalize single object like { permission, context } to consistent form handled below
  const normalize = (p) => {
    if (typeof p === 'object' && !Array.isArray(p)) {
      // If it's the wrapper that contains permissions array (handled elsewhere), leave as-is
      if (p.permissions !== undefined) return p;
      return { permission: p.permission ?? null, context: p.context ?? {} };
    }
    // string or boolean
    return { permission: p, context: {} };
  };

  // Handle array of permissions (each entry may be string or object)
  if (Array.isArray(requiredPermission)) {
    if (requireAll) {
      return requiredPermission.every(p => {
        const { permission, context } = normalize(p);
        return checkSinglePermission(permission, userStore, context);
      });
    } else {
      return requiredPermission.some(p => {
        const { permission, context } = normalize(p);
        return checkSinglePermission(permission, userStore, context);
      });
    }
  }

  // Single permission which may be string or object { permission, context }
  const { permission, context } = normalize(requiredPermission);
  return checkSinglePermission(permission, userStore, context);
}

/**
 * Vérifie si l'utilisateur actuel possède une permission spécifique
 * @param {string} permission - La permission à vérifier
 * @param {Object} userStore - L'instance du store utilisateur
 * @returns {boolean} - Vrai si l'utilisateur possède la permission
 */
function checkSinglePermission(permission, userStore, context = {}) {
  // SuperAdmin a accès à tout
  if (userStore.isSuperAdmin) {
    return true;
  }

  // Gérer cas booléens passés directement
  if (permission === true) return true;
  if (permission === false) return false;

  // Permissions simples (identiques à l'existant)
  if (permission === 'isPersonnel') return userStore.isPersonnel;
  if (permission === 'isEtudiant') return userStore.isEtudiant;
  if (permission === 'isAssistant') return userStore.isAssistant;
  if (permission === 'isQualite') return userStore.isQualite;
  if (permission === 'isCompta') return userStore.isCompta;
  if (permission === 'isScolarite') return userStore.isScolarite;
  if (permission === 'isDirection') return userStore.isDirection;
  if (permission === 'isChefDepartement') return userStore.isChefDepartement;
  if (permission === 'isRespParcours') return userStore.isRespParcours;
  if (permission === 'isDirecteurEtudes') return userStore.isDirecteurEtudes;
  if (permission === 'isAbsence') return userStore.isAbsence;
  if (permission === 'isNote') return userStore.isNote;
  if (permission === 'isEdt') return userStore.isEdt;
  if (permission === 'isStage') return userStore.isStage;
  if (permission === 'isRelaiComm') return userStore.isRelaiComm;
  if (permission === 'isEdusign') return userStore.isEdusign;
  if (permission === 'isSuperAdmin') return userStore.isSuperAdmin;

  // Permission spécifique : canManageEvaluation attend un contexte { evaluation }
  if (permission === 'canManageEvaluation') {
    return canManageEvaluation(context.evaluation);
  }

  // Permissions composites existantes
  if (permission in compositePermissions) {
    return compositePermissions[permission].some(role => userStore[role]) || userStore.isSuperAdmin;
  }

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
      // Support { permissions: [...], requireAll: true, context: {...} }
      if (value.permissions !== undefined) {
        requiredPermission = value.permissions;
        options.requireAll = value.requireAll || false;
        options.context = value.context || {};
      } else if (value.permission !== undefined) {
        // Support { permission: 'canManageEvaluation', context: { evaluation } }
        requiredPermission = value.permission;
        options = { requireAll: value.requireAll || false, context: value.context || {} };
      } else {
        requiredPermission = value;
      }
    } else {
      requiredPermission = value;
    }

    // Si requiredPermission est un objet unique avec contexte, on passe tel quel à hasPermission
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
