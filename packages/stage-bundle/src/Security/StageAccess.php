<?php

namespace StageBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;

/**
 * Règles d'accès aux dossiers de stage, partagées par le voter, les processeurs et l'extension de collection.
 *  - super administrateur : tout ;
 *  - responsable des stages (ROLE_STAGE_MANAGER) : tout, mais uniquement dans les départements où il a ce rôle ;
 *  - responsable (principal ou co-) de la période : lecture et écriture des dossiers de sa période ;
 *  - tuteur universitaire : lecture de ses stagiaires, saisie du suivi et de l'évaluation ;
 *  - étudiant : son propre dossier.
 */
class StageAccess
{
    public const ROLE_GESTIONNAIRE = 'ROLE_STAGE_MANAGER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly DepartmentPermissionChecker $departements,
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
        if (!$user instanceof Personnel) {
            return false;
        }
        if ($this->permissions->isSuperAdmin($user)) {
            return true;
        }
        if ($periode === null) {
            return $this->permissions->hasPermission($user, self::ROLE_GESTIONNAIRE);
        }
        $departement = $periode->getDepartement();

        return $departement !== null && $this->departements->checkPermission($user, $departement, self::ROLE_GESTIONNAIRE);
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
        return $this->peutGerer($user, $stage) || $this->estTuteur($user, $stage) || $this->estProprietaire($user, $stage);
    }

    /** Écriture complète d'un dossier (statut, tuteur, entreprise…). */
    public function peutGerer(mixed $user, StageEtudiant $stage): bool
    {
        if ($stage->getStagePeriode() === null) {
            return $this->estSuperAdmin($user);
        }
        if ($this->estGestionnaire($user, $stage->getStagePeriode())) {
            return true;
        }
        if (!$user instanceof Personnel) {
            return false;
        }
        $periode = $stage->getStagePeriode();
        if ($this->meme($periode->getResponsablePrincipal(), $user)) {
            return true;
        }
        foreach ($periode->getCoResponsables() as $co) {
            if ($this->meme($co, $user)) {
                return true;
            }
        }

        return false;
    }

    public function estTuteur(mixed $user, StageEtudiant $stage): bool
    {
        return $user instanceof Personnel && $this->meme($stage->getTuteurUniversitaire(), $user);
    }

    public function estProprietaire(mixed $user, StageEtudiant $stage): bool
    {
        return $user instanceof Etudiant && $this->meme($stage->getEtudiant(), $user);
    }

    private function meme(Etudiant|Personnel|null $a, Etudiant|Personnel $b): bool
    {
        return $a !== null && ($a === $b || ($a->getId() !== null && $a->getId() === $b->getId()));
    }
}
