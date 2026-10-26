<?php

namespace App\State\Processor\EtudiantScolariteSemestre;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\StructureSemestre;
use App\Repository\EtudiantScolariteSemestreRepository;
use App\Repository\Structure\StructureGroupeRepository;
use App\Repository\Structure\StructureSemestreRepository;
use Doctrine\ORM\EntityManagerInterface;

class SynchroSemestrePreviousProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly StructureSemestreRepository $semestreRepository,
        private readonly StructureGroupeRepository $groupeRepository,
        private readonly EtudiantScolariteSemestreRepository $scoSemestreRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $context['request'] ?? null;
        $payload = $request ? $request->toArray() : [];

        $semestreId = isset($payload['semestreId']) ? (int) $payload['semestreId'] : null;
        $anneeUniversitaireId = isset($payload['anneeUniversitaireId']) ? (int) $payload['anneeUniversitaireId'] : null;

        if (!$semestreId || !$anneeUniversitaireId) {
            return [
                'updatedCount' => 0,
                'errorCount' => 0,
                'status' => 'invalid_input',
                'message' => 'Paramètres semestreId et anneeUniversitaireId requis.',
            ];
        }

        $currentSemestre = $this->semestreRepository->find($semestreId);
        if (!$currentSemestre instanceof StructureSemestre) {
            return [
                'updatedCount' => 0,
                'errorCount' => 0,
                'status' => 'semester_not_found',
                'message' => 'Semestre courant introuvable.',
            ];
        }

        $previousSemestre = $this->semestreRepository->findOneBy([
            'annee' => $currentSemestre->getAnnee(),
            'ordreAnnee' => ($currentSemestre->getOrdreAnnee() ?? 0) - 1,
        ]);

        if (!$previousSemestre instanceof StructureSemestre) {
            return [
                'updatedCount' => 0,
                'errorCount' => 0,
                'status' => 'no_previous_semester',
                'message' => 'Impossible de trouver le semestre précédent pour cette année.',
            ];
        }

        $currentStudents = $this->scoSemestreRepository->findBySemestreAndAnneeUniversitaire(
            $currentSemestre->getId(),
            $anneeUniversitaireId
        );
        $previousStudents = $this->scoSemestreRepository->findBySemestreAndAnneeUniversitaire(
            $previousSemestre->getId(),
            $anneeUniversitaireId
        );

        if (0 === count($currentStudents)) {
            return [
                'updatedCount' => 0,
                'errorCount' => 0,
                'status' => 'no_students',
                'message' => 'Aucun étudiant à synchroniser pour ce semestre.',
            ];
        }

        $currentGroupes = $this->groupeRepository->findAllBySemestreId($currentSemestre->getId());
        $previousGroupes = $this->groupeRepository->findAllBySemestreId($previousSemestre->getId());

        $currentGroupByTypeOrder = [];
        foreach ($currentGroupes as $groupe) {
            if (!$groupe->getType() || null === $groupe->getOrdre()) {
                continue;
            }
            $currentGroupByTypeOrder[sprintf('%s::%s', $groupe->getType()->value, $groupe->getOrdre())] = $groupe;
        }

        $previousGroupById = [];
        foreach ($previousGroupes as $groupe) {
            $previousGroupById[$groupe->getId()] = $groupe;
        }

        $previousByEtudiantId = [];
        foreach ($previousStudents as $sco) {
            $etudiantId = $sco->getScolarite()?->getEtudiant()?->getId();
            if (null !== $etudiantId) {
                $previousByEtudiantId[$etudiantId] = $sco;
            }
        }

        $managedTypes = [];
        foreach ($currentGroupes as $groupe) {
            if ($groupe->getType()) {
                $managedTypes[$groupe->getType()->value] = true;
            }
        }

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($currentStudents as $currentSco) {
            $etudiantId = $currentSco->getScolarite()?->getEtudiant()?->getId();
            if (null === $etudiantId || !isset($previousByEtudiantId[$etudiantId])) {
                continue;
            }

            $previousSco = $previousByEtudiantId[$etudiantId];

            $currentExistingGroupes = $currentSco->getGroupes()->toArray();
            $conservedGroupes = array_values(array_filter(
                $currentExistingGroupes,
                fn ($g) => !isset($managedTypes[$g->getType()?->value ?? ''])
            ));

            $mappedGroupes = [];
            foreach ($previousSco->getGroupes() as $previousGroupeRef) {
                $previousGroupe = $previousGroupById[$previousGroupeRef->getId()] ?? null;
                if (!$previousGroupe || !$previousGroupe->getType() || null === $previousGroupe->getOrdre()) {
                    continue;
                }

                $key = sprintf('%s::%s', $previousGroupe->getType()->value, $previousGroupe->getOrdre());
                $target = $currentGroupByTypeOrder[$key] ?? null;
                if (null === $target) {
                    continue;
                }

                $alreadyAdded = array_filter(
                    $mappedGroupes,
                    fn ($g) => $g->getId() === $target->getId()
                );
                if ([] === $alreadyAdded) {
                    $mappedGroupes[] = $target;
                }
            }

            $newGroupes = [...$conservedGroupes, ...$mappedGroupes];

            $currentIds = array_map(static fn ($g) => $g->getId(), $currentExistingGroupes);
            sort($currentIds);

            $newIds = array_map(static fn ($g) => $g->getId(), $newGroupes);
            sort($newIds);

            if ($currentIds === $newIds) {
                continue;
            }

            try {
                $currentSco->getGroupes()->clear();
                foreach ($newGroupes as $groupe) {
                    $currentSco->addGroupe($groupe);
                }
                $updatedCount++;
            } catch (\Throwable) {
                $errorCount++;
            }
        }

        $this->entityManager->flush();

        return [
            'updatedCount' => $updatedCount,
            'errorCount' => $errorCount,
            'status' => 'ok',
            'message' => 'Synchronisation terminée.',
        ];
    }
}
