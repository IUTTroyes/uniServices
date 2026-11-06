<?php

namespace UnifolioBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;

/** Périmètre d'administration d'un personnel : son département actif, ou tout s'il est super administrateur. */
class ReferentContexte
{
    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
    ) {
    }

    public function isSuperAdmin(Personnel $personnel): bool
    {
        return $this->permissions->isSuperAdmin($personnel);
    }

    /** Département administré ; null pour un super administrateur (= tous) ou sans affectation. */
    public function departement(Personnel $personnel): ?StructureDepartement
    {
        if ($this->isSuperAdmin($personnel)) {
            return null;
        }

        return $this->permissions->getActiveDepartementPersonnel($personnel)?->getDepartement();
    }

    /** Département actif du personnel (y compris pour un super administrateur) : portée par défaut des créations. */
    public function departementActif(Personnel $personnel): ?StructureDepartement
    {
        return $this->permissions->getActiveDepartementPersonnel($personnel)?->getDepartement();
    }

    public function administre(Personnel $personnel, ?StructureDepartement $departement): bool
    {
        if ($this->isSuperAdmin($personnel)) {
            return true;
        }
        $sien = $this->departement($personnel);

        return $sien !== null && $departement !== null && $sien->getId() === $departement->getId();
    }
}
