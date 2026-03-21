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

Pour le personnel, différents rôles sont disponibles :

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

- `canViewStudentDetails` - Peut voir les détails des étudiants
- `canEditStudentDetails` - Peut modifier les détails des étudiants
- `canViewPersonnelDetails` - Peut voir les détails du personnel
- `canEditPersonnelDetails` - Peut modifier les détails du personnel
- `canViewGrades` - Peut voir les notes

## Utilisation de la directive `v-permission`

La directive `v-permission` permet de contrôler la visibilité d'un élément en fonction des permissions de l'utilisateur.

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
<PermissionGuard :permission="['isEtudiant', 'canViewStudentDetails']">
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
