import { useUsersStore } from '@stores';

/**
 * Permission utility for role-based access control
 *
 * This utility provides functions to check if a user has specific permissions
 * based on their role and type. It's designed to be used across the application
 * for consistent access control.
 */

/**
 * Check if the current user has permission to access a specific feature
 * @param {string|Array} requiredPermission - The permission(s) required to access the feature
 * @param {Object} options - Additional options
 * @param {boolean} options.requireAll - If true, the user must have all permissions in the array (default: false)
 * @returns {boolean} - True if the user has the required permission(s)
 */
export function hasPermission(requiredPermission, options = {}) {
  const userStore = useUsersStore();
  const { requireAll = false } = options;

  // If no user is loaded yet, deny access
  if (!userStore.isLoaded) {
    return false;
  }

  // Handle array of permissions
  if (Array.isArray(requiredPermission)) {
    if (requireAll) {
      // User must have ALL permissions in the array
      return requiredPermission.every(permission => checkSinglePermission(permission, userStore));
    } else {
      // User must have AT LEAST ONE permission in the array
      return requiredPermission.some(permission => checkSinglePermission(permission, userStore));
    }
  }

  // Handle single permission
  return checkSinglePermission(requiredPermission, userStore);
}

/**
 * Check if the current user has a specific permission
 * @param {string} permission - The permission to check
 * @param {Object} userStore - The user store instance
 * @returns {boolean} - True if the user has the permission
 */
function checkSinglePermission(permission, userStore) {
  // SuperAdmin has access to everything
  if (userStore.isSuperAdmin) {
    return true;
  }
  // Check user type permissions
  if (permission === 'isPersonnel') {
    return userStore.isPersonnel;
  }
  if (permission === 'isEtudiant') {
    return userStore.isEtudiant;
  }

  // Check role-based permissions
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

  // Special combined permissions
  if (permission === 'canViewEtudiantDetails') {
    return userStore.isScolarite || userStore.isDirection ||
           userStore.isChefDepartement || userStore.isRespParcours ||
           userStore.isDirecteurEtudes || userStore.isSuperAdmin;
  }

  if (permission === 'canEditEtudiantDetails') {
    return userStore.isScolarite || userStore.isDirection || userStore.isSuperAdmin;
  }

  if (permission === 'canViewPersonnelDetails') {
    return userStore.isDirection || userStore.isAssistant ||
           userStore.isChefDepartement || userStore.isSuperAdmin;
  }

  if (permission === 'canEditPersonnelDetails') {
    return userStore.isDirection || userStore.isSuperAdmin;
  }

  if (permission === 'canViewNotes') {
    return userStore.isNote || userStore.isScolarite ||
           userStore.isDirection || userStore.isChefDepartement ||
           userStore.isRespParcours || userStore.isDirecteurEtudes ||
           userStore.isSuperAdmin;
  }

  // Unknown permission, deny access
  console.warn(`Unknown permission: ${permission}`);
  return false;
}

/**
 * Vue directive for conditional rendering based on permissions
 *
 * Usage:
 * <div v-permission="'isScolarite'">Only visible to scolarite users</div>
 * <div v-permission="['isScolarite', 'isDirection']">Visible to scolarite or direction users</div>
 * <div v-permission="{permissions: ['isScolarite', 'isDirection'], requireAll: true}">Visible only if user has both permissions</div>
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
