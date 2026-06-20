<?php

namespace App\Services\Dashboard\Widget;

use App\Domain\Dashboard\DashboardContext;
use App\Domain\Dashboard\DashboardWidgetInterface;
use App\Entity\Users\Personnel;

class DocumentsRecentsWidget implements DashboardWidgetInterface
{
    public function getKey(): string
    {
        return 'documents_recents';
    }

    public function getLabel(): string
    {
        return 'Documents récents';
    }

    public function getIcon(): string
    {
        return 'pi pi-file';
    }

    public function getVueComponent(): string
    {
        return 'DocumentsRecentsWidget';
    }

    public function supports(Personnel $user, DashboardContext $context): bool
    {
        $roles = $user->getRoles();

        return in_array('ROLE_ASSISTANT', $roles, true)
            || in_array('ROLE_SCOLARITE', $roles, true)
            || in_array('ROLE_DIRECTION', $roles, true)
            || in_array('ROLE_SUPER_ADMIN', $roles, true);
    }

    public function getDefaultConfig(): array
    {
        return ['limit' => 5];
    }

    public function getDefaultSize(): string
    {
        return 'medium';
    }

    public function isDefaultEnabled(): bool
    {
        return true;
    }

    public function getDataUrl(): string
    {
        return '/api/widget/StructureDepartement';
    }
}
