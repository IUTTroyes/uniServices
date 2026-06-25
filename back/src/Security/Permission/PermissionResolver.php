<?php

namespace App\Security\Permission;

final readonly class PermissionResolver
{
    public function __construct(
        private PermissionRegistry $registry,
    ) {}

    public function resolve(array $roles): array
    {
        $resolved = [];

        foreach ($roles as $role) {
            $this->resolveRole($role, $resolved);
        }

        return array_values(array_unique($resolved));
    }

    private function resolveRole(string $role, array &$resolved): void
    {
        if (in_array($role, $resolved, true)) {
            return;
        }

        $resolved[] = $role;

        $definition = $this->registry->get($role);

        if (!$definition) {
            return;
        }

        foreach ($definition->inherits as $inheritedRole) {
            $this->resolveRole($inheritedRole, $resolved);
        }
    }
}
