# Questionnaires — fonctionnement et règles

## Droits (`Security/QuestionnaireAccess`)

| Profil | Droits |
|---|---|
| Super administrateur | Tout, y compris les questionnaires sans département (établissement) |
| Gestionnaire (`ROLE_QUESTIONNAIRE_MANAGER` ou `ROLE_QUALITE`) | Tous les questionnaires **des départements où il a ce rôle** (`DepartmentPermissionChecker`) |
| Auteur | Ses questionnaires (consultation, paramètres, publication, relances, résultats) ; suppression de ses brouillons seulement ; déplacement vers un département qu'il gère |
| Référent qualité du département (réglages) | Consultation des questionnaires et résultats du département, bilans ; pas de gestion |
| Destinataire des résultats (diffusion validée) | Après publication, résultats agrégés des seules sections qui le concernent ; retour (commentaire, plan d'action) |
| Répondant | Accès par le **jeton** de son invitation uniquement (route publique `/app/questionnaire-reponse/<jeton>`) |

- Voter : `QUESTIONNAIRE_VIEW`, `QUESTIONNAIRE_MANAGE`, `QUESTIONNAIRE_DELETE`, `QUESTIONNAIRE_CREATE`, `QUESTIONNAIRE_SYNTHESE`.
- Listes filtrées par `Doctrine/QuestionnaireVisibiliteExtension` (questionnaires, sections, questions, invitations).
- Le jeton n'est jamais exposé à l'équipe ; pour un questionnaire anonyme, l'API ne livre pas les réponses individuelles.
- Champs tenus par le serveur (statut, publication, auteur…) refusés en 422 (`State/QuestionnaireProcessor`) ;
  structure (sections, questions) figée après publication (409, `State/QuestionnaireStructureProcessor`) ;
  anonymat et seuil figés après publication.

## Réglages du département (`Services/QuestionnaireReglages`)

Stockés dans les options du département (`structure_departement.opt`, clé `questionnaire`), modifiables par les
gestionnaires du département (écran *Configuration*, API `GET|PUT /api/questionnaire/reglages/{département}`).
Ce qui vaut pour une enquête est **recopié sur le questionnaire** à sa création ou à sa publication.

| Réglage | Défaut | Effet |
|---|---|---|
| `anonymat` | oui | `opt.anonymous` des nouveaux questionnaires (si l'éditeur ne le fixe pas) |
| `modification_reponses` | oui | `opt.modificationReponses` : modifier ses réponses envoyées tant que c'est ouvert |
| `duree_jours` | 14 | À la publication sans date de clôture : ouverture + N jours |
| `cloture_auto` | oui | Clôture automatique (date atteinte ou tous répondus) ; sinon action « Clôturer » |
| `responsable_qualite` | — | Personnel du département : consultation et bilans |
| `seuil_min_reponses` | 5 | `opt.seuilMinReponses` : questionnaire anonyme, détail et exports masqués sous le seuil (questionnaire et section) |
| `publication_auto` | oui | Résultats publiés à la fin de la relecture (diffusion validée) |
| `delai_relecture_jours` | 7 | Durée entre la clôture et la publication automatique |
| `conservation_annees` | 5 | Purge des réponses après la clôture (statistiques archivées) |

## Notifications et e-mails

Types (`Enum/TypeNotificationEnum`, source `questionnaire`), envoyés par `Services/Notification/QuestionnaireNotifier`
(seul point d'envoi) :

| Clé | Public | Mode par défaut | Modes permis |
|---|---|---|---|
| `questionnaire.invitation_etudiant` / `_personnel` | étudiant / personnel | tout de suite | tout de suite, résumé |
| `questionnaire.relance_etudiant` / `_personnel` | étudiant / personnel | tout de suite | tout de suite, jamais |
| `questionnaire.completed` (bilan) | personnel | tout de suite | tous |
| `questionnaire.resultats` | personnel | tout de suite | tous |
| `questionnaire.retour` | personnel | résumé | tous |

- Une adresse qui correspond à un compte (adresse universitaire ou personnelle) est **notifiée** (cloche + e-mail
  selon ses préférences) ; une adresse extérieure reçoit un **e-mail direct** (`questionnaire.invitation`,
  `questionnaire.reminder`, catégorie transactionnelle).
- Chaque type a un e-mail dédié de même clé ; les variantes par public héritent (`getParentKey()`) du modèle de base.
- Contexte des e-mails en valeurs simples (`Services/QuestionnaireMailer`), liens absolus vers `URL_FRONTEND`.

## Diffusion ciblée des résultats (`Services/Diffusion/DiffusionService`)

Les sections répétées (une par matière, ressource, SAÉ ou ligne de prévisionnel) gardent l'enseignement évalué et, pour
le prévisionnel, l'intervenant (`QuestionnaireSectionInstance::enseignement`, `personnelCible`).

1. **Collecte** : questionnaire publié.
2. **Relecture** : à la clôture (automatique ou manuelle), la diffusion est pré-remplie : intervenant évalué, sinon
   intervenants de l'enseignement d'après le prévisionnel de l'année (package intranet, lu s'il est installé), sinon
   d'après l'emploi du temps ; les sections générales sont à compléter. L'équipe ajuste puis valide
   (page *Diffusion des résultats*, API `GET|PUT /api/questionnaires/{uuid}/diffusion`, `POST …/diffusion/preremplir`).
3. **Publication** : manuelle (`POST …/resultats/publier`) ou automatique à la fin de la relecture (réglages
   `publication_auto`, `delai_relecture_jours`), seulement si la diffusion est validée. Chaque destinataire est notifié
   une fois (`questionnaire.resultats`) ; un destinataire ajouté après la publication est prévenu aussitôt.

Un destinataire ne voit que **ses** sections (`QuestionnaireAccess::sectionsVisibles`) : page web accessible
*Mes résultats* (`/app/questionnaire/qualite/enquetes/resultats/{uuid}`, tableaux sans graphique), exports PDF / Excel par
section (`?sections=…`), commentaire et plan d'action (`GET|PUT …/retours`, notification `questionnaire.retour` à
l'auteur et au référent qualité). Le seuil d'anonymat s'applique au questionnaire **et à chaque section**.

## Modèles et comparaison d'une année sur l'autre

- Un **modèle** (`estModele`) ne se publie pas ; il sert à créer les campagnes (`POST …/instancier`), qui gardent le lien
  `modeleOrigine`. Modèles de département (gestionnaires du département) ou d'établissement (super administrateur ;
  consultables et réutilisables par tout gestionnaire). Un questionnaire s'enregistre comme modèle (`POST …/modele`).
- Chaque question garde sa **question d'origine** (`origine`) au fil des copies : `GET …/comparaison` compare les campagnes
  d'un même modèle (participation, moyennes des échelles, répartition des choix), campagne par campagne (année
  universitaire). Une campagne sous le seuil d'anonymat ne contribue qu'à la participation.

## Durée de conservation (`questionnaire:purger`)

Au-delà de `conservation_annees` (5 ans par défaut) après la clôture, les statistiques agrégées sont archivées
(`resultatsArchives`, `purgeLe`), puis les réponses sont supprimées et les invitations anonymisées (adresse, lien,
historique des relances). Les statistiques, exports et comparaisons restent disponibles à partir de l'archive.

## Tâches planifiées

```bash
php bin/console questionnaire:taches:installer   # une fois par environnement (--inactives pour les créer désactivées)
php bin/console app:questionnaire:send-reminders [--days=3] [--dry-run]
php bin/console app:questionnaire:check-completed [--dry-run]   # clôture, bilans, pré-remplissage, publication automatique
php bin/console questionnaire:purger [--dry-run]                # durée de conservation
```

## Tests

Mise en place (une fois) : base `<base>_test` accessible (`back/.env.test.local`), puis `make test-db` (à relancer après
chaque changement de schéma). Sans base de test, les tests d'API sont ignorés.

```bash
vendor/bin/phpunit --testsuite QuestionnaireBundle      # depuis la racine du dépôt
npx vitest run packages/questionnaire-bundle            # tests de rendu des composants
```

| Dossier | Contenu |
|---|---|
| `tests/Security` | `QuestionnaireAccess` : rôle limité au département, établissement, auteur, référent qualité, destinataires de synthèse |
| `tests/Service` | `QuestionnaireReglages` : valeurs par défaut, validation, recopie à la création et à la publication |
| `tests/Domain` | Ouverture, seuil d'anonymat, adresses d'invitation, réponse vide |
| `tests/Notification` | Types (définitions, publics, modes, e-mails dédiés) et `QuestionnaireNotifier` (compte notifié / adresse extérieure) |
| `tests/Api` | De bout en bout (noyau de test, JWT réel, transaction annulée) : droits par profil et département, champs refusés, structure figée, réponses par jeton, invitations et relances avec e-mails capturés, réglages, notifications, tâches, diffusion ciblée et retours, modèles et comparaison, purge |
| `assets/tests` | Vitest : extraction des adresses, fenêtres de relance et d'invitation, widget, tableau de bord, restitution accessible, modèles, comparaison |

Les e-mails sont capturés par un écouteur `MessageEvent` (`QuestionnaireApiTestCase::capturerEmails()`) avec
`MAILER_DSN=null://null` : rien ne part réellement.
