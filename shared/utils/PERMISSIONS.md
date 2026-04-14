# Système de Gestion des Droits d'Accès

Ce document décrit le système de gestion des droits d'accès utilisé dans l'application UniServices. Il explique comment utiliser les différentes méthodes pour contrôler l'affichage et l'accès aux fonctionnalités en fonction des rôles et permissions des utilisateurs.

## Vue d'ensemble

Le système de gestion des droits d'accès est basé sur deux approches complémentaires :

1. **Directive Vue (`v-permission`)** : Pour le contrôle simple de visibilité des éléments dans les templates
2. **Composant `PermissionGuard`** : Pour des scénarios plus complexes nécessitant un contenu alternatif

Ces deux approches utilisent la même logique sous-jacente définie dans le fichier `permissions.js`.

## Types d'utilisateurs et rôles

Le système distingue deux types principaux d'utilisateurs :

- **Étudiants** (`isEtudiant`)
- **Personnel** (`isPersonnel`)

### Hiérarchie des rôles

Il existe une hiérarchie entre les rôles. Un utilisateur est soit un Personnel, soit un Étudiant.

- Le rôle **Personnel** est le rôle de base pour la plupart des autres rôles (Scolarité, Direction, Chef de Département, etc.). Si un utilisateur possède l'un de ces rôles spécifiques, il possède également automatiquement le rôle `isPersonnel`.
- Le rôle **Étudiant** est actuellement autonome, mais pourrait également avoir des sous-rôles à l'avenir.

Cette hiérarchie est respectée même lors de l'utilisation des rôles temporaires (impersonnalisation). Par exemple, si vous sélectionnez le rôle temporaire "Scolarité", les vérifications pour `isPersonnel` renverront également `true`.

### Liste des rôles spécifiques au Personnel

- `isAssistant` - Assistants administratifs
- `isQualite` - Responsables qualité
- `isCompta` - Service comptabilité
- `isScolarite` - Service scolarité
- `isDirection` - Direction
- `isChefDepartement` - Chefs de département
- `isChefParcours` - Chefs de parcours
- `isDirecteurEtudes` - Directeurs d'études
- `isAbsence` - Gestionnaires des absences
- `isNote` - Gestionnaires des notes
- `isEdt` - Gestionnaires des emplois du temps
- `isStage` - Gestionnaires des stages
- `isRelaiComm` - Relais communication
- `isEdusign` - Utilisateurs Edusign
- `isSuperAdmin` - Super administrateurs

## Accès SuperAdmin

Les utilisateurs ayant le rôle `isSuperAdmin` bénéficient d'un accès complet à toutes les fonctionnalités de l'application, indépendamment des permissions spécifiques requises. Le système est configuré pour accorder automatiquement toutes les permissions aux superAdmins.

Cela signifie que :
- Un superAdmin peut voir et modifier toutes les données
- Un superAdmin peut accéder à toutes les fonctionnalités
- Il n'est pas nécessaire d'ajouter explicitement le rôle superAdmin à chaque vérification de permission

Cette approche simplifie la gestion des droits pour les administrateurs système tout en maintenant un contrôle d'accès granulaire pour les autres utilisateurs.

## Permissions composées

En plus des rôles simples, le système définit des permissions composées qui combinent plusieurs rôles :

- `canViewEtudiantDetails` - Peut voir les détails des étudiants
- `canEditEtudiantDetails` - Peut modifier les détails des étudiants
- `canViewPersonnelDetails` - Peut voir les détails du personnel
- `canEditPersonnelDetails` - Peut modifier les détails du personnel
- `canViewNotes` - Peut voir les notes

## Utilisation de la directive `v-permission`

La directive `v-permission` permet de contrôler la visibilité d'un élément en fonction des permissions de l'utilisateur. Elle est réactive et s'adapte automatiquement aux changements du store (chargement des données, changement de rôle temporaire) grâce à un observateur (`watch`) interne.

> **Note importante** : Contrairement au composant `PermissionGuard` qui peut supprimer complètement le contenu du DOM ou afficher un contenu alternatif, la directive `v-permission` utilise la propriété CSS `display: none !important` pour masquer l'élément si l'utilisateur n'a pas les droits. Cela garantit que l'élément peut réapparaître dynamiquement si les permissions changent sans nécessiter un remontage du composant.

### Exemples

```vue
<!-- Visible uniquement pour les utilisateurs du service scolarité -->
<div v-permission="'isScolarite'">
  Contenu réservé au service scolarité
</div>

<!-- Visible pour les utilisateurs du service scolarité OU de la direction -->
<div v-permission="['isScolarite', 'isDirection']">
  Contenu visible par la scolarité ou la direction
</div>

<!-- Visible uniquement pour les utilisateurs ayant TOUS les rôles spécifiés -->
<div v-permission="{permissions: ['isScolarite', 'isDirection'], requireAll: true}">
  Contenu visible uniquement si l'utilisateur a les deux rôles
</div>
```

## Utilisation du composant `PermissionGuard`

Le composant `PermissionGuard` offre plus de flexibilité que la directive, notamment la possibilité d'afficher un contenu alternatif lorsque l'utilisateur n'a pas les permissions requises.

### Exemples

```vue
<!-- Affichage conditionnel simple -->
<PermissionGuard permission="isScolarite">
  <p>Contenu visible uniquement par le service scolarité</p>
</PermissionGuard>

<!-- Avec contenu alternatif -->
<PermissionGuard permission="isScolarite" :showFallback="true">
  <p>Contenu visible uniquement par le service scolarité</p>

  <template #fallback>
    <p>Vous n'avez pas accès à ce contenu</p>
  </template>
</PermissionGuard>

<!-- Avec plusieurs permissions (OR logique) -->
<PermissionGuard :permission="['isEtudiant', 'canViewEtudiantDetails']">
  <p>Contenu visible par l'étudiant lui-même ou par les utilisateurs autorisés</p>
</PermissionGuard>
```

## Vérification programmatique des permissions

Pour vérifier les permissions dans le code JavaScript, utilisez la fonction `hasPermission` :

```javascript
import { hasPermission } from '@utils';

// Vérification simple
if (hasPermission('isScolarite')) {
  // Code exécuté uniquement pour les utilisateurs du service scolarité
}

// Vérification multiple (OR logique par défaut)
if (hasPermission(['isScolarite', 'isDirection'])) {
  // Code exécuté si l'utilisateur a au moins un des rôles
}

// Vérification multiple avec AND logique
if (hasPermission(['isScolarite', 'isDirection'], { requireAll: true })) {
  // Code exécuté uniquement si l'utilisateur a tous les rôles
}
```

## Gestion des accès aux routes (Vue Router)

Le système de permissions est intégré aux gardes de navigation de Vue Router (`router.beforeEach`) dans chaque bundle (`auth`, `intranet`, `edt`).

### Configuration des routes

Pour protéger une route, ajoutez une propriété `permission` dans l'objet `meta` de la définition de la route :

```javascript
// Exemple dans un fichier de routes
export default [
  {
    path: 'gestion-acces',
    component: GestionAccesView,
    name: 'gestion-acces',
    meta: {
      permission: 'isSuperAdmin', // Permission requise
      breadcrumb: [/* ... */]
    },
  }
]
```

### Fonctionnement du garde de navigation

Dans chaque `router/index.js`, le garde effectue les actions suivantes :
1. Attend l'initialisation de l'authentification (`userStore.initAuth()`).
2. Charge les données de l'utilisateur si nécessaire (`userStore.getUser()`).
3. Vérifie la permission définie dans `to.meta.permission`.
   - Si la route actuelle n'a pas de permission, il vérifie les routes parentes (`to.matched`).
4. Si la permission est manquante, l'utilisateur est redirigé vers la page `/access` (Accès Refusé).

### Cas particuliers

- **Routes publiques** : Marquez les routes publiques avec `meta: { public: true }` pour ignorer les vérifications d'authentification et de permissions.
- **Permissions héritées** : Si une route parente possède une permission, toutes ses routes enfants en héritent automatiquement, sauf si elles définissent leur propre permission plus restrictive.

## Bonnes pratiques

1. **Cohérence** : Utilisez les mêmes permissions pour les mêmes types d'actions dans toute l'application
2. **Granularité** : Créez des permissions spécifiques plutôt que de réutiliser des rôles généraux
3. **Documentation** : Commentez l'utilisation des permissions dans le code pour faciliter la maintenance
4. **Sécurité** : N'oubliez pas que les contrôles côté client ne sont pas suffisants - assurez-vous que les API sont également protégées

## Extension du système

Pour ajouter de nouvelles permissions composées, modifiez le fichier `permissions.js` en ajoutant une nouvelle condition dans la fonction `checkSinglePermission`.

## Permissions contextuelles (avancé)

Au-delà des rôles et des permissions composées, vous pouvez définir des permissions « contextuelles » qui dépendent d’un objet métier (ex.: une évaluation).

Format pris en charge partout (directive, composant, code JS) via `hasPermission`:

- Objet contextuel: `{ permission: string, context?: any }`
- Exemple pratique: `canManageEvaluation` utilise `context.evaluation`

Exemples d’utilisation:

```vue
<!-- Dans un template avec PermissionGuard -->
<PermissionGuard :permission="{ permission: 'canManageEvaluation', context: { evaluation } }">
  <!-- contenu autorisé -->
</PermissionGuard>

<!-- Combiner avec d’autres permissions (OR) -->
<PermissionGuard :permission="{ permissions: [ { permission: 'canManageEvaluation', context: { evaluation } }, 'isDirection' ], requireAll: false }"/>
```

```js
// En JavaScript
import { hasPermission } from '@utils/permissions';

if (hasPermission({ permission: 'canManageEvaluation', context: { evaluation } })) {
  // ...
}
```

Déclaration des handlers contextuels:
- Dans `permissions.js`, ajoutez une entrée au registre `contextualHandlers`:

```js
const contextualHandlers = {
  canManageEvaluation: ({ userStore, context }) => {
    const evaluation = context?.evaluation;
    if (!evaluation) return false;
    if (userStore.isSuperAdmin || userStore.isNote) return true;
    // Vérification d’appartenance dans evaluation.personnelAutorise ...
    return /* true/false */
  },
  // Ajoutez vos propres handlers ici
};
```

Bonnes pratiques:
- Gardez la logique serveur alignée (Voter/Policy) pour la sécurité.
- Nommez les permissions contextuelles de manière explicite (`canManageX`, `canEditY`).
- Réutilisez `hasPermission` pour composer simplement des règles plus riches.
