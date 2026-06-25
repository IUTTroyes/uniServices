<?php

namespace App\Security\Permission;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class PermissionRegistry
{
    /**
     * @param iterable<PermissionProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('app.permission_provider')]
        private iterable $providers,
    ) {}

    /**
     * @return array<string, PermissionDefinition>
     */
    public function all(): array
    {
        $permissions = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->getPermissions() as $permission) {
                $permissions[$permission->role] = $permission;
            }
        }

        return $permissions;
    }

    public function get(string $role): ?PermissionDefinition
    {
        return $this->all()[$role] ?? null;
    }

    public function byPackage(string $package): array
    {
        return array_filter(
            $this->all(),
            fn (PermissionDefinition $permission) => $permission->package === $package
        );
    }
}
