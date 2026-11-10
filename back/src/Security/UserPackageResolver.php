<?php

namespace App\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Repository\Structure\StructureDepartementPersonnelRepository;

/**
 * Packages (applications) dont dispose une personne : source unique, utilisée par le contexte de sécurité du front
 * et par les notifications.
 *  - personnel : packages de son affectation départementale par défaut (à défaut, la première) ;
 *  - étudiant  : ses « applications » (Intranet par défaut) ;
 *  - super administrateur : tous les packages.
 */
class UserPackageResolver
{
    /** Packages toujours disponibles, quel que soit le profil. */
    private const TOUJOURS = ['auth', 'core'];
    /** Anciens noms → nom canonique (même table que le front : securityStore.hasPackage). */
    private const ALIAS = ['stages' => 'stage', 'document' => 'documents'];

    public function __construct(
        private readonly StructureDepartementPersonnelRepository $affectations,
        private readonly UserEffectivePermissionService $permissions,
    ) {
    }

    /** @return list<string> noms normalisés en minuscules */
    public function packages(Personnel|Etudiant $user): array
    {
        if ($user instanceof Etudiant) {
            $brutes = array_map(static fn ($a) => (string) $a, $user->getApplications() ?? ['Intranet']);

            return $this->normaliser($brutes === [] ? ['intranet'] : $brutes);
        }

        $dps = $this->affectations->findBy(['personnel' => $user]);
        $retenue = null;
        foreach ($dps as $dp) {
            if ($dp->getDepartement() !== null && $dp->isDefaut()) {
                $retenue = $dp;
            }
        }
        $retenue ??= ($dps[0] ?? null);

        return $this->normaliser($retenue?->getPackages() ?? []);
    }

    public function hasPackage(Personnel|Etudiant $user, string $package): bool
    {
        $package = self::canonique($package);
        if (\in_array($package, self::TOUJOURS, true) || $this->permissions->isSuperAdmin($user)) {
            return true;
        }

        return \in_array($package, $this->packages($user), true);
    }

    public static function canonique(string $package): string
    {
        $p = strtolower($package);

        return self::ALIAS[$p] ?? $p;
    }

    /**
     * @param array<int, mixed> $noms
     *
     * @return list<string>
     */
    private function normaliser(array $noms): array
    {
        return array_values(array_unique(array_map(fn ($n) => self::canonique((string) $n), $noms)));
    }
}
