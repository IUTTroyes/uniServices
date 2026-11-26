# Rôles et accès

Ce document réunit les rôles de l'application, ce qu'ils permettent et la façon dont ils sont attribués. Il complète [VOTERS.md](VOTERS.md), qui décrit l'écriture technique des règles. Les utilisateurs retrouvent une version simplifiée dans l'application : **Profil → Comprendre mes accès** (`/profil/acces`).

## 1. Principes

| Principe | Explication |
| --- | --- |
| Deux types de comptes | `Personnel` et `Etudiant`. Seul le personnel porte des rôles métier. Un étudiant voit uniquement ses propres données (profil, notes, absences, stage, questionnaires reçus). |
| Les rôles s'attribuent **par affectation** | Une personne est affectée à un ou plusieurs départements (`StructureDepartementPersonnel`). Chaque affectation porte une liste de rôles et de modules (packages). Le même rôle donné dans le département A ne vaut pas dans le département B. |
| Les modules s'activent par affectation | Un rôle n'a d'effet que si son module est actif sur l'affectation (`intranet`, `finance`, `stages`, `helpdesk`, `questionnaire`, `unifolio`). |
| Département actif | Le portail travaille dans un département à la fois (sélecteur du bandeau). Il sert à la navigation et aux contrôles généraux. Une écriture est toujours contrôlée sur le département propriétaire de la ressource. |
| Héritage | Certains rôles en incluent d'autres (voir §3). L'héritage ne sort jamais de son module. |
| Super administrateur | `SUPER_ADMIN` donné par une affectation s'applique à toute l'application. |
| Responsable de parcours | `ROLE_RESP_PARCOURS` est limité aux diplômes désignés dans l'affectation. Il n'est jamais hérité. |
| Filtrage serveur | Les listes sont filtrées côté serveur. Masquer un bouton dans l'interface ne sert qu'au confort : le serveur refuse l'action de toute façon. |

Où les attribuer : **Configuration → Gestion des accès** (administrateurs autorisés sur le département). Les services de l'établissement (Configuration → Services, super administrateur) ne portent aucun droit : ils servent à imputer les demandes d'achat.

## 2. Rôles du noyau (module intranet)

Définis dans `back/src/Security/CorePermissionProvider.php`.

### Direction et administration

| Rôle | Libellé | Portée et usage |
| --- | --- | --- |
| `SUPER_ADMIN` | Super administrateur | Tout, dans toute l'application : configuration, référentiels, import d'étudiants, services, suppression. |
| `ROLE_ADMIN` | Administrateur | Hérite de DIRECTION, CHEF_DEPARTEMENT, CHEF_DEPT, DIRECTEUR_ETUDES, SCOLARITE, ASSISTANT, NOTES_MANAGER, EMAIL_ADMIN, PERMANENT. Gère le département : structure, personnel, étudiants, enseignements, APC, documents, actualités, suppressions. |
| `ROLE_DIRECTEUR_IUT`, `ROLE_DIRECTEUR_ADJOINT_IUT`, `ROLE_CHEF_SERVICES_ADMINISTRATIFS` | Direction de l'IUT | Activés sur toutes les affectations de la personne. Repérés par `isDirectionIut`. |
| `ROLE_CHEF_DEPT` / `ROLE_CHEF_DEPARTEMENT` | Chef de département | Pilote son département : personnel, scolarité, prévisionnel, évaluations, stages, questionnaires, actualités. `CHEF_DEPARTEMENT` inclut `CHEF_DEPT`. |
| `ROLE_DIRECTION` | Direction (historique) | Conservé pour compatibilité. |
| `ROLE_DIRECTEUR_ETUDES` | Directeur des études | Scolarité, absences, EDT, prévisionnel, plans de cours, questionnaires (lecture). |
| `ROLE_RESP_PARCOURS` | Responsable de parcours | **Limité aux diplômes désignés.** Scolarité, APC, notes, évaluations, prévisionnel, plans de cours de ses diplômes. |
| `ROLE_EMAIL_ADMIN` | Gestionnaire des emails | Gestion des emails du portail. |

### Scolarité et enseignement

| Rôle | Libellé | Ce qu'il permet |
| --- | --- | --- |
| `ROLE_SCOLARITE` | Scolarité | Dossiers étudiants et scolarité, absences, justificatifs. Peut éditer, pas supprimer certaines données réservées à l'administrateur. |
| `ROLE_ASSISTANT` | Assistant de formation | Lecture/édition des données de scolarité, absences, prévisionnel (lecture). |
| `ROLE_NOTES_MANAGER` | Responsable des notes | Voit et saisit toutes les évaluations et notes du département, supprime des évaluations. |
| `ROLE_ABSENCE` | Responsable des absences | Suivi des absences et justificatifs. |
| `ROLE_EDT` | Responsable des emplois du temps | Crée et modifie les cours de l'EDT, contraintes, créneaux, progression. |
| `ROLE_EDUSIGN` | Référent Edusign | Intégration Edusign. |
| `ROLE_PERMANENT` | Personnel permanent | Lecture élargie : scolarité, notes, absences, prévisionnel du département. Inclus dans ADMIN. |
| `ROLE_TEACHER` | Enseignant | Saisit les notes des évaluations qui lui sont attribuées ; plans de cours des enseignements où il intervient. |
| `ROLE_BIATSS` | BIATSS | Personnel administratif et technique : repère de profil. |

### Services et référents

| Rôle | Libellé | Usage |
| --- | --- | --- |
| `ROLE_RELAI_COMM` | Référent communication | Gère les actualités du département avec les administrateurs et chefs de département. |
| `ROLE_REFERENT` | Référent UniServices | Référent applicatif du département. |
| `ROLE_REFERENT_INTRANET` | Référent Intranet | Contenus de l'intranet. |
| `ROLE_REFERENT_PROJET` | Référent Projet | Suivi de projets. |
| `ROLE_INFORMATIQUE` | Service informatique | Repère de service. |
| `ROLE_COMPTA` (inclut `ROLE_COMPTABILITE`) | Service comptabilité | Consultation et édition des heures du prévisionnel (HRS). **Distinct** du service financier du module Finances. |
| `ROLE_QUALITE` | Service qualité | Crée, modifie, publie les questionnaires ; supprime. |

## 3. Rôles des modules

| Module | Rôle | Libellé | Ce qu'il permet |
| --- | --- | --- | --- |
| Finances (`finance`) | `ROLE_FINANCE_VIEW` | Accès aux finances | Accès au module, suivi de ses propres demandes. Accordé par défaut avec le module. |
|  | `ROLE_FINANCE_DEMANDEUR` | Demandeur | Crée des demandes d'achat dans ses départements (ou pour un service, dans n'importe lequel), dépose le PDF signé, constate le service fait. Inclut VIEW. |
|  | `ROLE_FINANCE_VALIDEUR` | Valideur | Voit les demandes de ses départements et rend l'avis de l'étape 1. Inclut VIEW. |
|  | `ROLE_FINANCE_MANAGER` | Gestionnaire = **service financier** | Toutes les demandes de l'établissement (voir §4). Inclut VIEW et DEMANDEUR. |
| Stages (`stages`) | `ROLE_STAGE_VIEW` | Voir les stages | Lecture des stages du département. Accordé par défaut avec le module. |
|  | `ROLE_STAGE_MANAGER` | Responsable des stages | Gère périodes, dossiers, avenants, soutenances de **son** département. Inclut VIEW. |
| Helpdesk (`helpdesk`) | `ROLE_HELPDESK_AGENT` | Agent de support | Traite les tickets. |
|  | `ROLE_HELPDESK_ADMIN` | Administrateur Helpdesk | Administration ; inclut AGENT. |
| Questionnaires (`questionnaire`) | `ROLE_QUESTIONNAIRE_MANAGER` | Administrateur Questionnaires | Gestion des questionnaires du module. |
| Portfolio (`unifolio`) | `ROLE_REFERENT_PORTFOLIO` | Référent Portfolio | Pilote les campagnes et modèles. |
|  | `ROLE_PORTFOLIO_CORRECTOR` | Correcteur | Évalue les portfolios. |
|  | `ROLE_PORTFOLIO_PUBLISHER` | Responsable publication | Publie les portfolios. |

Règles communes : un ticket peut être créé par tout compte connecté, modifié par son auteur ou un agent ; l'auteur d'un dossier de stage (étudiant) modifie son dossier et ses avenants ; les étudiants peuvent répondre aux questionnaires auxquels ils sont invités.

## 4. Module Finances en détail

### Qui est le service financier ?

Le service financier n'est pas une structure : c'est toute personne qui a `ROLE_FINANCE_MANAGER` dans **au moins un** département. Le rôle est enregistré sur l'affectation, mais son effet couvre **tout l'établissement**. Les **services** de l'établissement (Informatique, Patrimoine…) n'ont pas de droits : ils servent seulement à imputer une demande.

| Action | Demandeur | Valideur | Service financier | Super admin |
| --- | :-: | :-: | :-: | :-: |
| Créer une demande (département) | dans ses départements | non | seulement s'il a aussi le rôle demandeur du département | oui |
| Créer une demande (service) | rôle demandeur quelque part | non | oui | oui |
| Voir une demande | les siennes | celles de ses départements | **toutes** | toutes |
| Modifier / déposer le PDF signé | brouillon, soumise | non | jusqu'à la clôture | oui |
| Avis de l'étape 1 | non | département | demandes de service | oui |
| Constater le service fait (bons de livraison) | oui | non | oui | oui |
| Viser chaque réception, code MIGO | non | non | oui | oui |
| Vérification, engagement, transmission, certification, facture, paiement, clôture | non | non | oui | oui |
| Supprimer | son brouillon | non | à tout statut | oui |
| Fournisseurs, TVA | non | non | oui | oui |
| Notifications et récapitulatif | les siennes | département | **toutes les demandes en cours** | oui |

Attention : un rôle gestionnaire donné par erreur dans un seul département ouvre toutes les demandes. À réserver à l'équipe financière.

### Imputation

Une demande est imputée à un **département** (rempli d'après la personne connectée) **ou** à un **service** (jamais les deux). Une demande de service est déposée par une personne autorisée de l'établissement et instruite par le service financier.

## 5. Matrice par domaine

`●` écriture · `○` lecture · `—` aucun accès (hors super administrateur, qui a tout).

| Domaine | Admin | Chef dépt | Dir. études | Resp. parcours | Scolarité | Assistant | Notes | Permanent | Enseignant |
| --- | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: | :-: |
| Structure du département | ● | ● | ○ | ○ | ○ | ○ | — | — | — |
| Personnel et affectations | ● | ● (sauf rôles) | — | — | — | — | — | — | — |
| Étudiants (fiche) | ● | ● | ○ | ○ | ● | ○ | — | ○ | — |
| Scolarité | ● | ● | ● | ● | ● | ● | — | ○ | — |
| Évaluations | ● | ● | ○ | ● | — | — | ● | ○ | ses évaluations |
| Notes | ● | ● | — | ● | — | — | ● | ○ | ses évaluations |
| Absences et justificatifs | ● | ● | ● | — | ● | ○ | — | ○ | — |
| EDT (cours) | ● | ● | ● | — | — | — | — | ● (lecture élargie) | — |
| Enseignements, UE | ● | ● | ● | ● | — | — | — | — | — |
| APC (référentiels) | ● | ● | — | ● | — | — | — | — | — |
| Prévisionnel | ● | ● | ● | ● | — | ○ | — | ○ | ses heures |
| Plans de cours | ● | ● | ● | ● | — | — | — | — | les siens |
| Questionnaires | ● | ● | ○ | ● | — | — | — | ○ | — |
| Stages | ● | ● | — | — | ○ | — | — | — | — |
| Documents du département | ● | ● | — | — | — | — | — | — | — |
| Actualités | ● | ● | — | — | — | — | — | — | — |

La matrice résume les règles principales ; pour une décision précise, consulter le voter du domaine ([VOTERS.md](VOTERS.md)). Les écritures sont toujours limitées au département de la ressource.

## 6. Réservé au super administrateur

Configuration du portail (services, référentiels globaux), import CSV d'étudiants, suppression de ressources institutionnelles sans département, simulation de rôles (outil d'aide : le serveur applique toujours les droits réels).

## 7. Pour modifier une règle

1. Rôle ou héritage : provider du module (`*PermissionProvider.php`).
2. Décision d'accès : voter du domaine, puis tests (`tests/Security`, `FinanceSecuriteApiTest`…).
3. Interface : `shared/utils/roleCatalog.js` (liste des rôles, simulation) et le catalogue d'aide `packages/intranet-bundle/assets/data/aideAcces.js`.
4. Mettre à jour ce document et la page d'aide dans la même modification.
