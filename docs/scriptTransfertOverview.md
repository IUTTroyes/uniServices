# Suivi du script de transfert V3 vers V4

Pour chaque migrateur, noter le constat, la correction attendue et, si possible, un exemple ou un contrôle à effectuer.

## Utilisateurs

### EtudiantMigrator

[Code source](../back/src/Migration/IntranetV3/Users/EtudiantMigrator.php)

- **Constat**
    - Pas d'accès aux applis
- **Correction attendue**
    - Ajouter les droits d'accès à l'intranet
- **Exemple ou contrôle**
    - etudiant.applications = ['intranet']

### PersonnelDepartementMigrator

[Code source](../back/src/Migration/IntranetV3/Users/PersonnelDepartementMigrator.php)

- **Constat**
    - Les différentes permissions ne sont pas toujours correctement atribuées (peut-être un problème d'affectation dans la V3)
- **Correction attendue**
    - Révision des permissions et de l'affectation des personnels aux départements après transfert (BIATSS & ENSEIGNANTS).
- **Exemple ou contrôle**
    - Demande aux départements d'effectuer un contrôle de leurs personnels et de leurs permissions au lancement de l'application.

### PersonnelMigrator

[Code source](../back/src/Migration/IntranetV3/Users/PersonnelMigrator.php)

- **Constat**
    - ~~Liaison à une année universitaire = osbsolète~~
- **Correction attendue**
    - ~~Supprimer la liaison à l'année universitaire~~
- **Exemple ou contrôle**

## Structure

### AnneeMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/AnneeMigrator.php)

- **Constat**
    - On a un PN créé pour chaque Diplome pour l'année universitaire 26/27 sans distinction si il existe encore ou pas.
- **Correction attendue**
    - Utiliser les proriétés actif et anneeUniversitaire pour ne créer que les PN pertinents. Et peut-être reconstruire l'historique, au moins 25/26 ?
- **Exemple ou contrôle**

### AnneeUniversitaireMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/AnneeUniversitaireMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### CalendrierMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/CalendrierMigrator.php)

- **Constat** : le calendrier est calculé depuis une date de référence annuelle.
- **Correction attendue** : reprendre uniquement `dateDebutSemaineFormation` sur l’année, sans recréer de lignes hebdomadaires.
- **Exemple ou contrôle** : vérifier la continuité, les lundis et la borne du 31 août N+1. Voir [le calendrier calculé](CALENDRIER_UNIVERSITAIRE.md).

### DepartementMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/DepartementMigrator.php)

- **Constat**
    - ~~On récupère "resp_ri" dans la propriété opt mais on devrait le gérer dans la propriété permissions de département_personnel.~~
- **Correction attendue**
    - ~~Retrait de cette information de la propriété opt.~~
- **Exemple ou contrôle**

### DiplomeMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/DiplomeMigrator.php)

- **Constat**
    - On récupère "resp_qualite" dans la propriété opt
- **Correction attendue**
    - Gérer dans la table departement_personnel_parcours_diplome **implique d'ajouter des propriétés à la table relationnelle departement_personnel_parcours_diplome pour gérer les permissions.**
- **Exemple ou contrôle**

### GroupeMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/GroupeMigrator.php)

- **Constat**
    - ~~idEduSign pas repris~~
    - Les groupes ne sont pas répartis correctement dans les semestres.
- **Correction attendue**
    - ~~Reprendre idEduSign~~
    - Revoir la répartition des groupes dans les semestres (peut-être via groupe.apc_parcours de la v3 ??).
- **Exemple ou contrôle**
    - Exemple en GEA le semestre S4 GEMA FI contient les groupes de TD :
        - BUT2 GCFF S4
        - BUT2 GEMA S4 A
        - BUT2 GPRH S4
        - BUT2 GEMA ALT S4
        - BUT2 GEMA S4 B

### PnMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/PnMigrator.php)

- cf. AnneeMigrator
- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### SalleMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/SalleMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### SemestreMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/SemestreMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### TypeDiplomeMigrator

[Code source](../back/src/Migration/IntranetV3/Structure/TypeDiplomeMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

## Maquette

### MatiereMigrator

[Code source](../back/src/Migration/IntranetV3/Maquette/MatiereMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### UeMigrator

[Code source](../back/src/Migration/IntranetV3/Maquette/UeMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

## APC

### ApcApprentissageCritiqueMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcApprentissageCritiqueMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcCompetenceMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcCompetenceMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcDiplomeLinkMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcDiplomeLinkMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcNiveauMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcNiveauMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcParcoursMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcParcoursMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcReferentielMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcReferentielMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcRelationMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcRelationMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcRessourceMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcRessourceMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcSaeMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcSaeMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ApcUeLinkMigrator

[Code source](../back/src/Migration/IntranetV3/Apc/ApcUeLinkMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

## Scolarité

### AbsenceActiveMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/AbsenceActiveMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### BacMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/BacMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### EtudiantGroupeMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/EtudiantGroupeMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### EvaluationMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/EvaluationMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### HrsMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/HrsMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### JustificatifActiveMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/JustificatifActiveMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### NoteMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/NoteMigrator.php)

- **Constat**
    - La liaison entre Note et EtudiantScolariteSemestre est facultative
- **Correction attendue**
    - Rendre la relation obligatoire, ou bien gérer le cas où elle est absente
- **Exemple ou contrôle**
    - 

### PrevisionnelMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/PrevisionnelMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### RattrapageMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/RattrapageMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ScolariteDetailsMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/ScolariteDetailsMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### ScolariteMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/ScolariteMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### TypeHrsMigrator

[Code source](../back/src/Migration/IntranetV3/Scolarite/TypeHrsMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

## Emploi du temps

### EdtEventMigrator

[Code source](../back/src/Migration/IntranetV3/Edt/EdtEventMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

## Documents

### DocumentCategoryMigrator

[Code source](../packages/document-bundle/src/Migration/IntranetV3/DocumentCategoryMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### DocumentFileMigrator

[Code source](../packages/document-bundle/src/Migration/IntranetV3/DocumentFileMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### DocumentMigrator

[Code source](../packages/document-bundle/src/Migration/IntranetV3/DocumentMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

## Stages

### ContactMigrator

[Code source](../packages/stage-bundle/src/Migration/IntranetV3/ContactMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### EntrepriseMigrator

[Code source](../packages/stage-bundle/src/Migration/IntranetV3/EntrepriseMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### StageEtudiantMigrator

[Code source](../packages/stage-bundle/src/Migration/IntranetV3/StageEtudiantMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**

### StagePeriodeMigrator

[Code source](../packages/stage-bundle/src/Migration/IntranetV3/StagePeriodeMigrator.php)

- **Constat**
- **Correction attendue**
- **Exemple ou contrôle**
