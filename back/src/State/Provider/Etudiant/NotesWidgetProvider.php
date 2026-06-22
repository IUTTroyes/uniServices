<?php

namespace App\State\Provider\Etudiant;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Etudiant\NotesWidgetDto;

class NotesWidgetProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $dto = new NotesWidgetDto();

        $dto->items = [
            ['semestre' => 'S2', 'titre' => 'Initiation au Développement Web', 'completion' => 70, 'color' => 'blue', 'date' => '20/06/2026'],
            ['semestre' => 'S4 DevWebDi', 'titre' => 'Développement Back Avancé', 'completion' => 50, 'color' => 'green', 'date' => '21/06/2026'],
            ['semestre' => 'S6 Strat-UX FC', 'titre' => 'Stratégie et Management', 'completion' => 90, 'color' => 'purple', 'date' => '22/06/2026'],
        ];

        return $dto;
    }
}
