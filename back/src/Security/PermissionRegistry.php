<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class PermissionRegistry
{
    /**
     * @var PermissionDefinition[]
     */
    private array $permissions = [];

    /**
     * @param iterable<PermissionProviderInterface> $providers
     */
    public function __construct(
        #[TaggedIterator('app.permission_provider')] iterable $providers
    ) {
        foreach ($providers as $provider) {
            foreach ($provider->getPermissions() as $definition) {
                $this->permissions[$definition->getRole()] = $definition;
            }
        }
    }

    /**
     * @return PermissionDefinition[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getPermissionByRole(string $role): ?PermissionDefinition
    {
        return $this->permissions[$role] ?? null;
    }
}
