<?php

namespace App\State\Provider\Structure;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Structure\DocumentsRecentsWidgetDto;

class DocumentsRecentsWidgetProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $dto = new DocumentsRecentsWidgetDto();
        
        $dto->items = [
            ['icon' => 'pi pi-file-pdf', 'titre' => 'Sujet TP Réseaux – VLAN', 'date' => '14/05/2025'],
            ['icon' => 'pi pi-file-pdf', 'titre' => 'Correction DS Linux', 'date' => '13/05/2025'],
            ['icon' => 'pi pi-file', 'titre' => 'Support de cours – TCP/IP', 'date' => '12/05/2025'],
            ['icon' => 'pi pi-file', 'titre' => 'Énoncé Projet DevOps', 'date' => '10/05/2025'],
            ['icon' => 'pi pi-file', 'titre' => 'Grille d\'évaluation BUT S2', 'date' => '08/05/2025'],
        ];

        return $dto;
    }
}
