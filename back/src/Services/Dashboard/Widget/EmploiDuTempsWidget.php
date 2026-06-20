<?php

namespace App\Services\Dashboard\Widget;

use App\Domain\Dashboard\DashboardContext;
use App\Domain\Dashboard\DashboardWidgetInterface;
use App\Entity\Users\Personnel;

class EmploiDuTempsWidget implements DashboardWidgetInterface
{
    public function getKey(): string
    {
        return 'emploi_du_temps';
    }

    public function getIcon(): string
    {
        return 'pi pi-calendar';
    }

    public function getLabel(): string
    {
        return 'Aujourd\'hui';
    }

    public function getVueComponent(): string
    {
        return 'EmploiDuTempsWidget';
    }

    public function supports(Personnel $user, DashboardContext $context): bool
    {
        $roles = $user->getRoles();

        return in_array('ROLE_EDT', $roles, true)
            || in_array('ROLE_DIRECTION', $roles, true)
            || in_array('ROLE_SCOLARITE', $roles, true)
            || in_array('ROLE_CHEF_DEPARTEMENT', $roles, true)
            || in_array('ROLE_SUPER_ADMIN', $roles, true);
    }

    public function getDefaultConfig(): array
    {
        return ['limit' => 10];
    }

    public function getDefaultSize(): string
    {
        return 'large';
    }

    public function isDefaultEnabled(): bool
    {
        return true;
    }

    public function getDataUrl(): string
    {
        return '/api/widget/edt_events';
    }
}
