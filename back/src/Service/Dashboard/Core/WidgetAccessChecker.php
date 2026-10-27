<?php

namespace App\Service\Dashboard\Core;

use App\Domain\Dashboard\WidgetDefinition;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;

/** One authorization rule shared by catalog, preferences and data endpoints. */
final class WidgetAccessChecker
{
    public function __construct(private readonly UserEffectivePermissionService $permissions) {}

    public function isGranted(?WidgetDefinition $widget, Personnel|Etudiant $user): bool
    {
        if ($widget === null || !$widget->isAllowedForUser($user)) return false;
        $packages = $user instanceof Personnel
            ? ($this->permissions->getActiveDepartementPersonnel($user)?->getPackages() ?? [])
            : ($user->getApplications() ?? ['intranet']);
        $packages = array_map(self::normalizePackage(...), $packages);
        // The portal remains available without a business application.
        if (self::normalizePackage($widget->getBundle()) !== 'auth'
            && !in_array(self::normalizePackage($widget->getBundle()), $packages, true)) return false;

        $required = $widget->getRequiredPermissions();
        if ($required === []) return true;
        // Administrator convenience never overrides profile or package availability.
        if ($this->permissions->isSuperAdmin($user)) return true;
        $scopedPermissions = $this->permissions->getScopedPermissions($user);
        $results = array_map(function (string $permission) use ($user, $scopedPermissions): bool {
            if ($permission === 'ROLE_RESP_PARCOURS') {
                // Grants widget discovery only, never a department-wide resource permission.
                return !empty($scopedPermissions[$permission]['diplomeIds']);
            }
            return $this->permissions->hasPermission($user, $permission);
        }, $required);
        return $widget->requiresAllPermissions()
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    /** Scope for widgets granted solely through a responsibility of parcours. */
    public function getDiplomeIds(WidgetDefinition $widget, Personnel|Etudiant $user): ?array
    {
        if (!in_array('ROLE_RESP_PARCOURS', $widget->getRequiredPermissions(), true)
            || $this->permissions->isSuperAdmin($user)) return null;
        if (!$widget->requiresAllPermissions()) {
            foreach ($widget->getRequiredPermissions() as $permission) {
                if ($permission !== 'ROLE_RESP_PARCOURS' && $this->permissions->hasPermission($user, $permission)) return null;
            }
        }
        return $this->permissions->getScopedPermissions($user)['ROLE_RESP_PARCOURS']['diplomeIds'] ?? [];
    }

    private static function normalizePackage(string $package): string
    {
        $package = strtolower(trim($package));
        return match ($package) {
            'document' => 'documents',
            'portfolio', 'portfolios' => 'unifolio',
            'stages' => 'stage',
            default => $package,
        };
    }
}
