# Droits et rôles UniServices

## Modèle retenu

Un compte est de type **Personnel** (`personnels`) ou **Étudiant** (`etudiants`). Ce sont des types d'identité, pas des responsabilités administratives à attribuer à un étudiant.

Pour le personnel, chaque `StructureDepartementPersonnel` porte :

- les applications activées (`packages`) ;
- les rôles métier (`permissions`) ;
- les diplômes de la responsabilité de parcours (`responsableParcoursDiplomes`), si ce rôle est attribué.

Les droits se cumulent dans le département actif. Changer de département recharge le contexte. Un rôle de comptabilité, de référent ou de responsable de parcours n'accorde pas automatiquement les fonctions d'un chef de département ou d'un super administrateur.

Le serveur résout les rôles et héritages via `PermissionRegistry`, les providers des bundles et `PermissionResolver`. Le navigateur utilise `GET /api/me/security-context` :

- `permissions` : droits résolus, sans rôle de parcours global ;
- `packages` : applications activées ;
- `currentDepartment` : département actif ;
- `scopedPermissions.ROLE_RESP_PARCOURS.diplomeIds` : liste des diplômes de la responsabilité valide.

`userStore.hasRole` et `permissions.js` utilisent le contexte résolu pour les rôles métier. Ils refusent ces droits si le contexte n'est pas chargé ou correspond à un autre département. Les rôles temporaires simulent uniquement l'affichage ; ils ne modifient jamais les droits serveur.

## Catalogue de responsabilités

Le catalogue des propriétés côté Vue est centralisé dans `roleCatalog.js`. Le catalogue attribuable de l'écran de configuration provient du serveur (`GET /api/security/permissions`).

| Responsabilité | Propriété Vue | Rôle serveur | Portée / accès associé |
|---|---|---|---|
| Enseignant | `isEnseignant` | `ROLE_TEACHER` | Fonction explicite et cumulable avec BIATSS ; accès à « Mon activité » |
| BIATSS | `isBiatss` | `ROLE_BIATSS` | Fonction explicite et cumulable avec Enseignant |
| Directeur de l’IUT | `isDirecteurIut` | `ROLE_DIRECTEUR_IUT` | Direction IUT sur les départements affectés |
| Directeur adjoint de l’IUT | `isDirecteurAdjointIut` | `ROLE_DIRECTEUR_ADJOINT_IUT` | Direction IUT sur les départements affectés |
| Chef des services administratifs | `isChefServicesAdministratifs` | `ROLE_CHEF_SERVICES_ADMINISTRATIFS` | Direction IUT sur les départements affectés |
| Chef de département | `isChefDepartement` | `ROLE_CHEF_DEPARTEMENT` | Département, fonctions de direction prévues par les voters |
| Directeur des études | `isDirecteurEtudes` | `ROLE_DIRECTEUR_ETUDES` | Département, fonctions pédagogiques prévues par les voters |
| Responsable de parcours | `isRespParcours` | `ROLE_RESP_PARCOURS` | **Un ou plusieurs diplômes du département**, contrôle de chaque ressource |
| Assistant de formation | `isAssistant` | `ROLE_ASSISTANT` | Département, fonctions administratives existantes |
| Référent UniServices | `isReferent` | `ROLE_REFERENT` | Coordination, aucun accès super administrateur implicite |
| Référent Edusign | `isEdusign` | `ROLE_EDUSIGN` | Fonctions Edusign explicitement protégées |
| Responsable des absences | `isAbsence` | `ROLE_ABSENCE` | Gestion des absences |
| Responsable des stages | `isStage` | `ROLE_STAGE_MANAGER` | Gestion des stages, application `stage` / `stages` |
| Responsable des emplois du temps | `isEdt` | `ROLE_EDT` | Fonctions EDT prévues par les voters |
| Responsable des notes | `isNote` | `ROLE_NOTES_MANAGER` | Gestion des évaluations et notes prévue par les voters |
| Référent communication | `isRelaiComm` | `ROLE_RELAI_COMM` | Coordination de la communication |
| Référent Intranet | `isReferentIntranet` | `ROLE_REFERENT_INTRANET` | Coordination Intranet |
| Référent Portfolio | `isReferentPortfolio` | `ROLE_REFERENT_PORTFOLIO` | Coordination Portfolio, application `unifolio` |
| Référent Projet | `isReferentProjet` | `ROLE_REFERENT_PROJET` | Coordination des projets |
| Service informatique | `isInformatique` | `ROLE_INFORMATIQUE` | Accès explicitement associés au service, aucun super administrateur implicite |
| Service comptabilité | `isCompta` | `ROLE_COMPTA` | Fonctions comptables existantes, compatibilité `ROLE_COMPTABILITE` |
| Service qualité | `isQualite` | `ROLE_QUALITE` | Fonctions qualité explicitement protégées |
| Service scolarité | `isScolarite` | `ROLE_SCOLARITE` | Gestion de la scolarité |
| Direction (historique) | `isDirection` | `ROLE_DIRECTION` | Fonctions de direction existantes |
| Super administrateur | `isSuperAdmin` | `SUPER_ADMIN` | Administration technique, attribuée dans le département actif |

Les nouvelles responsabilités de coordination sont définies et attribuables ; leur attribution ne crée **pas** d'autorisation d'écriture sur tous les objets d'un bundle. Une nouvelle action doit être protégée explicitement côté serveur et, pour l'affichage, côté Vue. Le référent Portfolio n'hérite pas automatiquement des rôles de correcteur/publication ; le service informatique n'hérite pas automatiquement de l'administration Helpdesk ; la comptabilité n'hérite pas automatiquement de la validation financière.

Les anciens noms `ROLE_NOTE` / `ROLE_RESP_NOTES` et `ROLE_STAGE` sont convertis lors de la résolution vers `ROLE_NOTES_MANAGER` et `ROLE_STAGE_MANAGER`. Les données historiques ne sont pas supprimées.

## Responsable de parcours : rattachement obligatoire

L'écran Gestion des accès propose les diplômes du département de l'affectation. Il faut sélectionner **au moins un diplôme** pour enregistrer ce rôle. L'API valide la même contrainte, même si le navigateur est contourné. Le champ utilise une relation Doctrine et une clé étrangère ; la suppression d’un diplôme retire son rattachement.

Le rôle est refusé si le lien manque, si l’un des diplômes change de département, si Intranet est désactivé ou si la ressource concerne un diplôme non attribué. Les anciens rôles sans diplôme restent visibles pour correction mais ne donnent aucun droit de parcours.

Une relation `StructureDiplome.responsableDiplome` existait déjà : elle décrit le responsable pédagogique affiché sur le diplôme et conserve ses contrôles existants. Elle ne remplace pas l'attribution explicite d'une responsabilité de parcours dans les accès. Pour éviter une double saisie métier, une synchronisation pourra être décidée ultérieurement ; aucune nomination n'est déduite automatiquement.

### Vérification côté serveur

```php
// Contrôle explicite sur un diplôme ou un objet possédant un diplôme identifiable.
$permissions->hasPermission($user, 'ROLE_RESP_PARCOURS', $diplome);
$this->isGranted('ROLE_RESP_PARCOURS', $diplome);

// Sans sujet : le rôle de parcours est refusé.
$permissions->hasPermission($user, 'ROLE_RESP_PARCOURS'); // false
```

`DiplomeScope` remonte les relations académiques d'un objet : diplôme, semestre, PN, année, évaluation, enseignement, UE, niveau ou référentiel. Une collection doit contenir un seul élément pour être retenue. Une ressource partagée, inconnue ou sans diplôme identifiable n'est pas autorisée au seul titre de ce rôle. Les droits indépendants de l'utilisateur restent applicables : être également chef de département peut donc accorder un accès plus large.

Les voters Structure, Scolarité, APC, Post, Prévisionnel et Questionnaire transmettent le sujet pour leurs branches utilisant ce rôle. Les opérations sur des collections ou des objets partagés nécessitent une politique dédiée ; il ne faut pas simplement tester le rôle sans sujet.

### Vérification côté Vue

```js
hasPermission({ permission: 'isRespParcours', context: { diplome: { id: 12 } } });
hasPermission({ permission: 'canViewNotes', context: { diplome: 12 } });
userStore.isResponsableParcours(diplome);
```

```vue
<PermissionGuard :permission="{ permission: 'isRespParcours', context: { diplome } }">
  <!-- contenu limité au diplôme -->
</PermissionGuard>
<div v-permission="{ permission: 'canViewNotes', context: { diplome } }" />
```

`userStore.isRespParcours` indique qu'une responsabilité valide existe, pour afficher une entrée de navigation. Pour autoriser une action, utiliser le contrôle contextuel. `hasPermission('isRespParcours')` seul ne suffit pas et renvoie `false` hors dérogation super administrateur.

## Permissions composées et affichage

Les permissions existantes `canViewAdministration`, `canViewEtudiantDetails`, `canEditEtudiantDetails`, `canViewPersonnelDetails`, `canEditPersonnelDetails`, `canViewNotes` et `canEditAbsences` restent disponibles. Une branche basée sur le responsable de parcours exige le contexte du diplôme, sauf `canViewAdministration` qui permet uniquement d'ouvrir la navigation lorsqu'une responsabilité valide existe. Cette permission de navigation ne doit jamais servir à autoriser une opération sur des données.

Les points d'accès dédiés sont `canManageStages`, `canManageEdt`, `canManageNotes`, `canUseEdusign`, `canCoordinateUniservices`, `canCoordinateIntranet`, `canCoordinatePortfolio`, `canCoordinateProjet`, `canManageCommunication`, `canAccessInformatique`, `canAccessComptabilite` et `canAccessQualite`. Ils ne remplacent pas les voters des actions serveur.

```js
hasPermission('isAssistant');
hasPermission(['isAssistant', 'isScolarite']); // OU
hasPermission(['isAssistant', 'package:intranet'], { requireAll: true }); // ET
hasPermission({ permissions: ['isAssistant', 'package:intranet'], requireAll: true });
hasPermission({ permission: 'canManageEvaluation', context: { evaluation } });
```

```vue
<PermissionGuard :permission="['isAssistant', 'package:intranet']" :requireAll="true" />
<div v-permission="{ permissions: ['isAssistant', 'package:intranet'], requireAll: true }" />
```

La directive observe les changements de droits et de contexte ; elle masque l'élément sans le supprimer. `PermissionGuard` propose un slot `fallback`. Aucun de ces mécanismes ne protège les données téléchargées dans le navigateur.

Les types Personnel/Étudiant restent distincts, même pour le super administrateur. `false` reste un refus explicite. Les applications sont vérifiées dans `activePackages` ; seul le shell `auth` est systématiquement accessible, avec la dérogation super administrateur existante.

## Déploiement et vérifications

1. Appliquer la migration `back/migrations/Version20261027160000.php` avec le processus habituel de déploiement. Elle crée une table de rattachement multiple et y copie les liens existants avant de supprimer la colonne unique. Elle succède à `Version20261027144343` et ne choisit aucun diplôme arbitrairement.
2. Dans Gestion des accès, compléter les anciens responsables de parcours avec un ou plusieurs diplômes de leur département, ou retirer le rôle.
3. Recharger le contexte de sécurité après modification des droits.
4. Exécuter `php back/tests/Security/DepartmentRolesTest.php`, puis `npm exec -- vitest run --config shared/utils/__tests__/vitest.config.js` et `npm run build:shell`.

L'écriture des affectations (création, modification, suppression) est réservée à `SUPER_ADMIN`. Le changement de département personnel conserve son processeur qui vérifie la propriété de l'affectation.

Voir également [l'audit des limites restantes](../../docs/SECURITE_ROLES_IUT.md). Cette évolution ne constitue pas une sécurisation exhaustive de toutes les API.

## Permissions des widgets

Les widgets cumulent désormais trois conditions, contrôlées **côté serveur** par `WidgetAccessChecker` : profil compatible, application activée dans le département actif, puis permissions requises. Le shell `auth` reste accessible sans application métier. Les alias historiques `document`/`documents`, `portfolio`/`unifolio` et `stages`/`stage` sont reconnus.

Une définition peut déclarer :

```php
new WidgetDefinition(
    'intranet.exemple', 'intranet', 'Exemple', 'pi pi-home', 'ExampleWidget',
    allowedProfiles: [WidgetDefinition::PROFILE_PERSONNEL],
    requiredPermissions: ['ROLE_DIRECTEUR_ETUDES', 'ROLE_RESP_PARCOURS'],
    requireAllPermissions: false, // OU par défaut ; true impose toutes les permissions
);
```

Sans `requiredPermissions`, le profil et l'application restent obligatoires. Le super administrateur satisfait les permissions métier, mais ne contourne ni le profil ni l'activation du bundle.

| Widget | Profil | Permissions supplémentaires (OU) |
|---|---|---|
| Emploi du temps, actualités, questionnaires en attente, documents récents | Personnel / Étudiant | Aucune |
| Listings étudiants | Personnel | Enseignant, assistant, scolarité, chef de département ou directeur des études |
| Actions urgentes | Personnel | Responsable de parcours valide, direction, chef de département, directeur des études, assistant, scolarité, responsable des stages, des absences ou des notes |
| Statistiques et dernières réponses des questionnaires | Personnel | Gestionnaire des questionnaires ou service qualité |
| Portfolios à corriger | Personnel | Correcteur de portfolios |
| Progression portfolio, notes étudiantes | Étudiant | Aucune |
| Alertes portfolio | Personnel | Référent Portfolio, correcteur ou responsable de publication |
| Statistiques documentaires | Personnel | Référent UniServices, référent Intranet, direction ou chef de département |

Le catalogue, la configuration, l'enregistrement des préférences et les données appliquent la même règle. Une préférence sauvegardée ne constitue jamais une autorisation. Les préférences devenues interdites sont conservées mais masquées ; elles pourront redevenir disponibles après réattribution des droits. Un widget nouvellement autorisé peut être activé depuis la configuration, sans remplacer la personnalisation existante.

Un paramètre `structureDepartementPersonnelId` ne peut pas sélectionner une autre affectation que celle active de l'utilisateur connecté. Le même contexte est utilisé pour l'affichage et les données, même lorsque le paramètre est omis.

### Portée des données de parcours

Pour la visibilité d'un widget, `ROLE_RESP_PARCOURS` exige au moins un diplôme valide attribué dans le département actif. Cela ne crée aucune permission générale sur les ressources de ce département.

Si cette responsabilité est la seule raison de l'accès, `WidgetDataRegistry` transmet aux fournisseurs un `DashboardContext` dont `getDiplomeIds()` contient les seuls diplômes attribués. Chaque fournisseur qui charge des données de parcours doit utiliser cette liste pour filtrer ses requêtes. `null` signifie qu'aucune restriction par diplôme n'est imposée par cette règle, notamment si un autre rôle de pilotage accorde indépendamment l'accès.

Le fournisseur « Actions urgentes » contient actuellement des exemples statiques sans diplôme associé. Dans le contexte limité au parcours, il renvoie une liste vide plutôt que ces exemples non contextualisés ; le widget affiche alors son état vide. Le chargement d'actions métier réelles devra appliquer le filtre transmis.



## Architecture des accueils : Portail et dashboards métier

Le **Portail** est le seul dashboard personnalisable. Les API de catalogue, de configuration et d’enregistrement des préférences acceptent uniquement `dashboardCode=portail`. Les bundles continuent à fournir leurs widgets au Portail et conservent leurs contrôles de profil, d’application et de permissions.

L’**Intranet** présente un dashboard commun, fixe, sans sélecteur de profil. « Vos accès métier » conserve les liens vers les outils existants : chaque carte utilise `PermissionGuard`, les applications doivent être activées et les destinations gardent leurs autorisations.

Les sections sont cumulables : activité personnelle pour les enseignants, formations pour la direction du département, la scolarité et les assistants, départements de direction pour la direction de l’IUT, diplômes attribués pour les responsables de parcours. Un BIATSS enseignant bénéficie aussi de son activité pédagogique. Les étudiants et les BIATSS sans responsabilité retrouvent leurs accès quotidiens sur la même page.

Chaque composant de données est monté à l’intérieur de son `PermissionGuard`. Il charge uniquement ses données via `GET /api/intranet/dashboard/{section}`. Le serveur contrôle les droits réels et le périmètre avant lecture ; masquer une section ne constitue jamais une autorisation. Parcours reste limité aux diplômes attribués et la direction IUT aux départements affectés avec les permissions correspondantes. Les données sont effacées au changement de département ou de droits, et les réponses obsolètes sont ignorées.

L’agenda réutilise le service du widget Portail sans charger le système de widgets. Aucun compteur fictif n’est affiché.

Les anciennes préférences Intranet restent en base sans être utilisées ni supprimées. L’ancien lien `/intranet/widgets` redirige vers la personnalisation du Portail. Les préférences du Portail sont conservées telles quelles.

Questionnaires conserve ses vues métier « Pilotage Qualité », « Mes Évaluations » et son espace étudiant. Leurs contrôles d’affichage utilisent le même utilitaire de permissions, sans recours aux rôles bruts du compte.



### Fonctions Enseignant et BIATSS

`Personnel` reste le type du compte. `ROLE_TEACHER` (Enseignant) et `ROLE_BIATSS` sont des fonctions attribuées explicitement sur l’affectation départementale ; elles ne sont pas exclusives. Activer Intranet, être permanent, avoir une responsabilité ou être super administrateur n’attribue pas automatiquement la fonction Enseignant.

Exemples d’affectations :

- Enseignant sans responsabilité : `ROLE_TEACHER` → « Mon activité ».
- BIATSS informatique : `ROLE_BIATSS`, `ROLE_INFORMATIQUE` → « Mon espace BIATSS » avec une section Informatique.
- BIATSS informatique donnant des cours : `ROLE_BIATSS`, `ROLE_INFORMATIQUE`, `ROLE_TEACHER` → « Mon espace BIATSS » et « Mon activité », enrichis de la section Informatique.
- BIATSS : `ROLE_BIATSS` → « Mon espace BIATSS », sans outils d’enseignement.

Les fonctions sont disponibles dans la gestion des accès (cases indépendantes). Les propriétés `userStore.isEnseignant` et `userStore.isBiatss`, ainsi que `hasPermission('isEnseignant')` / `hasPermission('isBiatss')`, vérifient les rôles explicites du département actif. Elles ne bénéficient pas de la dérogation automatique super administrateur. Le retrait du rôle Enseignant masque immédiatement « Mon activité ». Questionnaires applique la même règle à « Mes Évaluations » ; un BIATSS qualité conserve son espace de pilotage sans être assimilé à un enseignant.

Les filtres API `enseignant=true` et `biatss=true` utilisent ces rôles, avec le département demandé lorsqu’il est fourni. Ils peuvent être combinés pour retrouver les BIATSS qui enseignent. Le statut administratif `Personnel.statut` (MCF, BIATSS, etc.) est conservé indépendamment et ne sert plus à exclure les BIATSS des listes d’enseignants.

**Transition des comptes existants :** `ROLE_TEACHER` n’est plus accordé par défaut à tous les personnels. Compléter les affectations avec Enseignant et/ou BIATSS dans la gestion des accès, puis recharger le contexte de sécurité. Les attributions déjà explicites restent valables. Aucune affectation n’est inventée à partir du statut administratif et aucune base de données n’est modifiée automatiquement. Aucun changement de schéma n’est nécessaire.


### Types de compte distincts des rôles attribuables

Personnel (`personnels`) et Étudiant (`etudiants`) sont exclusivement des **types de compte**. Ils ne figurent plus dans `AVAILABLE_ROLES` ni dans `ROLE_MAP` et ne sont jamais à attribuer dans les permissions départementales. Enseignant, BIATSS et les responsabilités métier restent des rôles indépendants et cumulables pour les personnels.

Les contrôles existants restent utilisables sans transformer les types en rôles :

```js
userStore.isPersonnel; // userType === 'personnels'
userStore.isEtudiant;  // userType === 'etudiants'
hasPermission('isPersonnel');
hasPermission('isEtudiant');
```

```vue
<PermissionGuard permission="isPersonnel">Contenu réservé aux personnels</PermissionGuard>
<div v-permission="'isEtudiant'">Contenu réservé aux étudiants</div>
```

Côté Symfony, utiliser `is_granted('IS_PERSONNEL')` ou `is_granted('IS_ETUDIANT')`. `UserTypeVoter` se fonde sur la classe du compte authentifié (`Personnel` / `Etudiant`) : aucun rôle et aucune dérogation super administrateur ne changent son type. Les règles des documents qui utilisaient `ROLE_PERSONNEL` ont été adaptées à `IS_PERSONNEL`.

Le contexte de sécurité d’un étudiant ne fabrique plus de rôle `ROLE_ETUDIANT`. Les créations, imports et fixtures d’étudiants n’en attribuent plus. Les anciens `ROLE_PERSONNEL` / `ROLE_ETUDIANT` déjà stockés sont ignorés à la lecture et écartés à la sauvegarde. Les contrôles `hasRole` ne doivent plus être utilisés pour tester un type.

La simulation de rôles concerne seulement les rôles métier d’un personnel : elle ne transforme jamais un personnel en étudiant ni un étudiant en personnel. Pour tester un parcours étudiant, utiliser un véritable compte étudiant. Le simulateur local de Stages qui mélangeait types et rôles a été supprimé ; le menu commun de simulation reste disponible aux personnels administrateurs.

Aucune modification de schéma ni attribution de rôle Personnel/Étudiant n’est nécessaire. Recharger le contexte de sécurité après mise à jour.


### Deux groupes de direction distincts, mêmes permissions départementales

La direction du département regroupe exclusivement le chef de département (`ROLE_CHEF_DEPARTEMENT`, avec l’alias historique `ROLE_CHEF_DEPT`) et les directeurs d’études (`ROLE_DIRECTEUR_ETUDES`).

La direction de l’IUT regroupe trois fonctions séparées et attribuables :

- Directeur : `ROLE_DIRECTEUR_IUT` (`isDirecteurIut`).
- Directeur adjoint : `ROLE_DIRECTEUR_ADJOINT_IUT` (`isDirecteurAdjointIut`).
- Chef des services administratifs : `ROLE_CHEF_SERVICES_ADMINISTRATIFS` (`isChefServicesAdministratifs`).

`isDirectionIut` est un groupe calculé, pas un quatrième rôle attribuable. Le rôle générique `ROLE_DIRECTION` est conservé pour les usages historiques, mais n’active aucune des deux sections de direction et n’est jamais converti automatiquement en fonction IUT.

Ces trois fonctions restent dans la propriété **`permissions` de chaque affectation départementale**. Aucun champ établissement ni migration n’est ajouté. Dans Gestion des accès, le groupe « Fonctions de direction de l’IUT » permet d’appliquer chaque fonction à tous les départements du personnel. À l’enregistrement, les attributions partielles sont complétées sur les affectations présentes, sans recopier les rôles Enseignant, BIATSS ou les autres responsabilités locales d’un département vers un autre. Décocher une fonction la retire de toutes les affectations de la personne.

Le serveur continue à vérifier les permissions de l’affectation active. Le dashboard IUT ne liste que les départements actifs auxquels la personne est rattachée, où Intranet est activé et où une de ces trois fonctions de direction est autorisée. Il n’accorde aucun accès aux départements non affectés ou dépourvus de permission, ni à l’administration technique. Une nouvelle affectation doit recevoir les fonctions voulues ; l’écran de gestion des accès les propage lors de l’enregistrement.

### Menu Rôles et affichage

Le sélecteur du super administrateur permet de cocher plusieurs rôles temporaires. Leur cumul agit uniquement sur les `PermissionGuard` côté interface. Une sélection vide simule un personnel sans fonction métier ; le bouton « Rétablir mes droits réels » quitte explicitement la simulation. Les droits réels de super administrateur maintiennent l’accès au sélecteur, sans accorder leur contournement aux sections simulées. Aucun rôle simulé n’est transmis à l’API et aucune permission persistée n’est modifiée. Les données restent soumises aux droits réels du compte. Le type Personnel / Étudiant et le périmètre des diplômes restent inchangés.
