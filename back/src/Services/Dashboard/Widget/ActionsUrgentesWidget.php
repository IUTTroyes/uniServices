<?php

namespace App\Services\Dashboard\Widget;

use App\Domain\Dashboard\DashboardContext;
use App\Domain\Dashboard\DashboardWidgetInterface;
use App\Entity\Users\Personnel;

class ActionsUrgentesWidget implements DashboardWidgetInterface
{
    public function getKey(): string
    {
        return 'actions_urgentes';
    }

    public function getLabel(): string
    {
        return 'Actions urgentes';
    }

    public function getVueComponent(): string
    {
        return 'ActionsUrgentesWidget';
    }

    public function supports(Personnel $user, DashboardContext $context): bool
    {
        return $context->hasDepartement();
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
                ['icon' => 'pi pi-file', 'titre' => '3 copies à corriger', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Corriger', 'color' => 'red'],
                ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
                ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
            ],
        ];
    }
}
