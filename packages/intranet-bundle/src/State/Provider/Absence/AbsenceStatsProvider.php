<?php

namespace IntranetBundle\State\Provider\Absence;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class AbsenceStatsProvider implements ProviderInterface
{

    public function __construct(
        private AbsenceEpisodeProvider $absenceEpisodeProvider,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $stats = [];
            $episodesResult = $this->absenceEpisodeProvider->provide($operation, $uriVariables, $context);
            if ($episodesResult === null) {
                $episodes = [];
            } elseif (is_array($episodesResult)) {
                $episodes = $episodesResult;
            } elseif ($episodesResult instanceof \Traversable) {
                $episodes = iterator_to_array($episodesResult);
            } else {
                $episodes = [];
            }

            $stats['total']['title'] = 'Total de périodes d\'absence';
            $stats['total']['icon'] = 'pi pi-list';
            $stats['total']['color'] = 'yellow-500';
            $stats['total']['value'] = count($episodes);
            $stats['justifiee']['title'] = 'Justifiées';
            $stats['justifiee']['icon'] = 'pi pi-check';
            $stats['justifiee']['color'] = 'green-500';
            $stats['justifiee']['value'] = count(array_filter($episodes, fn(array $episode) => (bool)($episode['justifiee'] ?? false)));
            $stats['non_justifiee']['title'] = 'Non justifiées';
            $stats['non_justifiee']['icon'] = 'pi pi-times';
            $stats['non_justifiee']['color'] = 'red-500';
            $stats['non_justifiee']['value'] = count(array_filter($episodes, fn(array $episode) => !($episode['justifiee'] ?? false)));
            $stats['scolarite_semestre']['title'] = 'Étudiants concernés';
            $stats['scolarite_semestre']['icon'] = 'pi pi-calendar';
            $stats['scolarite_semestre']['color'] = 'blue-500';
            $stats['scolarite_semestre']['value'] = count(
                array_unique(
                    array_filter(
                        array_map(fn(array $episode) => $episode['scolariteSemestreId'] ?? null, $episodes),
                        fn($id) => $id !== null
                    )
                )
            );

            return $stats;
        }

        return null;
    }
}
