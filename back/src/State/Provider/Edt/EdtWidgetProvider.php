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

        $now = new \DateTimeImmutable();

        $formatter = new \IntlDateFormatter(
            'fr_FR',                        // locale français
            \IntlDateFormatter::FULL,       // format de date
            \IntlDateFormatter::NONE,       // pas d'heure
            null,                           // timezone (null = celle du système)
            \IntlDateFormatter::GREGORIAN,  // calendrier
            'EEEE d MMMM yyyy'             // pattern personnalisé
        );

        $dto->todayLabel = $formatter->format($now);

        if (!is_iterable($data)) {
            return $dto;
        }

        foreach ($data as $event) {
            if (!$event instanceof EdtEvent) {
                continue;
            }

            $dto->items[] = [
                'heure' => $event->getDebut()?->format('H:i') . ' - ' . $event->getFin()?->format('H:i') ?? '',
                'groupe' => $event->getLibGroupe(),
                'eval' => $event->isEvaluation(),
                'cours' => $event->getCodeModule() . ' - ' . $event->getLibModule() ?? 'Cours',
                'salle' => $event->getSalle() ?? '-',
                'color' => $event->getCouleur(),
            ];
        }


        return $dto;
    }
}
