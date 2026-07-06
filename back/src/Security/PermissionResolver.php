<?php

namespace App\Security;

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
     * @param string[] $activePackages E.g., ['core', 'stages']
     * @return string[] Resolved Symfony roles, e.g. ['ROLE_STAGE_MANAGER', 'ROLE_STAGE_VIEW', ...]
     */
    public function resolve(array $assignedRoles, array $activePackages): array
    {
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
                    if (!isset($resolved[$inheritedRole])) {
                        $inheritedDef = $this->registry->getPermissionByRole($inheritedRole);
                        if ($inheritedDef && in_array($inheritedDef->getPackage(), $activePackages, true)) {
                            $resolved[$inheritedRole] = true;
                            $toProcess[] = $inheritedRole;
                        }
                    }
                }
            }
        }

        return array_keys($resolved);
    }
}
