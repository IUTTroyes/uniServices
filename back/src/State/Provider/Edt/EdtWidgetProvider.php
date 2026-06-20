<?php

namespace App\State\Provider\Edt;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Edt\EdtWidgetDto;
use App\Entity\Edt\EdtEvent;

class EdtWidgetProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

        $dto = new EdtWidgetDto();
        $dto->todayLabel = (new \DateTimeImmutable())->format('d/m/Y');

        if (!is_iterable($data)) {
            return $dto;
        }

        foreach ($data as $event) {
            if (!$event instanceof EdtEvent) {
                continue;
            }

            $dto->items[] = [
                'heure' => $event->getDebut()?->format('H:i') ?? '',
                'type' => $event->isEvaluation() ? 'Évaluation' : 'Cours',
                'cours' => $event->getLibModule() ?? 'Cours',
                'salle' => $event->getSalle() ?? '-',
                'color' => $event->isEvaluation() ? 'purple' : 'blue',
            ];
        }


        return $dto;
    }
}
