# Documentation des Voters - Système de Permissions

## Vue d'ensemble

Ce document décrit le système de contrôle d'accès basé sur les Voters Symfony pour l'application uniServices.

## Voters disponibles

### 1. PostVoter (`src/Security/PostVoter.php`)

Gère les permissions pour les entités principales liées aux étudiants et à la scolarité.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_ETUDIANT` | Voir les étudiants | Personnel (tous), Étudiant (soi-même) |
| `CAN_EDIT_ETUDIANT` | Modifier un étudiant | SUPER_ADMIN, ADMIN, SCOLARITE, CHEF_DEPT, Étudiant (soi-même) |
| `CAN_DELETE_ETUDIANT` | Supprimer un étudiant | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_ETUDIANT_SCOLARITE` | Voir la scolarité d'un étudiant | Personnel avec rôles scolarité, Étudiant (sa propre scolarité) |
| `CAN_EDIT_ETUDIANT_SCOLARITE` | Modifier la scolarité | SUPER_ADMIN, ADMIN, SCOLARITE, CHEF_DEPT |
| `CAN_DELETE_ETUDIANT_SCOLARITE` | Supprimer la scolarité | SUPER_ADMIN, ADMIN, SCOLARITE |
| `CAN_VIEW_SCOL` | Voir les scolarités semestre | Personnel avec rôles scolarité, Étudiant (sa propre scolarité) |
| `CAN_EDIT_SCOL` | Modifier la scolarité semestre | SUPER_ADMIN, ADMIN, SCOLARITE, CHEF_DEPT, ASSISTANT, DIRECTEUR_ETUDES, RESP_PARCOURS |
| `CAN_DELETE_SCOL` | Supprimer la scolarité semestre | SUPER_ADMIN, ADMIN, SCOLARITE |
| `CAN_VIEW_EVAL` | Voir les évaluations | Personnel autorisé ou rôles appropriés, Étudiant (évaluations visibles) |
| `CAN_EDIT_EVAL` | Modifier une évaluation | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS, RESP_NOTES, Personnel autorisé |
| `CAN_DELETE_EVAL` | Supprimer une évaluation | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_NOTES |
| `CAN_VIEW_NOTES` | Voir les notes | Personnel autorisé, Étudiant (ses propres notes si évaluation visible) |
| `CAN_EDIT_NOTES` | Modifier les notes | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS, RESP_NOTES, Personnel autorisé |
| `CAN_DELETE_NOTES` | Supprimer les notes | SUPER_ADMIN, ADMIN, RESP_NOTES |
| `CAN_VIEW_ABSENCE` | Voir les absences | Personnel avec rôles appropriés, Étudiant (ses propres absences) |
| `CAN_EDIT_ABSENCE` | Modifier les absences | Personnel avec rôles appropriés, PERMANENT |
| `CAN_DELETE_ABSENCE` | Supprimer les absences | SUPER_ADMIN, ADMIN, SCOLARITE |
| `CAN_VIEW_JUSTIFICATIF` | Voir les justificatifs | Personnel avec rôles appropriés, Étudiant (ses propres justificatifs) |
| `CAN_EDIT_JUSTIFICATIF` | Modifier les justificatifs | Personnel avec rôles scolarité, Étudiant (soumettre) |
| `CAN_DELETE_JUSTIFICATIF` | Supprimer les justificatifs | SUPER_ADMIN, ADMIN, SCOLARITE |
| `CAN_VIEW_ANNEE_UNIV` | Voir les années universitaires | Tous |
| `CAN_EDIT_ANNEE_UNIV` | Modifier les années universitaires | SUPER_ADMIN |

---

### 2. StructureVoter (`src/Security/StructureVoter.php`)

Gère les permissions pour les entités de structure organisationnelle.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_DEPARTEMENT` | Voir les départements | Tous |
| `CAN_EDIT_DEPARTEMENT` | Modifier un département | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_DELETE_DEPARTEMENT` | Supprimer un département | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_DIPLOME` | Voir les diplômes | Tous |
| `CAN_EDIT_DIPLOME` | Modifier un diplôme | SUPER_ADMIN, ADMIN, CHEF_DEPT, Responsable/Assistant du diplôme |
| `CAN_DELETE_DIPLOME` | Supprimer un diplôme | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_SEMESTRE` | Voir les semestres | Tous |
| `CAN_EDIT_SEMESTRE` | Modifier un semestre | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS, DIRECTEUR_ETUDES |
| `CAN_DELETE_SEMESTRE` | Supprimer un semestre | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_GROUPE` | Voir les groupes | Tous |
| `CAN_EDIT_GROUPE` | Modifier un groupe | SUPER_ADMIN, ADMIN, CHEF_DEPT, SCOLARITE, ASSISTANT, DIRECTEUR_ETUDES |
| `CAN_DELETE_GROUPE` | Supprimer un groupe | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_VIEW_UE` | Voir les UE | Tous |
| `CAN_EDIT_UE` | Modifier une UE | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_UE` | Supprimer une UE | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_PN` | Voir les PN | Tous |
| `CAN_EDIT_PN` | Modifier un PN | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_DELETE_PN` | Supprimer un PN | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_ANNEE` | Voir les années | Tous |
| `CAN_EDIT_ANNEE` | Modifier une année | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES |
| `CAN_DELETE_ANNEE` | Supprimer une année | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_CALENDRIER` | Voir le calendrier | Tous |
| `CAN_EDIT_CALENDRIER` | Modifier le calendrier | SUPER_ADMIN, ADMIN, CHEF_DEPT, SCOLARITE, DIRECTEUR_ETUDES |
| `CAN_DELETE_CALENDRIER` | Supprimer le calendrier | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_TYPE_DIPLOME` | Voir les types de diplôme | Tous |
| `CAN_EDIT_TYPE_DIPLOME` | Modifier un type de diplôme | SUPER_ADMIN, ADMIN |
| `CAN_DELETE_TYPE_DIPLOME` | Supprimer un type de diplôme | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_DEPT_PERSONNEL` | Voir les liens département-personnel | Personnel |
| `CAN_EDIT_DEPT_PERSONNEL` | Modifier les liens | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_DELETE_DEPT_PERSONNEL` | Supprimer les liens | SUPER_ADMIN, ADMIN, CHEF_DEPT |

---

### 3. ScolariteVoter (`src/Security/ScolariteVoter.php`)

Gère les permissions pour les entités de scolarité (hors évaluations/notes).

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_ENSEIGNEMENT` | Voir les enseignements | Tous |
| `CAN_EDIT_ENSEIGNEMENT` | Modifier un enseignement | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS, DIRECTEUR_ETUDES |
| `CAN_DELETE_ENSEIGNEMENT` | Supprimer un enseignement | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_ENSEIGNEMENT_UE` | Voir les liaisons enseignement-UE | Tous |
| `CAN_EDIT_ENSEIGNEMENT_UE` | Modifier les liaisons | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_ENSEIGNEMENT_UE` | Supprimer les liaisons | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_BAC` | Voir les bacs | Tous |
| `CAN_EDIT_BAC` | Modifier un bac | SUPER_ADMIN, ADMIN, SCOLARITE |
| `CAN_DELETE_BAC` | Supprimer un bac | SUPER_ADMIN, ADMIN |

---

### 4. EdtVoter (`src/Security/EdtVoter.php`)

Gère les permissions pour les entités d'emploi du temps.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_EDT` | Voir l'EDT | Tous |
| `CAN_EDIT_EDT` | Modifier l'EDT | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT, ASSISTANT, Personnel (ses propres événements) |
| `CAN_DELETE_EDT` | Supprimer de l'EDT | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT |
| `CAN_VIEW_EDT_CONTRAINTES` | Voir les contraintes | Personnel |
| `CAN_EDIT_EDT_CONTRAINTES` | Modifier les contraintes | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT, DIRECTEUR_ETUDES |
| `CAN_DELETE_EDT_CONTRAINTES` | Supprimer les contraintes | SUPER_ADMIN, ADMIN, EDT |
| `CAN_VIEW_EDT_CRENEAUX` | Voir les créneaux interdits | Personnel |
| `CAN_EDIT_EDT_CRENEAUX` | Modifier les créneaux | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT |
| `CAN_DELETE_EDT_CRENEAUX` | Supprimer les créneaux | SUPER_ADMIN, ADMIN, EDT |
| `CAN_VIEW_EDT_PROGRESSION` | Voir la progression | Personnel avec rôles appropriés |
| `CAN_EDIT_EDT_PROGRESSION` | Modifier la progression | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES, PERMANENT |
| `CAN_DELETE_EDT_PROGRESSION` | Supprimer la progression | SUPER_ADMIN, ADMIN, CHEF_DEPT |

---

### 5. PrevisionnelVoter (`src/Security/PrevisionnelVoter.php`)

Gère les permissions pour les prévisionnels d'enseignement et heures complémentaires.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_PREVISIONNEL` | Voir les prévisionnels | Personnel avec rôles appropriés, PERMANENT |
| `CAN_EDIT_PREVISIONNEL` | Modifier les prévisionnels | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES, RESP_PARCOURS |
| `CAN_DELETE_PREVISIONNEL` | Supprimer les prévisionnels | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES |
| `CAN_VIEW_HRS` | Voir les heures complémentaires | Personnel avec rôles appropriés |
| `CAN_EDIT_HRS` | Modifier les heures | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES, COMPTABILITE |
| `CAN_DELETE_HRS` | Supprimer les heures | SUPER_ADMIN, ADMIN, CHEF_DEPT |

---

### 6. QuestionnaireVoter (`src/Security/QuestionnaireVoter.php`)

Gère les permissions pour les questionnaires.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_QUESTIONNAIRE` | Voir les questionnaires | Personnel avec rôles appropriés, Étudiants (invités) |
| `CAN_EDIT_QUESTIONNAIRE` | Modifier un questionnaire | SUPER_ADMIN, ADMIN, CHEF_DEPT, QUALITE, RESP_PARCOURS |
| `CAN_DELETE_QUESTIONNAIRE` | Supprimer un questionnaire | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_PUBLISH_QUESTIONNAIRE` | Publier un questionnaire | SUPER_ADMIN, ADMIN, CHEF_DEPT, QUALITE |
| `CAN_VIEW_QUESTION_SECTION` | Voir les sections | Tous |
| `CAN_EDIT_QUESTION_SECTION` | Modifier une section | SUPER_ADMIN, ADMIN, QUALITE, RESP_PARCOURS |
| `CAN_DELETE_QUESTION_SECTION` | Supprimer une section | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_VIEW_QUESTION` | Voir les questions | Tous |
| `CAN_EDIT_QUESTION` | Modifier une question | SUPER_ADMIN, ADMIN, QUALITE, RESP_PARCOURS |
| `CAN_DELETE_QUESTION` | Supprimer une question | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_VIEW_INVITATION` | Voir les invitations | Personnel avec rôles appropriés, Étudiants (leurs invitations) |
| `CAN_EDIT_INVITATION` | Modifier une invitation | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_DELETE_INVITATION` | Supprimer une invitation | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_VIEW_ANSWERS` | Voir les réponses | Personnel avec rôles appropriés |
| `CAN_SUBMIT_ANSWERS` | Soumettre des réponses | Étudiants, Personnel (invités) |

---

### 7. StageVoter (`src/Security/StageVoter.php`)

Gère les permissions pour les stages.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_STAGE_PERIODE` | Voir les périodes de stage | Tous |
| `CAN_EDIT_STAGE_PERIODE` | Modifier une période | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_STAGES, SCOLARITE |
| `CAN_DELETE_STAGE_PERIODE` | Supprimer une période | SUPER_ADMIN, ADMIN, RESP_STAGES |
| `CAN_VIEW_STAGE` | Voir les stages | Personnel avec rôles appropriés, Étudiants (leurs stages) |
| `CAN_EDIT_STAGE` | Modifier un stage | Personnel avec rôles appropriés, Étudiants (leur stage) |
| `CAN_DELETE_STAGE` | Supprimer un stage | SUPER_ADMIN, ADMIN, RESP_STAGES |
| `CAN_VALIDATE_STAGE` | Valider un stage | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_STAGES, DIRECTEUR_ETUDES |

---

### 8. ApcVoter (`src/Security/ApcVoter.php`)

Gère les permissions pour l'Approche Par Compétences (APC).

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_APC_REFERENTIEL` | Voir les référentiels | Tous |
| `CAN_EDIT_APC_REFERENTIEL` | Modifier un référentiel | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_REFERENTIEL` | Supprimer un référentiel | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_APC_COMPETENCE` | Voir les compétences | Tous |
| `CAN_EDIT_APC_COMPETENCE` | Modifier une compétence | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_COMPETENCE` | Supprimer une compétence | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_APC_NIVEAU` | Voir les niveaux | Tous |
| `CAN_EDIT_APC_NIVEAU` | Modifier un niveau | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_NIVEAU` | Supprimer un niveau | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_APC_PARCOURS` | Voir les parcours | Tous |
| `CAN_EDIT_APC_PARCOURS` | Modifier un parcours | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_PARCOURS` | Supprimer un parcours | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_APC_AC` | Voir les AC | Tous |
| `CAN_EDIT_APC_AC` | Modifier un AC | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_AC` | Supprimer un AC | SUPER_ADMIN, ADMIN |

---

### 9. PersonnelVoter (`src/Security/PersonnelVoter.php`)

Gère les permissions pour les entités Personnel.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_PERSONNEL` | Voir les personnels | Personnel |
| `CAN_EDIT_PERSONNEL` | Modifier un personnel | SUPER_ADMIN, ADMIN, CHEF_DEPT, Personnel (soi-même) |
| `CAN_DELETE_PERSONNEL` | Supprimer un personnel | SUPER_ADMIN, ADMIN |
| `CAN_ASSIGN_ROLES` | Assigner des rôles | SUPER_ADMIN, ADMIN |

---

### 10. SalleVoter (`src/Security/SalleVoter.php`)

Gère les permissions pour les salles.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_SALLE` | Voir les salles | Tous |
| `CAN_EDIT_SALLE` | Modifier une salle | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT, ASSISTANT |
| `CAN_DELETE_SALLE` | Supprimer une salle | SUPER_ADMIN, ADMIN |

---

## Utilisation dans les entités

### Exemple avec API Platform

```php
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new Post(securityPostDenormalize: "is_granted('CAN_EDIT_EXEMPLE', object)"),
        new Patch(securityPostDenormalize: "is_granted('CAN_EDIT_EXEMPLE', object)"),
        new Delete(security: "is_granted('CAN_DELETE_EXEMPLE', object)"),
    ]
)]
```

### Exemple dans un contrôleur

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('CAN_EDIT_EVAL', subject: 'evaluation')]
public function editEvaluation(ScolEvaluation $evaluation): Response
{
    // ...
}
```

### Exemple dans un service

```php
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

public function __construct(
    private AuthorizationCheckerInterface $authChecker
) {}

public function someMethod(ScolEvaluation $evaluation): void
{
    if ($this->authChecker->isGranted('CAN_EDIT_EVAL', $evaluation)) {
        // L'utilisateur peut modifier
    }
}
```

---

## Hiérarchie des rôles

| Rôle | Description |
|------|-------------|
| `ROLE_SUPER_ADMIN` | Accès complet à tout |
| `ROLE_ADMIN` | Administration générale |
| `ROLE_CHEF_DEPT` | Chef de département |
| `ROLE_DIRECTEUR_ETUDES` | Directeur des études |
| `ROLE_RESP_PARCOURS` | Responsable de parcours |
| `ROLE_RESP_NOTES` | Responsable des notes |
| `ROLE_RESP_STAGES` | Responsable des stages |
| `ROLE_SCOLARITE` | Service scolarité |
| `ROLE_ASSISTANT` | Assistant(e) |
| `ROLE_EDT` | Gestionnaire EDT |
| `ROLE_QUALITE` | Responsable qualité |
| `ROLE_COMPTABILITE` | Service comptabilité |
| `ROLE_PERMANENT` | Personnel permanent |
| `ROLE_ETUDIANT` | Étudiant |

---

## Couverture des entités

### Entités couvertes par les Voters

| Dossier | Entité | Voter |
|---------|--------|-------|
| `Apc/` | ApcApprentissageCritique | ApcVoter |
| `Apc/` | ApcCompetence | ApcVoter |
| `Apc/` | ApcNiveau | ApcVoter |
| `Apc/` | ApcParcours | ApcVoter |
| `Apc/` | ApcReferentiel | ApcVoter |
| `Edt/` | EdtContraintesSemestre | EdtVoter |
| `Edt/` | EdtCreneauxInterditsSemaine | EdtVoter |
| `Edt/` | EdtEvent | EdtVoter |
| `Edt/` | EdtProgression | EdtVoter |
| `Etudiant/` | EtudiantAbsence | PostVoter |
| `Etudiant/` | EtudiantAbsenceJustificatif | PostVoter |
| `Etudiant/` | EtudiantNote | PostVoter |
| `Etudiant/` | EtudiantScolarite | PostVoter |
| `Etudiant/` | EtudiantScolariteSemestre | PostVoter |
| `Personnel/` | PersonnelEnseignantHrs | PrevisionnelVoter |
| `Personnel/` | PersonnelEnseignantTypeHrs | PrevisionnelVoter |
| `Previsionnel/` | Previsionnel | PrevisionnelVoter |
| `Questionnaires/` | Questionnaire | QuestionnaireVoter |
| `Questionnaires/` | QuestionnaireAnswer | QuestionnaireVoter |
| `Questionnaires/` | QuestionnaireInvitation | QuestionnaireVoter |
| `Questionnaires/` | QuestionnaireQuestion | QuestionnaireVoter |
| `Questionnaires/` | QuestionnaireSection | QuestionnaireVoter |
| `Questionnaires/` | QuestionnaireSectionInstance | QuestionnaireVoter |
| `Scolarite/` | ScolBac | ScolariteVoter |
| `Scolarite/` | ScolEnseignement | ScolariteVoter |
| `Scolarite/` | ScolEnseignementUe | ScolariteVoter |
| `Scolarite/` | ScolEvaluation | PostVoter |
| `Stages/` | StagePeriode | StageVoter |
| `Structure/` | StructureAnnee | StructureVoter |
| `Structure/` | StructureAnneeUniversitaire | PostVoter |
| `Structure/` | StructureCalendrier | StructureVoter |
| `Structure/` | StructureDepartement | StructureVoter |
| `Structure/` | StructureDepartementPersonnel | StructureVoter |
| `Structure/` | StructureDiplome | StructureVoter |
| `Structure/` | StructureGroupe | StructureVoter |
| `Structure/` | StructurePn | StructureVoter |
| `Structure/` | StructureSemestre | StructureVoter |
| `Structure/` | StructureTypeDiplome | StructureVoter |
| `Structure/` | StructureUe | StructureVoter |
| `Users/` | Etudiant | PostVoter |
| `Users/` | Personnel | PersonnelVoter |
| `-` | Salle | SalleVoter |

---

### 2. StructureVoter (`src/Security/StructureVoter.php`)

Gère les permissions pour les entités de structure organisationnelle.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_DEPARTEMENT` | Voir les départements | Tous |
| `CAN_EDIT_DEPARTEMENT` | Modifier un département | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_DELETE_DEPARTEMENT` | Supprimer un département | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_DIPLOME` | Voir les diplômes | Tous |
| `CAN_EDIT_DIPLOME` | Modifier un diplôme | SUPER_ADMIN, ADMIN, CHEF_DEPT, Responsable/Assistant du diplôme |
| `CAN_DELETE_DIPLOME` | Supprimer un diplôme | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_SEMESTRE` | Voir les semestres | Tous |
| `CAN_EDIT_SEMESTRE` | Modifier un semestre | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS, DIRECTEUR_ETUDES |
| `CAN_DELETE_SEMESTRE` | Supprimer un semestre | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_GROUPE` | Voir les groupes | Tous |
| `CAN_EDIT_GROUPE` | Modifier un groupe | SUPER_ADMIN, ADMIN, CHEF_DEPT, SCOLARITE, ASSISTANT, DIRECTEUR_ETUDES |
| `CAN_DELETE_GROUPE` | Supprimer un groupe | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_VIEW_UE` | Voir les UE | Tous |
| `CAN_EDIT_UE` | Modifier une UE | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_UE` | Supprimer une UE | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_PN` | Voir les PN | Tous |
| `CAN_EDIT_PN` | Modifier un PN | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_DELETE_PN` | Supprimer un PN | SUPER_ADMIN, ADMIN |

---

### 3. EdtVoter (`src/Security/EdtVoter.php`)

Gère les permissions pour les entités d'emploi du temps.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_EDT` | Voir l'EDT | Tous |
| `CAN_EDIT_EDT` | Modifier l'EDT | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT, ASSISTANT, Personnel (ses propres événements) |
| `CAN_DELETE_EDT` | Supprimer de l'EDT | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT |
| `CAN_VIEW_EDT_CONTRAINTES` | Voir les contraintes | Personnel |
| `CAN_EDIT_EDT_CONTRAINTES` | Modifier les contraintes | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT, DIRECTEUR_ETUDES |
| `CAN_DELETE_EDT_CONTRAINTES` | Supprimer les contraintes | SUPER_ADMIN, ADMIN, EDT |
| `CAN_VIEW_EDT_CRENEAUX` | Voir les créneaux interdits | Personnel |
| `CAN_EDIT_EDT_CRENEAUX` | Modifier les créneaux | SUPER_ADMIN, ADMIN, CHEF_DEPT, EDT |
| `CAN_DELETE_EDT_CRENEAUX` | Supprimer les créneaux | SUPER_ADMIN, ADMIN, EDT |
| `CAN_VIEW_EDT_PROGRESSION` | Voir la progression | Personnel avec rôles appropriés |
| `CAN_EDIT_EDT_PROGRESSION` | Modifier la progression | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES, PERMANENT |
| `CAN_DELETE_EDT_PROGRESSION` | Supprimer la progression | SUPER_ADMIN, ADMIN, CHEF_DEPT |

---

### 4. PrevisionnelVoter (`src/Security/PrevisionnelVoter.php`)

Gère les permissions pour les prévisionnels d'enseignement.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_PREVISIONNEL` | Voir les prévisionnels | Personnel avec rôles appropriés, PERMANENT |
| `CAN_EDIT_PREVISIONNEL` | Modifier les prévisionnels | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES, RESP_PARCOURS |
| `CAN_DELETE_PREVISIONNEL` | Supprimer les prévisionnels | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES |
| `CAN_VIEW_HRS` | Voir les heures complémentaires | Personnel avec rôles appropriés |
| `CAN_EDIT_HRS` | Modifier les heures | SUPER_ADMIN, ADMIN, CHEF_DEPT, DIRECTEUR_ETUDES, COMPTABILITE |
| `CAN_DELETE_HRS` | Supprimer les heures | SUPER_ADMIN, ADMIN, CHEF_DEPT |

---

### 5. QuestionnaireVoter (`src/Security/QuestionnaireVoter.php`)

Gère les permissions pour les questionnaires.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_QUESTIONNAIRE` | Voir les questionnaires | Personnel avec rôles appropriés, Étudiants (invités) |
| `CAN_EDIT_QUESTIONNAIRE` | Modifier un questionnaire | SUPER_ADMIN, ADMIN, CHEF_DEPT, QUALITE, RESP_PARCOURS |
| `CAN_DELETE_QUESTIONNAIRE` | Supprimer un questionnaire | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_PUBLISH_QUESTIONNAIRE` | Publier un questionnaire | SUPER_ADMIN, ADMIN, CHEF_DEPT, QUALITE |
| `CAN_VIEW_QUESTION_SECTION` | Voir les sections | Tous |
| `CAN_EDIT_QUESTION_SECTION` | Modifier une section | SUPER_ADMIN, ADMIN, QUALITE, RESP_PARCOURS |
| `CAN_DELETE_QUESTION_SECTION` | Supprimer une section | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_VIEW_QUESTION` | Voir les questions | Tous |
| `CAN_EDIT_QUESTION` | Modifier une question | SUPER_ADMIN, ADMIN, QUALITE, RESP_PARCOURS |
| `CAN_DELETE_QUESTION` | Supprimer une question | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_VIEW_INVITATION` | Voir les invitations | Personnel avec rôles appropriés, Étudiants (leurs invitations) |
| `CAN_EDIT_INVITATION` | Modifier une invitation | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_DELETE_INVITATION` | Supprimer une invitation | SUPER_ADMIN, ADMIN, QUALITE |
| `CAN_VIEW_ANSWERS` | Voir les réponses | Personnel avec rôles appropriés |
| `CAN_SUBMIT_ANSWERS` | Soumettre des réponses | Étudiants, Personnel (invités) |

---

### 6. StageVoter (`src/Security/StageVoter.php`)

Gère les permissions pour les stages.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_STAGE_PERIODE` | Voir les périodes de stage | Tous |
| `CAN_EDIT_STAGE_PERIODE` | Modifier une période | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_STAGES, SCOLARITE |
| `CAN_DELETE_STAGE_PERIODE` | Supprimer une période | SUPER_ADMIN, ADMIN, RESP_STAGES |
| `CAN_VIEW_STAGE` | Voir les stages | Personnel avec rôles appropriés, Étudiants (leurs stages) |
| `CAN_EDIT_STAGE` | Modifier un stage | Personnel avec rôles appropriés, Étudiants (leur stage) |
| `CAN_DELETE_STAGE` | Supprimer un stage | SUPER_ADMIN, ADMIN, RESP_STAGES |
| `CAN_VALIDATE_STAGE` | Valider un stage | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_STAGES, DIRECTEUR_ETUDES |

---

### 7. ApcVoter (`src/Security/ApcVoter.php`)

Gère les permissions pour l'Approche Par Compétences (APC).

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_APC_REFERENTIEL` | Voir les référentiels | Tous |
| `CAN_EDIT_APC_REFERENTIEL` | Modifier un référentiel | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_REFERENTIEL` | Supprimer un référentiel | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_APC_COMPETENCE` | Voir les compétences | Tous |
| `CAN_EDIT_APC_COMPETENCE` | Modifier une compétence | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_COMPETENCE` | Supprimer une compétence | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_APC_NIVEAU` | Voir les niveaux | Tous |
| `CAN_EDIT_APC_NIVEAU` | Modifier un niveau | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_NIVEAU` | Supprimer un niveau | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_APC_PARCOURS` | Voir les parcours | Tous |
| `CAN_EDIT_APC_PARCOURS` | Modifier un parcours | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_PARCOURS` | Supprimer un parcours | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_APC_AC` | Voir les AC | Tous |
| `CAN_EDIT_APC_AC` | Modifier un AC | SUPER_ADMIN, ADMIN, CHEF_DEPT, RESP_PARCOURS |
| `CAN_DELETE_APC_AC` | Supprimer un AC | SUPER_ADMIN, ADMIN |

---

### 8. PersonnelVoter (`src/Security/PersonnelVoter.php`)

Gère les permissions pour les entités Personnel.

| Permission | Description | Rôles autorisés |
|------------|-------------|-----------------|
| `CAN_VIEW_PERSONNEL` | Voir les personnels | Personnel |
| `CAN_EDIT_PERSONNEL` | Modifier un personnel | SUPER_ADMIN, ADMIN, CHEF_DEPT, Personnel (soi-même) |
| `CAN_DELETE_PERSONNEL` | Supprimer un personnel | SUPER_ADMIN, ADMIN |
| `CAN_VIEW_DEPT_PERSONNEL` | Voir les liens département-personnel | Personnel |
| `CAN_EDIT_DEPT_PERSONNEL` | Modifier les liens | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_DELETE_DEPT_PERSONNEL` | Supprimer les liens | SUPER_ADMIN, ADMIN, CHEF_DEPT |
| `CAN_ASSIGN_ROLES` | Assigner des rôles | SUPER_ADMIN, ADMIN |

---

## Utilisation dans les entités

### Exemple avec API Platform

```php
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new Post(securityPostDenormalize: "is_granted('CAN_EDIT_EXEMPLE', object)"),
        new Patch(securityPostDenormalize: "is_granted('CAN_EDIT_EXEMPLE', object)"),
        new Delete(security: "is_granted('CAN_DELETE_EXEMPLE', object)"),
    ]
)]
```

### Exemple dans un contrôleur

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('CAN_EDIT_EVAL', subject: 'evaluation')]
public function editEvaluation(ScolEvaluation $evaluation): Response
{
    // ...
}
```

### Exemple dans un service

```php
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

public function __construct(
    private AuthorizationCheckerInterface $authChecker
) {}

public function someMethod(ScolEvaluation $evaluation): void
{
    if ($this->authChecker->isGranted('CAN_EDIT_EVAL', $evaluation)) {
        // L'utilisateur peut modifier
    }
}
```

---

## Hiérarchie des rôles

| Rôle | Description |
|------|-------------|
| `ROLE_SUPER_ADMIN` | Accès complet à tout |
| `ROLE_ADMIN` | Administration générale |
| `ROLE_CHEF_DEPT` | Chef de département |
| `ROLE_DIRECTEUR_ETUDES` | Directeur des études |
| `ROLE_RESP_PARCOURS` | Responsable de parcours |
| `ROLE_RESP_NOTES` | Responsable des notes |
| `ROLE_RESP_STAGES` | Responsable des stages |
| `ROLE_SCOLARITE` | Service scolarité |
| `ROLE_ASSISTANT` | Assistant(e) |
| `ROLE_EDT` | Gestionnaire EDT |
| `ROLE_QUALITE` | Responsable qualité |
| `ROLE_COMPTABILITE` | Service comptabilité |
| `ROLE_PERMANENT` | Personnel permanent |
| `ROLE_ETUDIANT` | Étudiant |

