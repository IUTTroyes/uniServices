<?php

namespace App\State\Provider\Users;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Users\ActionsUrgentesWidgetDto;

class ActionsUrgentesWidgetProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $dto = new ActionsUrgentesWidgetDto();
        
        $dto->items = [
            ['icon' => 'pi pi-file', 'titre' => '3 copies à corriger', 'detail' => 'Date limite : 22 mai 2025', 'cta' => 'Corriger', 'color' => 'red'],
            ['icon' => 'pi pi-pencil', 'titre' => 'Notes à saisir', 'detail' => 'Avant jeudi 18h', 'cta' => 'Saisir les notes', 'color' => 'orange'],
            ['icon' => 'pi pi-user', 'titre' => '2 absences à justifier', 'detail' => 'En attente de justificatif', 'cta' => 'Voir les absences', 'color' => 'yellow'],
        ];

        return $dto;
    }
}
