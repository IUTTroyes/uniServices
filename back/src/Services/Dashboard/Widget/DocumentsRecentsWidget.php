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
        return '/api/dashboard/widgets/'.$this->getKey();
    }

    public function getData(Personnel $user, DashboardContext $context, array $config): array
    {
        return [
            'items' => [
                ['icon' => 'pi pi-file-pdf', 'titre' => 'Sujet TP Réseaux – VLAN', 'date' => '14/05/2025'],
                ['icon' => 'pi pi-file-pdf', 'titre' => 'Correction DS Linux', 'date' => '13/05/2025'],
                ['icon' => 'pi pi-file', 'titre' => 'Support de cours – TCP/IP', 'date' => '12/05/2025'],
                ['icon' => 'pi pi-file', 'titre' => 'Énoncé Projet DevOps', 'date' => '10/05/2025'],
                ['icon' => 'pi pi-file', 'titre' => 'Grille d\'évaluation BUT S2', 'date' => '08/05/2025'],
            ],
        ];
    }
}
