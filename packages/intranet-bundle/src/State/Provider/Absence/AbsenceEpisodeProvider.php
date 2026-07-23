<?php

namespace IntranetBundle\State\Provider\Absence;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use IntranetBundle\Entity\Etudiant\EtudiantAbsence;

class AbsenceEpisodeProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof GetCollection) {
            return null;
        }

        $unpaginatedOperation = $operation->withPaginationEnabled(false);
        $absencesResult = $this->collectionProvider->provide($unpaginatedOperation, $uriVariables, $context);
        $absences = is_array($absencesResult)
            ? $absencesResult
            : iterator_to_array($absencesResult);

        usort($absences, function (EtudiantAbsence $a, EtudiantAbsence $b) {
            $studentA = $a->getScolariteSemestre()?->getId() ?? 0;
            $studentB = $b->getScolariteSemestre()?->getId() ?? 0;

            if ($studentA !== $studentB) {
                return $studentA <=> $studentB;
            }

            $dateA = $a->getEvent()?->getDate()?->format('Y-m-d') ?? '0000-00-00';
            $dateB = $b->getEvent()?->getDate()?->format('Y-m-d') ?? '0000-00-00';
            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }

            $debutA = $a->getEvent()?->getDebut()?->format('H:i:s') ?? '00:00:00';
            $debutB = $b->getEvent()?->getDebut()?->format('H:i:s') ?? '00:00:00';

            return $debutA <=> $debutB;
        });

        if ($this->isFlatMode($context)) {
            $flatRows = array_map(fn(EtudiantAbsence $absence) => $this->buildFlatRow($absence), $absences);

            return $this->paginateRows($flatRows, $context);
        }

        $episodes = $this->buildEpisodes($absences);

        return $this->paginateRows($episodes, $context);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function paginateRows(array $rows, array $context): array
    {
        $filters = $context['filters'] ?? [];
        $page = max(1, (int)($filters['page'] ?? 1));
        $itemsPerPage = (int)($filters['itemsPerPage'] ?? 0);

        if ($itemsPerPage <= 0) {
            return $rows;
        }

        $offset = ($page - 1) * $itemsPerPage;

        return array_slice($rows, $offset, $itemsPerPage);
    }

    private function isFlatMode(array $context): bool
    {
        $filters = $context['filters'] ?? [];

        return isset($filters['event']) || isset($filters['personnel']);
    }

    /**
     * @param EtudiantAbsence[] $absences
     * @return array<int, array<string, mixed>>
     */
    private function buildEpisodes(array $absences): array
    {
        $episodes = [];
        $currentEpisode = null;

        foreach ($absences as $absence) {
            $currentDate = $absence->getEvent()?->getDate();
            if (!$currentDate) {
                continue;
            }

            $studentId = $absence->getScolariteSemestre()?->getId();
            $status = (bool)$absence->isJustifiee();

            if ($currentEpisode === null || !$this->canJoinEpisode($currentEpisode, $studentId, $currentDate, $status)) {
                if ($currentEpisode !== null) {
                    $episodes[] = $currentEpisode;
                }

                $currentEpisode = $this->newEpisode($absence);
                continue;
            }

            $currentEpisode['dateFin'] = $currentDate->format('Y-m-d');
            $currentEpisode['creneauxCount']++;
            $currentEpisode['events'][] = $this->mapEvent($absence);
            $currentEpisode['absences'][] = $this->mapAbsence($absence);
            $currentEpisode['absenceIds'][] = $absence->getId();
            $currentEpisode['jours'][(int)$currentDate->format('Ymd')] = true;
            $currentEpisode['joursCount'] = count($currentEpisode['jours']);
        }

        if ($currentEpisode !== null) {
            $episodes[] = $currentEpisode;
        }

        return array_map(function (array $episode) {
            unset($episode['jours']);

            return $episode;
        }, $episodes);
    }

    /**
     * @param array<string, mixed> $episode
     */
    private function canJoinEpisode(array $episode, ?int $studentId, \DateTimeInterface $date, bool $status): bool
    {
        if ($episode['scolariteSemestreId'] !== $studentId || $episode['justifiee'] !== $status) {
            return false;
        }

        $lastDate = new \DateTimeImmutable($episode['dateFin']);
        $currentDay = new \DateTimeImmutable($date->format('Y-m-d'));
        $diffDays = (int)$lastDate->diff($currentDay)->format('%r%a');

        return $diffDays >= 0 && $diffDays <= 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function newEpisode(EtudiantAbsence $absence): array
    {
        $date = $absence->getEvent()?->getDate()?->format('Y-m-d');
        $student = $absence->getScolariteSemestre()?->getScolarite()?->getEtudiant();

        return [
            'id' => sprintf(
                'episode-%s-%s-%s',
                $absence->getScolariteSemestre()?->getId() ?? 'none',
                $absence->isJustifiee() ? 'justifiee' : 'non-justifiee',
                $date ?? 'unknown'
            ),
            'mode' => 'episode',
            'justifiee' => (bool)$absence->isJustifiee(),
            'scolariteSemestreId' => $absence->getScolariteSemestre()?->getId(),
            'etudiantDisplay' => $student?->getDisplay() ?? '-',
            'dateDebut' => $date,
            'dateFin' => $date,
            'joursCount' => 1,
            'creneauxCount' => 1,
            'absenceIds' => [$absence->getId()],
            'events' => [$this->mapEvent($absence)],
            'absences' => [$this->mapAbsence($absence)],
            'jours' => [$date ? (int)str_replace('-', '', $date) : 0 => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFlatRow(EtudiantAbsence $absence): array
    {
        $date = $absence->getEvent()?->getDate()?->format('Y-m-d');
        $student = $absence->getScolariteSemestre()?->getScolarite()?->getEtudiant();

        return [
            'id' => sprintf('absence-%s', $absence->getId()),
            'mode' => 'flat',
            'justifiee' => (bool)$absence->isJustifiee(),
            'scolariteSemestreId' => $absence->getScolariteSemestre()?->getId(),
            'etudiantDisplay' => $student?->getDisplay() ?? '-',
            'dateDebut' => $date,
            'dateFin' => $date,
            'joursCount' => 1,
            'creneauxCount' => 1,
            'absenceIds' => [$absence->getId()],
            'events' => [$this->mapEvent($absence)],
            'absences' => [$this->mapAbsence($absence)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapEvent(EtudiantAbsence $absence): array
    {
        $event = $absence->getEvent();

        return [
            'id' => $event?->getId(),
            'date' => $event?->getDate()?->format('Y-m-d'),
            'debut' => $event?->getDebut()?->format(DATE_ATOM),
            'fin' => $event?->getFin()?->format(DATE_ATOM),
            'codeModule' => $event?->getCodeModule(),
            'libModule' => $event?->getLibModule(),
            'libGroupe' => $event?->getLibGroupe(),
            'codeGroupe' => $event?->getCodeGroupe(),
            'salle' => $event?->getSalle(),
            'couleur' => $event?->getCouleur(),
            'intervenant' => $event?->getPersonnel()?->getDisplay() ?? '-',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAbsence(EtudiantAbsence $absence): array
    {
        $personnel = $absence->getPersonnel();

        return [
            'id' => $absence->getId(),
            'justifiee' => (bool)$absence->isJustifiee(),
            'personnelDisplay' => $personnel?->getDisplay() ?? '-',
            'created' => $absence->getCreated()?->format(DATE_ATOM),
            'updated' => $absence->getUpdated()?->format(DATE_ATOM),
            'event' => $this->mapEvent($absence),
        ];
    }
}
