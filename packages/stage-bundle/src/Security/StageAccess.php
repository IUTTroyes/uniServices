<?php

namespace StageBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use StageBundle\Entity\Stages\StageEtudiant;

/**
 * Règles d'accès aux dossiers de stage, partagées par le voter, les processeurs et l'extension de collection.
 *  - responsable des stages (ROLE_STAGE_MANAGER) / super administrateur : tout ;
 *  - responsable (principal ou co-) de la période : lecture et écriture des dossiers de sa période ;
 *  - tuteur universitaire : lecture de ses stagiaires, saisie du suivi et de l'évaluation ;
 *  - étudiant : son propre dossier.
 */
class StageAccess
{
    public function __construct(private readonly UserEffectivePermissionService $permissions)
    {
    }

    public function estGestionnaire(mixed $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        return $this->permissions->isSuperAdmin($user) || $this->permissions->hasPermission($user, 'ROLE_STAGE_MANAGER');
    }

    public function peutVoir(mixed $user, StageEtudiant $stage): bool
    {
        return $this->peutGerer($user, $stage) || $this->estTuteur($user, $stage) || $this->estProprietaire($user, $stage);
    }

    /** Écriture complète d'un dossier (statut, tuteur, entreprise…). */
    public function peutGerer(mixed $user, StageEtudiant $stage): bool
    {
        if ($this->estGestionnaire($user)) {
            return true;
        }
        if (!$user instanceof Personnel || $stage->getStagePeriode() === null) {
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
