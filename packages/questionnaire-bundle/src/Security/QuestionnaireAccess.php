<?php

namespace QuestionnaireBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Services\QuestionnaireReglages;

/**
 * Adaptateur des fonctionnalités du package vers les règles locales de QuestionnaireVoter.
 * Une relation métier (auteur, demandeur, tuteur ou responsable) ne remplace jamais les habilitations du voter.
 * Les contraintes de statut et de contenu restent appliquées par les processeurs et workflows.
 */
class QuestionnaireAccess
{
    public const ROLES_GESTIONNAIRE = ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_QUALITE'];

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly DepartmentPermissionChecker $departements,
        private readonly QuestionnaireVoter $voter,
        private readonly QuestionnaireReglages $reglages,
    ) {
    }

    public function estSuperAdmin(mixed $user): bool
    {
        return $user instanceof Personnel && $this->permissions->isSuperAdmin($user);
    }

    /**
     * Gestionnaire des questionnaires du département. Sans département (questionnaire d'établissement) : super
     * administrateur uniquement.
     */
    public function estGestionnaire(mixed $user, ?StructureDepartement $departement): bool
    {
        $questionnaire = (new Questionnaire())->setDepartement($departement);
        return $this->voter->allows(QuestionnaireVoter::CAN_EDIT_QUESTIONNAIRE, $questionnaire, $user);
    }

    /** Gestionnaire dans au moins un département (menus, création). */
    public function estGestionnaireQuelquePart(mixed $user): bool
    {
        return $this->estSuperAdmin($user) || ($user instanceof Personnel && $this->departementsGeres($user) !== []);
    }

    /**
     * Départements dont la personne gère les questionnaires (pour filtrer les listes).
     *
     * @return list<int>
     */
    public function departementsGeres(Personnel $user): array
    {
        $ids = [];
        foreach ($user->getDepartementsPersonnel() as $affectation) {
            $departement = $affectation->getDepartement();
            if ($departement?->getId() !== null && $this->estGestionnaire($user, $departement)) {
                $ids[] = $departement->getId();
            }
        }
        return array_values(array_unique($ids));
    }

    public function estAuteur(mixed $user, Questionnaire $questionnaire): bool
    {
        return $user instanceof Personnel && self::memePersonne($questionnaire->getAuthor(), $user);
    }

    /** Consultation du questionnaire, de sa structure, de ses invitations et de ses résultats. */
    public function peutVoir(mixed $user, Questionnaire $questionnaire): bool
    {
        return $this->voter->allows(QuestionnaireVoter::CAN_VIEW_QUESTIONNAIRE, $questionnaire, $user);
    }

    /** Résultats agrégés (statistiques, exports) : qui peut voir le questionnaire, ou destinataire de résultats publiés. */
    public function peutVoirSynthese(mixed $user, Questionnaire $questionnaire): bool
    {
        return $this->voter->allows(QuestionnaireVoter::CAN_EDIT_QUESTIONNAIRE, $questionnaire, $user);
    }

    /**
     * Sections dont les résultats sont visibles : null = toutes (équipe), sinon celles dont la personne est
     * destinataire une fois les résultats publiés.
     *
     * @return list<int>|null
     */
    public function sectionsVisibles(mixed $user, Questionnaire $questionnaire): ?array
    {
        return $this->peutVoir($user, $questionnaire) ? null : $this->sectionsDestinataire($user, $questionnaire);
    }

    /** @return list<int> */
    public function sectionsDestinataire(mixed $user, Questionnaire $questionnaire): array
    {
        if (!$user instanceof Personnel || !$this->peutVoirSynthese($user, $questionnaire) || !$questionnaire->resultatsPublies()) {
            return [];
        }
        $ids = [];
        foreach ($questionnaire->getSectionInstances() as $section) {
            if ($section->estDestinataire($user)) {
                $ids[] = (int) $section->getId();
            }
        }

        return $ids;
    }

    /** Paramètres, structure (tant que le questionnaire est un brouillon), publication, invitations, relances. */
    public function peutGerer(mixed $user, Questionnaire $questionnaire): bool
    {
        return $this->voter->allows(QuestionnaireVoter::CAN_EDIT_QUESTIONNAIRE, $questionnaire, $user);
    }

    /**
     * Départements administrables selon les habilitations locales (pour les tableaux de pilotage).
     *
     * @return list<int>
     */
    public function departementsVisibles(Personnel $user): array
    {
        return $this->departementsGeres($user);
    }

    /** Suppression selon les rôles locaux ; être auteur ne suffit pas. */
    public function peutSupprimer(mixed $user, Questionnaire $questionnaire): bool
    {
        return $this->voter->allows(QuestionnaireVoter::CAN_DELETE_QUESTIONNAIRE, $questionnaire, $user);
    }

    /** Créer un questionnaire dans ce département (ou l'y déplacer) : gestionnaire du département. */
    public function peutCreer(mixed $user, ?StructureDepartement $departement): bool
    {
        return $this->estGestionnaire($user, $departement);
    }

    /**
     * Même personne ? (même type de compte et même identifiant ; instanceof plutôt que ::class, une entité chargée
     * paresseusement étant un proxy Doctrine).
     */
    public static function memePersonne(Etudiant|Personnel|null $a, Etudiant|Personnel|null $b): bool
    {
        if ($a === null || $b === null || ($a instanceof Etudiant) !== ($b instanceof Etudiant)) {
            return false;
        }

        return $a === $b || ($a->getId() !== null && $a->getId() === $b->getId());
    }
}
