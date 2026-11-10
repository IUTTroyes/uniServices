<?php

namespace StageBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;

/**
 * Adaptateur des fonctionnalités du package vers les règles locales de StageVoter.
 * Une relation métier (auteur, demandeur, tuteur ou responsable) ne remplace jamais les habilitations du voter.
 * Les contraintes de statut et de contenu restent appliquées par les processeurs et workflows.
 */
class StageAccess
{
    public const ROLE_GESTIONNAIRE = 'ROLE_STAGE_MANAGER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly DepartmentPermissionChecker $departements,
        private readonly StageVoter $voter,
    ) {
    }

    public function estSuperAdmin(mixed $user): bool
    {
        return $user instanceof Personnel && $this->permissions->isSuperAdmin($user);
    }

    /**
     * Gestionnaire des stages de la période : ROLE_STAGE_MANAGER dans le département de la période.
     * Sans période, la question porte sur le département actif de la personne (menus, écrans).
     * Une période sans département n'est gérable que par le super administrateur.
     */
    public function estGestionnaire(mixed $user, ?StagePeriode $periode = null): bool
    {
        if ($periode === null) {
            return $this->estSuperAdmin($user) || ($user instanceof Personnel && $this->permissions->hasPermission($user, self::ROLE_GESTIONNAIRE));
        }
        return $this->voter->allows(StageVoter::CAN_MANAGE_STAGE_PERIODE, $periode, $user);
    }

    /**
     * Départements dont la personne gère les stages (pour filtrer les listes).
     *
     * @return list<int>
     */
    public function departementsGeres(Personnel $user): array
    {
        $ids = [];
        foreach ($user->getDepartementsPersonnel() as $affectation) {
            $departement = $affectation->getDepartement();
            if ($departement?->getId() !== null && $this->departements->checkPermission($user, $departement, self::ROLE_GESTIONNAIRE)) {
                $ids[] = $departement->getId();
            }
        }

        return array_values(array_unique($ids));
    }

    public function peutVoir(mixed $user, StageEtudiant $stage): bool
    {
        return $this->voter->allows(StageVoter::CAN_VIEW_STAGE, $stage, $user);
    }

    /** Écriture complète d'un dossier (statut, tuteur, entreprise…). */
    public function peutGerer(mixed $user, StageEtudiant $stage): bool
    {
        return $this->voter->allows(StageVoter::CAN_VALIDATE_STAGE, $stage, $user);
    }

    public function estTuteur(mixed $user, StageEtudiant $stage): bool
    {
        return $user instanceof Personnel && self::memePersonne($stage->getTuteurUniversitaire(), $user)
            && $this->voter->allows(StageVoter::CAN_EDIT_STAGE, $stage, $user);
    }

    public function estProprietaire(mixed $user, StageEtudiant $stage): bool
    {
        return $user instanceof Etudiant && self::memePersonne($stage->getEtudiant(), $user);
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
