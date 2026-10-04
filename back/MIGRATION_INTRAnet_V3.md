# Migration Intranet V3 vers UniServices

> Document de travail --- branche `feat/intranet-v3-migration`

Ce document décrit l'architecture de migration mise en place pour
reprendre les données de l'ancien **intranet V3** dans **UniServices**,
les commandes disponibles, les données actuellement migrées et les
éléments volontairement exclus ou restant à traiter.

## 1. Principes généraux

La migration est organisée par domaine fonctionnel afin de conserver le
découpage modulaire d'UniServices :

-   le **Core** migre les données communes : structure, utilisateurs,
    scolarité, référentiels, notes, absences, EDT, prévisionnels,
    droits, etc. ;
-   le **StageBundle** possède sa propre commande et ses propres
    migrateurs ;
-   le **DocumentBundle** possède également sa propre commande et ses
    propres migrateurs.

La base V3 est accessible par une connexion Doctrine DBAL dédiée nommée
`copy`, configurée via `DATABASE_URL_ORIGINAL`. La base UniServices
reste la connexion Doctrine principale.

Les migrateurs réutilisent le framework commun situé dans :

``` text
back/src/Migration/IntranetV3/
```

Il fournit notamment :

-   `MigratorInterface`
-   `AbstractMigrator`
-   `MigrationContext`
-   `MigrationResult`
-   le mécanisme de dépendances entre migrateurs ;
-   le traitement par lots ;
-   les barres de progression ;
-   le mode `dry-run`.

Le traitement par lots utilise actuellement une taille de **200
lignes**.

### Identifiants V3

Lorsque cela est nécessaire, les entités cibles conservent un champ
`oldId`.

Cet identifiant permet :

-   de retrouver l'entité UniServices correspondant à une ligne V3 ;
-   de résoudre les relations entre données migrées ;
-   de rendre les imports relançables ;
-   de faciliter les contrôles et investigations après migration.

`oldId` est une référence technique de migration et n'a pas vocation à
devenir l'identifiant fonctionnel de l'entité.

------------------------------------------------------------------------

# 2. Migration Core

## Commande

``` bash
php bin/console app:migrate-intranet-v3
```

Commandes utiles :

``` bash
# Lister les migrations disponibles
php bin/console app:migrate-intranet-v3 --list

# Simuler toute la migration
php bin/console app:migrate-intranet-v3 --dry-run -vv

# Exécuter un migrateur particulier
php bin/console app:migrate-intranet-v3 <migration> --dry-run -vv

# Réinitialiser les données cibles avant import
# Disponible uniquement en dev/test
php bin/console app:migrate-intranet-v3 --reset-db --force
```

Le `dry-run` exécute réellement les `persist()` / `flush()` dans une
transaction globale afin que les migrateurs dépendants puissent
retrouver les entités créées précédemment. La transaction est ensuite
annulée par rollback.

## Données Core migrées

La migration Core prend actuellement en charge :

### Structure

-   départements ;
-   types de diplômes ;
-   diplômes ;
-   années universitaires ;
-   structure annuelle du PN ;
-   années pédagogiques ;
-   semestres ;
-   groupes ;
-   UE ;
-   matières.

La structure pédagogique est reconstruite sous forme de **snapshots
annuels**.

`StructurePn` constitue la racine d'un snapshot pour un diplôme et une
année universitaire. Les descendants conservent leur `oldId` V3 pour
permettre la résolution des références dans le snapshot concerné.

Les structures historiques sont reconstruites à partir des données
disponibles dans V3 et des années pour lesquelles une activité est
constatée.

### Utilisateurs

-   étudiants ;
-   personnels ;
-   rattachements personnel/département ;
-   scolarités étudiantes.

### APC

-   référentiels ;
-   compétences ;
-   niveaux ;
-   apprentissages critiques ;
-   parcours ;
-   liens APC ;
-   ressources ;
-   SAE.

### Évaluations

-   évaluations ;
-   notes.

Les notes historiques sont conservées.

L'historique des modifications successives d'une note n'est pas migré.

### Rattrapages

Les demandes détaillées de rattrapage sont migrées uniquement pour
**l'année universitaire active**.

L'historique complet des demandes de rattrapage n'est pas repris.

### Emploi du temps

Les données détaillées d'EDT sont migrées uniquement pour **l'année
universitaire active**.

### Absences et justificatifs

Pour l'année active :

-   absences détaillées ;
-   justificatifs.

Pour les années historiques :

-   le détail des absences n'est pas conservé ;
-   le nombre agrégé d'absences par semestre est conservé.

### Prévisionnels

Les prévisionnels sont migrés pour **l'année active**.

### HRS

-   types de HRS ;
-   HRS de l'année active.

### Droits

Une partie des rôles V3 est convertie dans le nouveau système de
permissions par département.

Correspondances actuellement connues :

  Rôle V3            Rôle UniServices
  ------------------ -------------------------
  `ROLE_PERMANENT`   `ROLE_PERMANENT`
  `ROLE_CDD`         `ROLE_CHEF_DEPARTEMENT`
  `ROLE_DDE`         `ROLE_DIRECTEUR_ETUDES`
  `ROLE_ASS`         `ROLE_ASSISTANT`
  `ROLE_RP`          `ROLE_RESP_PARCOURS`
  `ROLE_NOTES`       `ROLE_NOTES_MANAGER`

Les rôles non reconnus ne sont **pas convertis arbitrairement**.

Rôles V3 encore rencontrés notamment :

-   `ROLE_STA`
-   `ROLE_ABS`
-   `ROLE_EDT`
-   `ROLE_PRJ`

Certains de ces droits ont vocation à être gérés par leur bundle métier,
par exemple `ROLE_STA` dans le bundle Stage.

## Anomalies V3 connues

Certaines incohérences de la base historique sont acceptées et signalées
pendant l'import, notamment :

-   environ 70 scolarités sans année universitaire V3 exploitable ;
-   environ 802 anomalies de groupes étudiants ;
-   quelques années, semestres ou UE impossibles à rattacher
    correctement ;
-   quelques anomalies sur les évaluations ou enseignements.

Ces éléments doivent être considérés comme des anomalies de la donnée
source et non nécessairement comme des erreurs du processus de
migration.

## Non repris / points particuliers

Ne sont notamment pas repris actuellement :

-   historique des modifications de notes ;
-   détail historique complet des absences ;
-   justificatifs historiques ;
-   demandes historiques de rattrapage ;
-   EDT historique détaillé ;
-   relation SAE ↔ ressources lorsque la cardinalité V3 ne correspond
    pas au nouveau modèle.

------------------------------------------------------------------------

# 3. Migration StageBundle

La migration Stage est volontairement indépendante de la migration Core.

Elle suppose que les principales données Core ont déjà été importées.

## Commande

``` bash
php bin/console app:migrate-intranet-v3-stage
```

Commandes disponibles :

``` bash
# Lister les migrations
php bin/console app:migrate-intranet-v3-stage --list

# Simulation complète
php bin/console app:migrate-intranet-v3-stage --dry-run -vv

# Sans barre de progression
php bin/console app:migrate-intranet-v3-stage --dry-run --no-progress -vv

# Une migration particulière
php bin/console app:migrate-intranet-v3-stage stage-etudiants --dry-run -vv

# Contrôler un import déjà présent
php bin/console app:migrate-intranet-v3-stage --check

# Réinitialisation avant import en dev/test
php bin/console app:migrate-intranet-v3-stage --reset --force
```

## Prérequis Core contrôlés

La commande vérifie la présence de :

-   années universitaires ;
-   semestres ;
-   étudiants ;
-   personnels.

La commande Stage ne relance pas automatiquement la migration Core.

## Ordre des migrations

L'ordre actuel est notamment :

``` text
stage-periodes
stage-contacts
stage-entreprises
stage-etudiants
```

Les dépendances sont également résolues automatiquement par le runner.

## Périodes de stage

Sont notamment migrés :

-   libellé ;
-   année universitaire ;
-   semestre programme ;
-   nombre de semaines ;
-   nombre de jours ;
-   date de début ;
-   date de fin ;
-   dates flexibles ;
-   commentaire libre ;
-   compétences visées ;
-   modalités d'évaluation entreprise ;
-   modalités d'évaluation pédagogique ;
-   modalités d'encadrement ;
-   documents à rendre ;
-   responsables de période.

Pour les responsables, le modèle UniServices distingue :

-   `responsablePrincipal` ;
-   `coResponsables`.

La relation V3 entre périodes et personnels est donc adaptée à ce
nouveau modèle.

Ne sont pas directement repris lorsqu'aucun équivalent métier pertinent
n'existe :

-   `documentName` de la période ;
-   `numeroPeriode` ;
-   `nbEcts` ;
-   `copieAssistant`.

## Entreprises et contacts

Seules les entreprises et contacts **réellement nécessaires aux stages
migrés** sont importés.

L'objectif n'est pas de reprendre l'intégralité des anciens annuaires V3
inutilisés.

Sont importés :

-   entreprises référencées par un stage étudiant ;
-   tuteurs entreprise référencés par un stage ;
-   responsables d'entreprises nécessaires aux entreprises migrées.

Les entreprises et contacts disposent d'un `oldId` permettant de
conserver le lien avec V3.

## Stages étudiants

Les `StageEtudiant` sont migrés avec notamment :

-   période de stage ;
-   étudiant ;
-   entreprise ;
-   tuteur/responsable en entreprise ;
-   tuteur universitaire / responsable de stage ;
-   service dans l'entreprise ;
-   sujet ;
-   dates du workflow ;
-   état du stage ;
-   dates de début et fin ;
-   activités ;
-   aménagement ;
-   gratification ;
-   montant et période de gratification ;
-   avantages ;
-   durée hebdomadaire ;
-   durée en jours ;
-   adresse du stage ;
-   périodes d'interruption textuelles ;
-   commentaires ;
-   données d'évaluation disponibles dans le modèle cible.

Les UUID V3 sont conservés lorsqu'ils sont exploitables. Les UUID
binaires V3 sont convertis vers le composant
`Symfony\Component\Uid\Uuid`.

### Stage sans période

V3 contient des stages dont `stage_periode_id` est nul.

Ils sont volontairement conservés :

``` text
stagePeriode = null
```

Lors des tests de migration, **30 stages** se trouvaient dans ce cas.

## États particuliers historiques

V3 utilisait certains états pour représenter en réalité un
**type/contexte de stage** :

-   `ETAT_STAGE_ERASMUS`
-   `ETAT_STAGE_ETRANGER`
-   `ETAT_STAGE_APPRENTISSAGE`

Ces informations ne sont pas perdues.

Elles sont représentées séparément via :

``` text
StageContexte
TypeStageEnum
```

avec notamment :

``` text
TYPE_STAGE_CLASSIQUE
TYPE_STAGE_ETRANGER
TYPE_STAGE_ERASMUS
TYPE_STAGE_APPRENTISSAGE
```

Lors des tests effectués :

``` text
TYPE_STAGE_APPRENTISSAGE = 2
TYPE_STAGE_ERASMUS       = 64
TYPE_STAGE_ETRANGER      = 78
```

Cela permet de ne pas mélanger l'état du workflow d'un stage avec sa
nature.

## Résultat de référence obtenu

Un dry-run de référence a produit :

``` text
Migration           Created   Updated   Skipped   Failed   Total
stage-periodes      152       0         0         0        152
stage-contacts      8103      0         0         0        8103
stage-entreprises   4057      0         0         0        4057
stage-etudiants     4203      13        0         0        4216
```

Les 13 `Updated` sur `stage-etudiants` sont à surveiller/investiguer
lors de la validation finale, notamment en contrôlant d'éventuels UUID
V3 dupliqués.

Une requête utile côté V3 :

``` sql
SELECT HEX(uuid), COUNT(*) AS c
FROM stage_etudiant
GROUP BY uuid
HAVING COUNT(*) > 1;
```

## Contrôle d'intégrité Stage

La commande :

``` bash
php bin/console app:migrate-intranet-v3-stage --check
```

compare V3 et UniServices sur plusieurs cardinalités, notamment :

-   périodes ;
-   stages étudiants ;
-   stages sans période ;
-   stages avec entreprise ;
-   stages avec tuteur entreprise ;
-   stages avec tuteur universitaire ;
-   entreprises distinctes référencées ;
-   tuteurs entreprise distincts ;
-   responsables des périodes.

Un écart fait échouer la commande afin que le contrôle puisse être
intégré à un script de déploiement ou de migration.

## Éléments Stage volontairement non migrés actuellement

Ne sont notamment pas migrés :

-   offres de stage ;
-   historique complet des avenants ;
-   fiches de suivi historiques ;
-   rapports de stage physiques ;
-   soutenances historiques ;
-   interruptions structurées lorsque seules les données textuelles
    nécessaires sont reprises ;
-   fichiers physiques associés au module Stage lorsqu'ils ne font pas
    partie du périmètre explicitement repris.

L'objectif du premier import est de disposer d'un historique cohérent
des **périodes et stages étudiants**, sans importer toutes les données
périphériques de l'ancien module.

------------------------------------------------------------------------

# 4. Migration DocumentBundle

Une migration spécifique est également disponible dans le bundle
Document.

## Commande

``` bash
php bin/console app:migrate-intranet-v3-document
```

Commandes utiles :

``` bash
# Liste
php bin/console app:migrate-intranet-v3-document --list

# Simulation
php bin/console app:migrate-intranet-v3-document --dry-run -vv

# Contrôle après import
php bin/console app:migrate-intranet-v3-document --check

# Réinitialisation en dev/test
php bin/console app:migrate-intranet-v3-document --reset --force
```

## Ordre

``` text
document-categories
documents
```

## Catégories

L'entité V3 :

``` text
TypeDocument
```

est convertie en :

``` text
DocumentCategory
```

Sont repris :

-   libellé ;
-   département ;
-   hiérarchie parent/enfant ;
-   `oldId`.

La hiérarchie est traitée en deux passes :

1.  création de toutes les catégories ;
2.  résolution des catégories parentes.

L'indicateur V3 `originaux` est actuellement signalé mais n'est pas
transposé arbitrairement s'il n'existe pas d'équivalent métier direct
dans `DocumentCategory`.

## Documents

Sont notamment repris :

-   titre/libellé ;
-   description ;
-   nom du fichier ;
-   MIME type ;
-   taille ;
-   catégorie ;
-   département dérivé de la catégorie ;
-   type de document dérivé du MIME ;
-   visibilité ;
-   `oldId`.

Conversion actuelle du destinataire V3 :

  V3       UniServices
  -------- -------------
  `ETU`    `ETUDIANT`
  `PERS`   `PERSONNEL`

Les valeurs inconnues sont signalées pendant l'import afin d'être
vérifiées.

## Fichiers physiques

Dans intranet V3, les documents étaient déposés sous :

``` text
public/upload/documents/
```

La migration actuelle importe **les métadonnées en base**, mais ne copie
pas encore les fichiers physiques.

Cette étape est volontairement séparée : le stockage cible définitif du
`DocumentBundle` doit être stabilisé avant de déplacer les fichiers.

La future étape de migration des fichiers devra au minimum :

1.  localiser le fichier V3 à partir de `document_name` ;
2.  vérifier son existence ;
3.  copier ou déplacer le fichier vers le stockage UniServices ;
4.  conserver ou recalculer les métadonnées nécessaires ;
5.  produire un rapport des fichiers V3 manquants ;
6.  permettre une vérification sans copie ;
7.  être relançable sans dupliquer les fichiers.

## Éléments V3 non transposés actuellement

Ne sont pas migrés automatiquement lorsqu'il n'existe pas encore
d'équivalent direct dans le modèle Document :

-   relations document ↔ semestres ;
-   favoris utilisateurs ;
-   notion historique `originaux` lorsqu'elle n'est pas représentée
    explicitement dans le nouveau modèle.

Ces informations sont signalées plutôt que converties vers un champ
arbitraire.

------------------------------------------------------------------------

# 5. Migrations Doctrine à appliquer

Avant de tester les migrateurs, les migrations Doctrine de la branche
doivent être appliquées :

``` bash
php bin/console doctrine:migrations:migrate
```

Elles ajoutent notamment les champs techniques nécessaires à la
migration :

-   `stage_periode.old_id` ;
-   `entreprise.old_id` ;
-   `contact.old_id` ;
-   `document.old_id` ;
-   `document_category.old_id` ;
-   les tables nécessaires à la conservation du contexte historique des
    stages.

------------------------------------------------------------------------

# 6. Procédure conseillée

## En environnement de développement

### 1. Mettre le schéma à jour

``` bash
php bin/console doctrine:migrations:migrate
```

### 2. Tester le Core

``` bash
php bin/console app:migrate-intranet-v3 --dry-run -vv
```

### 3. Importer réellement le Core

Après validation du dry-run :

``` bash
php bin/console app:migrate-intranet-v3 --reset-db --force
```

ou exécuter la procédure d'import réel retenue pour l'environnement.

### 4. Tester Stage

``` bash
php bin/console app:migrate-intranet-v3-stage --dry-run -vv
```

### 5. Importer Stage

``` bash
php bin/console app:migrate-intranet-v3-stage --reset --force
```

### 6. Vérifier Stage

``` bash
php bin/console app:migrate-intranet-v3-stage --check
```

### 7. Tester Document

``` bash
php bin/console app:migrate-intranet-v3-document --dry-run -vv
```

### 8. Importer Document

``` bash
php bin/console app:migrate-intranet-v3-document --reset --force
```

### 9. Vérifier Document

``` bash
php bin/console app:migrate-intranet-v3-document --check
```

### 10. Vérifier les fichiers physiques

Cette étape sera ajoutée lorsque la stratégie de stockage définitive du
`DocumentBundle` sera arrêtée.

------------------------------------------------------------------------

# 7. Principes de validation avant bascule

Une migration n'est pas considérée comme valide uniquement parce que la
commande se termine sans exception.

Avant une bascule réelle, vérifier au minimum :

-   `Failed = 0` pour chaque migrateur ;
-   tous les `Skipped` sont expliqués ;
-   les `Updated` inattendus sont analysés ;
-   les contrôles `--check` passent ;
-   les anomalies V3 connues sont documentées ;
-   les relations vers étudiants, personnels, départements, semestres et
    années sont cohérentes ;
-   les fichiers physiques attendus existent ;
-   quelques dossiers historiques représentatifs sont vérifiés
    manuellement dans l'interface.

Il est recommandé de conserver le rapport de migration final avec les
logs de la bascule.

------------------------------------------------------------------------

# 8. Éléments restant à finaliser

Les principaux travaux encore identifiés sont :

-   finaliser et tester la migration `DocumentBundle` sur les données V3
    réelles ;
-   définir le stockage cible des documents ;
-   implémenter la copie et le contrôle des fichiers physiques ;
-   analyser les 13 `StageEtudiant` actuellement comptabilisés comme
    `Updated` lors du dry-run de référence ;
-   décider si certaines données Stage périphériques doivent finalement
    être reprises ;
-   vérifier les rôles V3 encore non convertis et les affecter au bon
    bundle métier ;
-   effectuer un import complet sur une base UniServices vierge ;
-   conserver un rapport de contrôle final avant la bascule.

------------------------------------------------------------------------

# 9. Règle générale pour les futures migrations de bundles

Pour les prochains bundles, conserver la même organisation :

``` text
<Bundle>/
└── src/
    ├── Command/
    │   └── MigrateIntranetV3<Bundle>Command.php
    └── Migration/
        └── IntranetV3/
            ├── ...Migrator.php
            ├── <Bundle>MigrationRunner.php
            ├── <Bundle>DatabaseResetter.php
            └── <Bundle>IntegrityChecker.php
```

Chaque bundle doit :

-   déclarer explicitement ses prérequis Core ;
-   ne migrer que les données relevant de son domaine ;
-   utiliser les `oldId` lorsque la résolution de relations historiques
    le nécessite ;
-   proposer un `dry-run` ;
-   proposer un contrôle post-migration ;
-   ne pas inventer de correspondance lorsqu'un concept V3 n'existe plus
    dans le nouveau modèle ;
-   signaler clairement les données ignorées ou transformées ;
-   séparer, lorsque pertinent, la migration BDD de la migration des
    fichiers physiques.

Cette organisation permet de rejouer, tester et valider chaque domaine
indépendamment tout en conservant une migration globale maîtrisable.
