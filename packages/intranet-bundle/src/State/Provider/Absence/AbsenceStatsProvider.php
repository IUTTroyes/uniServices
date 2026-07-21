<?php

namespace IntranetBundle\State\Provider\Absence;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class AbsenceStatsProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $stats = [];
            $absencesResult = $this->collectionProvider->provide($operation, $uriVariables, $context);

            // Conversion en tableau, quelle que soit la forme (array, Traversable, Paginator...)
            $absences = is_array($absencesResult)
                ? $absencesResult
                : iterator_to_array($absencesResult);

            $stats['total']['title'] = 'Total d\'absences';
            $stats['total']['icon'] = 'pi pi-list';
            $stats['total']['color'] = 'yellow-500';
            $stats['total']['value'] = count($absences);
            $stats['justifiee']['title'] = 'Justifiées';
            $stats['justifiee']['icon'] = 'pi pi-check';
            $stats['justifiee']['color'] = 'green-500';
            $stats['justifiee']['value'] = count(array_filter($absences, fn($absence) => $absence->isJustifiee()));
            $stats['non_justifiee']['title'] = 'Non justifiées';
            $stats['non_justifiee']['icon'] = 'pi pi-times';
            $stats['non_justifiee']['color'] = 'red-500';
            $stats['non_justifiee']['value'] = count(array_filter($absences, fn($absence) => !$absence->isJustifiee()));
            $stats['scolarite_semestre']['title'] = 'Étudiants concernés';
            $stats['scolarite_semestre']['icon'] = 'pi pi-calendar';
            $stats['scolarite_semestre']['color'] = 'blue-500';
            $stats['scolarite_semestre']['value'] = count(array_unique(array_map(fn($absence) => $absence->getScolariteSemestre()->getId(), $absences)));

            return $stats;
        }

        return null;
    }
}
