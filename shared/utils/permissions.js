import { useUsersStore, useSecurity } from '@stores';
import { watchEffect, shallowRef } from 'vue';
import { ROLE_MAP } from './roleCatalog';
export { ROLE_MAP, AVAILABLE_ROLES } from './roleCatalog';

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
  canEditAbsences: ['isAbsence'],
  canManageStages: ['isStage'],
  canManageEdt: ['isEdt'],
  canManageNotes: ['isNote'],
  canUseEdusign: ['isEdusign'],
  canCoordinateUniservices: ['isReferent'],
  canCoordinateIntranet: ['isReferentIntranet'],
  canCoordinatePortfolio: ['isReferentPortfolio'],
  canCoordinateProjet: ['isReferentProjet'],
  canManageCommunication: ['isRelaiComm'],
  canAccessInformatique: ['isInformatique'],
  canAccessComptabilite: ['isCompta'],
  canAccessQualite: ['isQualite']
};

// Handlers de permissions contextuelles nommées
// Permet d'écrire: hasPermission({ permission: 'canManageEvaluation', context: { evaluation } })
const getUserIdentifiers = (userStore) => ({
  ids: [userStore?.personnel?.id, userStore?.user?.id, userStore?.id, userStore?.uid].filter(v => v !== null && v !== undefined),
  emails: [userStore?.email, userStore?.user?.email, userStore?.personnel?.email]
    .filter(v => typeof v === 'string' && v.trim() !== '')
    .map(v => v.trim().toLowerCase()),
  logins: [userStore?.username, userStore?.login, userStore?.user?.username]
    .filter(v => typeof v === 'string' && v.trim() !== '')
    .map(v => v.trim().toLowerCase()),
});

const isUserInList = (userStore, list) => {
  if (!Array.isArray(list) || list.length === 0) {
    return false;
  }

  const { ids, emails, logins } = getUserIdentifiers(userStore);

  return list.some((person) => {
    if (!person) {
      return false;
    }

    // Support des payloads simplifiés (id/uid/email/login en string|number)
    if (typeof person === 'string' || typeof person === 'number') {
      const value = String(person).trim();
      const loweredValue = value.toLowerCase();
      return ids.includes(person)
        || ids.includes(Number.isNaN(Number(value)) ? value : Number(value))
        || emails.includes(loweredValue)
        || logins.includes(loweredValue);
    }

    if (typeof person !== 'object') {
      return false;
    }

    const personId = person.id ?? person.userId ?? person.uid;
    const personEmail = typeof person.email === 'string' ? person.email.trim().toLowerCase() : null;
    const personLogin = typeof (person.login ?? person.username) === 'string'
      ? (person.login ?? person.username).trim().toLowerCase()
      : null;

    return ids.includes(personId)
      || (personEmail ? emails.includes(personEmail) : false)
      || (personLogin ? logins.includes(personLogin) : false);
  });
};

const contextualHandlers = {
  canManageEvaluation: ({ userStore, context }) => {
    if (!context?.evaluation) return false;
    if (userStore.isSuperAdmin || userStore.isNote) return true;

    const list = Array.isArray(context.evaluation.personnelAutorise)
      ? context.evaluation.personnelAutorise
      : [];

    return isUserInList(userStore, list);
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
  const normalizedOptions = (options && typeof options === 'object') ? options : {};
  const { requireAll = false } = normalizedOptions;

  // Si aucun utilisateur n'est encore chargé, refuser l'accès
  if (!userStore.isLoaded) {
    return false;
  }

  // 1) Tableau de permissions (OR par défaut, AND si requireAll)
  // Supporte les éléments de types variés: string, fonction, objet composite, objet contextuel
  if (Array.isArray(requiredPermission)) {
    return requireAll
        ? requiredPermission.every(permission => hasPermission(permission, { requireAll: false }))
        : requiredPermission.some(permission => hasPermission(permission, { requireAll: false }));
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
    return checkSinglePermission(name, userStore, requiredPermission.context);
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

/**
 * Vérifie si l'utilisateur actuel possède une permission spécifique
 * @param {string} permission - La permission à vérifier
 * @param {Object} userStore - L'instance du store utilisateur
 * @returns {boolean} - Vrai si l'utilisateur possède la permission
 */
function checkSinglePermission(permission, userStore, context = {}) {
  const security = useSecurity();
  if (typeof permission === 'boolean') return permission;

  // Vérification de l'activation d'un package (ex: 'package:stages' ou 'package:questionnaire')
  if (typeof permission === 'string' && permission.startsWith('package:')) {
    const packageName = permission.slice(8);
    return security.hasPackage(packageName);
  }

  // Vérifier les types d'utilisateurs
  if (permission === 'isPersonnel') {
    return userStore.isPersonnel;
  }
  if (permission === 'isEtudiant') {
    return userStore.isEtudiant;
  }

  if (permission === 'isDirectionIut') return userStore.isDirectionIut;

  // These staff functions are explicit and cumulative, not an administrator override.
  if (permission === 'isEnseignant' || permission === 'ROLE_TEACHER') return userStore.isEnseignant;
  if (permission === 'isBiatss' || permission === 'ROLE_BIATSS') return userStore.isBiatss;

  // Seuls les rôles sélectionnés sont utilisés pendant la simulation, y compris une sélection vide.
  if (userStore.hasRole('SUPER_ADMIN')) {
    return true;
  }

  // En mode rôle temporaire, SUPER_ADMIN doit suivre le rôle actif simulé
  if (permission === 'SUPER_ADMIN') {
    return userStore.hasRole('SUPER_ADMIN');
  }

  // Vérifier les permissions basées sur les rôles via la map
  if (permission in ROLE_MAP) {
    const mappedRole = ROLE_MAP[permission];
    return userStore.hasRole(mappedRole, context?.diplome);
  }

  // Autorisation de navigation uniquement : les actions restent liées au diplôme.
  if (permission === 'canViewAdministration' && !context?.diplome && userStore.isRespParcours) {
    return true;
  }

  // Vérifier les permissions composites
  if (permission in compositePermissions) {
    // Vérifier si l'utilisateur possède l'un des rôles qui accordent cette permission
    return compositePermissions[permission].some(roleKey => {
      const mappedRole = ROLE_MAP[roleKey];
      return mappedRole ? userStore.hasRole(mappedRole, context?.diplome) : false;
    });
  }

  // Vérifier si l'utilisateur est un personnel avec un rattachement à un service
  if (permission === 'isPersonnelService') {
    if (security.user?.type !== 'personnels') {
      return false;
    }

    const structureServices = security.user?.structureServices;
    if (Array.isArray(structureServices)) {
      return structureServices.length > 0;
    }

    return false;
  }

  // Support d'un prédicat fonctionnel pour des contrôles contextuels
  if (typeof permission === 'function') {
    try {
      const result = permission(userStore);
      return !!result;
    } catch (e) {
      console.warn('Erreur lors de l\'évaluation de la permission fonctionnelle:', e);
      return false;
    }
  }

  // Vérification directe si c'est un rôle de permission brut
  return typeof permission === 'string' && userStore.hasRole(permission, context?.diplome);
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
    el._permissionValue = shallowRef(binding.value);
    el._permissionWatch = watchEffect(() => updatePermission(el, { value: el._permissionValue.value }));
  },
  updated(el, binding) {
    el._permissionValue.value = binding.value;
  },
  unmounted(el) {
    if (el._permissionWatch) {
      el._permissionWatch();
      delete el._permissionWatch;
    }
  }
};

function updatePermission(el, binding) {
  const hasPerm = hasPermission(binding.value);

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
