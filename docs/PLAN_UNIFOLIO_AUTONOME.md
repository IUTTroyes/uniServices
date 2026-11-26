# UniFolio autonome : audit et plan d'action

Date : 26 novembre 2026.
Méthode : lecture et recherche dans le code, des appels en lecture à l'API ORéBUT. **Rien n'a été exécuté** (ni application, ni tests, ni imports).

## 1. Objectif et architecture cible

Distribuer UniFolio seul **aux IUT**, avec le même code que l'intranet. **ORéBUT, outil national, fournit les référentiels de compétences** (décision du 26 novembre 2026).

```text
back/                       cœur PHP : entités, connexion, droits, API
packages/auth-bundle        connexion, portail, administration
packages/formation-bundle   structure, maquettes, compétences, étudiants (à créer)
packages/unifolio-bundle    portfolio

intranet = cœur + auth + formation + intranet + unifolio + autres bundles
unifolio = cœur + auth + formation + unifolio
```

- Un seul dépôt, un seul `back/`.
- La variable `APP_DISTRIBUTION` choisit les bundles (`back/config/distributions/<nom>.php`).
- `formation-bundle` remplace le `referentiel-bundle` envisagé d'abord : UniFolio a besoin de plus que les compétences (voir 2.1).

## 2. Constats de l'audit

### 2.1 Données nécessaires à UniFolio

```text
Département > Diplôme (type APC) > PN > Année > Semestre > Groupes
Diplôme > Référentiel > Compétences > Niveaux > Apprentissages critiques
Étudiant > Scolarité > Inscription semestrielle > Semestre (+ groupes)
```

Le diplôme d'un étudiant est retrouvé par `semestre -> annee -> pn -> diplome`. **Le PN est donc obligatoire**, même si UniFolio n'exploite que les compétences.

### 2.2 Écrans à sortir de l'intranet

| Besoin | Emplacement actuel (`packages/intranet-bundle/assets/`) |
| --- | --- |
| Départements | `super-administration/departement(s)` |
| Types de diplômes | `super-administration/type(s)-diplome(s)` |
| PN | `views/Pn/PnView.vue`, `components/Pn/Fiche*.vue` |
| Compétences | `views/Ref-Competences/`, `components/Pn/AfficheReferentielCompetences.vue`, `ApcCompetence.vue` |
| Groupes | `super-administration/groupe/:groupeId` |
| Étudiants | `views/Etudiants/`, `components/Etudiant/EtudiantAdd*.vue` |
| Personnels | `views/Personnels/` |

Les années universitaires, diplômes et accès sont déjà dans `auth-bundle`.

### 2.3 Import des étudiants

| Constat | Gravité |
| --- | --- |
| Tout étudiant importé reçoit le mot de passe `test` (`back/src/Controller/Etudiant/CreateEtudiantController.php`) | **Critique** |
| L'import Apogée est une coquille : le contrôleur répond `ok` sans rien importer | Élevée |
| Contrôleur présent en double (`back/` et `intranet-bundle`), avec la même route `/api/etudiants/new` | Élevée |
| Domaine `@etudiant.univ-reims.fr` écrit en dur ; identifiant `prenom.nom` sans gestion des homonymes | Élevée |
| Application attribuée en dur : `Intranet` (UniFolio resterait invisible) | Élevée |
| D'après le code, un nouvel étudiant est ajouté à **tous** les groupes de chaque semestre | Élevée, à confirmer |
| Un code étape inconnu interrompt tout l'import au lieu de signaler la ligne | Moyenne |
| Le fichier `assets/files/import_etudiant.csv` (colonne `code_semestre`) ne correspond plus au modèle généré par l'écran (`annee_code_etape`) | Faible |
| Colonnes `specialite_bac` et `sexe` : non reprises à la création | Faible |

### 2.4 Référentiels de compétences

- (Avant l'étape 4) La synchronisation (`SynchroRefCompetences`, supprimée depuis) interroge **déjà ORéBUT**, avec une URL en dur. Les réglages `integrations.orebut` de l'établissement ne sont pas lus.
- Le référentiel créé n'est pas rattaché au diplôme : UniFolio risque de n'afficher aucune compétence.
- Chaque synchronisation crée un nouveau référentiel (doublons, traces liées à l'ancien).
- L'année du niveau et la version du programme sont ignorées.
- Je n'ai pas vu d'écran de modification des compétences.
- OReOF n'est utilisé que pour le PN (`SynchroRefFormation`, URL de l'université de Reims en dur). Il reste propre à l'intranet.

Ce que l'API publique ORéBUT expose (lecture sans authentification) :

| Adresse | Contenu observé |
| --- | --- |
| `/api/specialite/{sigle}/competences` | Compétences, composantes essentielles, situations professionnelles, niveaux par année, apprentissages critiques. Testé sur MMI (« Programme 2021 »). Pas de parcours |
| `/api/departements` | Spécialités de BUT, avec compétences, **parcours** (code, libellé, couleur) et liens parcours / niveau (compétence et année), dates de version |
| `/api/apc_saes` | SAÉ : semestre, compétences, apprentissages critiques, volumes |
| `/api/apc_ressources` | Ressources : semestre, compétences, apprentissages critiques, volumes |

Limites : seul le début des trois dernières réponses a été lu en détail ; l'entrée MMI de `/api/departements` n'a pas été vérifiée ; aucun filtre par spécialité n'a été vu sur les SAÉ et ressources, et leurs parcours étaient vides dans la partie lue.

### 2.5 Couplage du cœur avec les bundles

- 8 relations `OneToMany` vers Intranet et Helpdesk : `ScolEnseignement`, `EdtEvent`, `EtudiantScolariteSemestre`, `Personnel` (3), `StructureAnneeUniversitaire`, `StructureService`.
- Classes de bundles citées dans : `DepartmentScope`, `PostVoter`, `DepartmentCollectionAccessExtension`, `AcademicResultAccessExtension`, classes `AnneeUniversitaire/*`, services et contrôleurs EDT. Vérifier aussi `EdtAppel` dans `EdtEvent`.
- Réglages propres aux bundles en dur : `doctrine.yaml`, `api_platform.yaml`, `routes.yaml`, `services.yaml` (Parcauto).

### 2.6 Installation et déploiement

| Constat | Gravité |
| --- | --- |
| Les migrations ne créent pas le schéma complet (5 fichiers, 4 `CREATE TABLE`) : une base vide ne peut pas être construite avec elles | **Bloquant** |
| Seules 2 migrations sur 5 sont suivies par Git ; les autres sont ignorées par `.gitignore` | **Bloquant** |
| `docker/Dockerfile` copie `packages/edt-bundle/package.json`, dossier absent | Bloquant pour l'image |
| Un seul initialiseur (`EtablissementInitializer`) : rien pour créer le premier administrateur ou les types de diplômes (à confirmer) | Élevée |
| Aucun fichier `LICENSE` à la racine ; `back/composer.json` est marqué `proprietary` | À décider |
| Valeurs propres à l'établissement en dur (e-mails `univ-reims.fr`, flux RSS, pied de page) | Moyenne |

### 2.7 Portail et divers

- Un étudiant sans réglage ne reçoit que l'application `Intranet` (`UserPackageResolver`).
- La barre du haut appelle toujours l'API du bundle message.
- `auth-bundle` contient un contenu propre à Finance (`TableauFinance.vue`) et une copie de `AuthController`.
- La connexion se fait avec des comptes locaux ; le LDAP sert à rechercher des personnels.

## 3. Plan d'action

Étapes 1 et 2 et migration initiale réalisées (voir `docs/DISTRIBUTIONS.md`). Reste notamment : lancer les tests en CI (job « architecture » ajouté, pas encore exécuté sur GitHub). Les scripts de bundles sont adaptés. Les constats de la section 2 décrivent l'état **avant** ces travaux.

Taille indicative : S (petit), M (moyen), L (gros).

### Étape 0 : urgences, utiles même sans distribution

- Les mots de passe `test` des fixtures (`back/src/DataFixtures/`) sont **conservés** : ils servent au développement.
- [ ] (S) À décider : l'import CSV (`CreateEtudiantController`, hors fixtures) attribue lui aussi `test`, y compris hors développement. Proposition : mot de passe aléatoire et lien d'activation.
- [x] (M) Créer une migration initiale du schéma et suivre toutes les migrations dans Git. Prévoir le marquage « déjà exécutée » sur les bases existantes.
- [ ] (S) Supprimer les contrôleurs en double (étudiants, Apogée, `AuthController`) après `debug:router`.

**Terminé quand** : une base vide se construit par les migrations.

### Étape 1 : découpler le cœur (L)

- [x] Supprimer les relations inverses, en remplaçant les `cascade` et `orphanRemoval` perdus.
- [x] Remplacer les classes citées en dur par des interfaces implémentées par les bundles.
- [x] (job « architecture » de la CI) Ajouter un contrôle CI : `back/src` n'importe aucun bundle (hors reprise V3).

**Terminé quand** : le contrôle passe.

### Étape 2 : les deux distributions (M)

- [x] `APP_DISTRIBUTION`, listes de bundles et cache séparé dans `App\Kernel`.
- [x] Chaque bundle apporte son mapping Doctrine, ses ressources API et ses services.
- [x] Registre front et `tools.generated.json` par distribution.
- [ ] Adapter `scripts/*-bundle.php`.
- [x] Séparer les migrations du cœur et des bundles.
- [ ] CI : démarrer la distribution `unifolio` sur une base vide et lancer ses tests.

**Terminé quand** : `APP_DISTRIBUTION=unifolio` démarre sans aucun autre bundle.

### Étape 3 : `formation-bundle` (L)

Écrans déplacés sous `/formation`. Restent : les droits avec un compte référent, et un essai dans l'application. La création d'un diplôme et d'un PN sans OReOF reste à vérifier.

- [x] Créer le bundle (`scripts/create-bundle.php`).
- [x] Déplacer les écrans du 2.2, avec redirections des anciennes adresses.
- [x] Vérifier qu'ils n'appellent aucune API propre à l'intranet.
- [ ] Vérifier que les droits sont attribuables sans le paquet `intranet`.
- [x] Rendre les vues de formation faciles à trouver : une entrée « Formations » dans le portail et dans la configuration, visible selon le rôle, dans les deux distributions.
- [ ] Vérifier le parcours complet avec un compte référent, pas seulement super administrateur.

**Terminé quand** : toute la chaîne du 2.1 se met en place dans la distribution `unifolio`.

### Étape 4 : référentiels depuis ORéBUT (M)

- ORéBUT et UniServices ont le même auteur : pas d'autorisation à obtenir, et l'API peut être adaptée. Les programmes sont nationaux.
- [x] Choisir la spécialité dans une liste (`/api/departements`).
- [x] Importer compétences, niveaux, apprentissages critiques et parcours.
- [x] Mise à jour sans recréation, rattachement au diplôme, année du programme conservée.
- [x] URL lue dans les réglages `integrations.orebut` (le réglage « activé » n'est pas pris en compte).
- [x] Prévisualiser les changements avant écriture.
- [ ] Essayer l'écran avec un compte référent, puis vérifier ce que voit un étudiant.
- [ ] Côté ORéBUT : exposer un identifiant stable par compétence et par apprentissage critique (`numeroIdentifiant` vaut 0 ; des noms courts et un code d'apprentissage sont en double). À stocker ensuite dans UniServices.

Vérifié sur une base temporaire avec les 24 spécialités : import sans erreur, second passage sans changement.

### Étape 4 bis : créer la formation depuis ORéBUT (M)

- [ ] À partir d'une spécialité et de ses parcours : proposer diplômes, PN, années et semestres.
- [ ] Faire saisir les codes étape (`annee_code_etape`), nécessaires à l'import des étudiants.
- [ ] En option : reprendre les SAÉ et ressources nationales, après avoir vérifié leur filtrage par spécialité.

**Terminé quand** : un IUT installe une spécialité sans saisir sa structure à la main, hors groupes.

### Étape 5 : groupes, étudiants, personnels (M)

- [ ] Un service d'import unique, avec simulation et rapport ligne par ligne.
- [ ] Modèles téléchargeables (4.1 à 4.3), produits par une seule source.
- [ ] Domaine de messagerie et applications par défaut configurables.
- [ ] Affectation aux groupes indiqués dans le fichier, et à eux seuls.
- [ ] Gestion des homonymes.
- [ ] Apogée : développer le connecteur ou retirer l'écran.

**Terminé quand** : après la création depuis ORéBUT, les groupes, étudiants et personnels fictifs s'importent par fichiers.

### Étape 6 : distribution (M)

- [ ] Corriger le Dockerfile et construire une image `unifolio`, avec le moteur de rendu.
- [ ] Commande d'installation `bin/console uniservices:installer`, relançable sans doublon :
  - vérifie la configuration (base, clés JWT, messagerie, rendu Node) ;
  - applique les migrations (suppose l'étape 0) ;
  - initialise l'établissement (`InitializationRunner`, appelé aujourd'hui par la seule reprise V3) ;
  - crée le premier administrateur, mot de passe saisi à l'installation ;
  - crée le type de diplôme BUT et l'année universitaire ;
  - installe les grilles (`unifolio:grilles:init`).
  Elle n'utilise pas les fixtures, réservées aux environnements `dev` et `test`.
- [ ] Retirer les valeurs en dur de l'établissement.
- [ ] Points du portail (2.7).
- [ ] Licence, guide d'installation et de mise à jour.

**Terminé quand** : une personne extérieure installe UniFolio avec le seul guide.

## 4. Modèles d'import proposés

Ces formats sont des **propositions** : aucun importateur ne les lit encore. Les données sont fictives. CSV en UTF-8, séparateur `;`.

Aucun modèle n'est prévu pour les référentiels : ils viennent d'ORéBUT.

### 4.1 Groupes

La structure (diplômes, PN, années, semestres) est créée depuis ORéBUT (étape 4 bis). Il reste les groupes :

```csv
annee_code_etape;semestre_ordre;groupe_type;groupe_libelle;groupe_parent
EXE1;1;TD;TD A;
EXE1;1;TP;TP A1;TD A
```

### 4.2 Étudiants

```csv
numero_etudiant;numero_ine;nom;prenom;mail;date_naissance;annee_code_etape;groupe_td;groupe_tp;annee_promotion;annee_bac;telephone;adresse1;adresse2;adresse3;codepostal;ville
00000001;000000000AA;Exemple;Camille;camille.exemple@example.org;15/03/2007;EXE1;TD A;TP A1;2029;2026;;;;;;
```

Obligatoires : `numero_etudiant`, `nom`, `prenom`, `mail`, `annee_code_etape`. Nouveautés par rapport à l'écran actuel : `mail` et les groupes. L'année universitaire reste choisie à l'écran. Aucun mot de passe dans le fichier.

### 4.3 Personnels

```csv
nom;prenom;mail;identifiant;departement;roles
Exemple;Dominique;dominique.exemple@example.org;dexemple;Informatique;ROLE_TEACHER|ROLE_REFERENT_PORTFOLIO
```

Rôles UniFolio : `ROLE_REFERENT_PORTFOLIO`, `ROLE_PORTFOLIO_CORRECTOR`, `ROLE_PORTFOLIO_PUBLISHER`. Le paquet `unifolio` doit être activé sur l'affectation.

## 5. Décisions

Prises : la version autonome est destinée aux IUT ; les référentiels viennent d'ORéBUT.

À prendre :

| Question | Proposition par défaut |
| --- | --- |
| Quelle licence ? | À choisir avant toute diffusion, avec l'établissement |
| Connexion CAS nécessaire ? | Comptes locaux d'abord, CAS ensuite |
| MySQL seul ? | Oui, comme l'image Docker actuelle |
| Apogée dans la version autonome ? | Non : fichiers seulement |

## 6. À vérifier

- Parcours MMI dans `/api/departements`, et fin des réponses ORéBUT non lue.
- Démarrage réel de la distribution `unifolio` (d'autres couplages apparaîtront probablement).
- Création des personnels et de leur mot de passe (non auditée).
- Comportement réel de la synchronisation ORéBUT et du rattachement au diplôme.
- Droits sur les référentiels et diplômes sans le paquet `intranet`.
- Données personnelles : hébergement, durée de conservation, portfolios publics.
