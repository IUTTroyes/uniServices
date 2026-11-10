<?php

namespace UnifolioBundle\Service\Campagne;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Security\UnifolioRoles;

/** Qui peut voir et évaluer le travail d'un étudiant : un enseignant affecté à cet étudiant. */
class EvaluateurAcces
{
    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly CampagneAffectationRepository $affectations,
    ) {
    }

    public function aLeRoleEvaluateur(Personnel $personnel): bool
    {
        return $this->permissions->isSuperAdmin($personnel)
            || $this->permissions->hasAnyPermission($personnel, [UnifolioRoles::ENSEIGNANT, UnifolioRoles::CORRECTEUR, UnifolioRoles::REFERENT]);
    }

    public function estAffecte(Personnel $personnel, Etudiant $etudiant): bool
    {
        return $this->aLeRoleEvaluateur($personnel) && $this->affectations->estAffecte($personnel, $etudiant);
    }

    /** Un évaluateur affecté voit les traces déposées de ses étudiants (jamais les brouillons). */
    public function peutVoirTrace(Personnel $personnel, Trace $trace): bool
    {
        return $trace->getStatut() !== StatutTraceEnum::BROUILLON && $this->estAffecte($personnel, $trace->getEtudiant());
    }
}
