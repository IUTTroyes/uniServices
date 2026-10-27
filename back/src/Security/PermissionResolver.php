<?php

namespace App\Security;

use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDiplome;

class PermissionResolver
{
    public function __construct(
        private readonly PermissionRegistry $registry
    ) {}

    /**
     * Resolves all effective permissions given a set of directly assigned permissions (roles)
     * and a list of active package names.
     *
     * @param string[] $assignedRoles E.g., ['ROLE_STAGE_MANAGER']
     * @param string[] $activePackages E.g., ['intranet', 'stages']
     * @return string[] Resolved Symfony roles, e.g. ['ROLE_STAGE_MANAGER', 'ROLE_STAGE_VIEW', ...]
     */
    public function resolve(array $assignedRoles, array $activePackages): array
    {
        $activePackages = array_values(array_unique(array_map(
            static fn (string $package): string => $package === 'stage' ? 'stages' : $package,
            $activePackages
        )));
        // A scoped role must never be granted by a flat role list or inheritance.
        $assignedRoles = array_values(array_diff($assignedRoles, ['ROLE_RESP_PARCOURS']));
        $aliases = ['ROLE_NOTE' => 'ROLE_NOTES_MANAGER', 'ROLE_RESP_NOTES' => 'ROLE_NOTES_MANAGER', 'ROLE_STAGE' => 'ROLE_STAGE_MANAGER'];
        $assignedRoles = array_map(static fn (string $role): string => $aliases[$role] ?? $role, $assignedRoles);
        $allPermissions = $this->registry->getPermissions();
        $resolved = [];

        // 1. Add default access permissions for active packages
        foreach ($allPermissions as $role => $definition) {
            if (in_array($definition->getPackage(), $activePackages, true) && $definition->isDefaultAccess()) {
                $resolved[$role] = true;
            }
        }

        // 2. Add direct permissions (only if their package is active)
        foreach ($assignedRoles as $role) {
            $definition = $this->registry->getPermissionByRole($role);
            if ($definition && in_array($definition->getPackage(), $activePackages, true)) {
                $resolved[$role] = true;
            }
        }

        // 3. Resolve inheritance recursively
        $toProcess = array_keys($resolved);
        while (!empty($toProcess)) {
            $currentRole = array_pop($toProcess);
            $definition = $this->registry->getPermissionByRole($currentRole);
            if ($definition) {
                foreach ($definition->getInheritedRoles() as $inheritedRole) {
                    if ($inheritedRole !== 'ROLE_RESP_PARCOURS' && !isset($resolved[$inheritedRole])) {
                        $inheritedDef = $this->registry->getPermissionByRole($inheritedRole);
                        if ($inheritedDef && in_array($inheritedDef->getPackage(), $activePackages, true)) {
                            $resolved[$inheritedRole] = true;
                            $toProcess[] = $inheritedRole;
                        }
                    }
                }
            }
        }

        unset($resolved['ROLE_RESP_PARCOURS']);
        return array_keys($resolved);
    }
    public function resolveForDepartment(StructureDepartementPersonnel $assignment, ?StructureDiplome $diplome = null): array
    {
        $permissions = $this->resolve($assignment->getPermissions(), $assignment->getPackages());
        if ($diplome !== null
            && $assignment->isResponsableParcoursForDiplome($diplome)
            && in_array('intranet', $assignment->getPackages(), true)
            && in_array('ROLE_RESP_PARCOURS', $assignment->getPermissions(), true)
            && $diplome->getDepartement()?->getId() === $assignment->getDepartement()?->getId()
        ) {
            $permissions[] = 'ROLE_RESP_PARCOURS';
        }
        return $permissions;
    }

}
