<?php

namespace App\State\Provider\Etudiant;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureAnnee;
use Doctrine\ORM\EntityManagerInterface;

class EtudiantAnneesStatsProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private EntityManagerInterface $entityManager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            $annees = [];
            $anneeIds = [];

            foreach ($data as $annee) {
                if (!$annee instanceof StructureAnnee || null === $annee->getId()) {
                    continue;
                }

                $anneeId = $annee->getId();
                $annees[$anneeId] = [
                    'id' => $anneeId,
                    'libelle' => $annee->getLibelle(),
                    'etudiantsCount' => 0,
                ];
                $anneeIds[] = $anneeId;
            }

            if ([] === $anneeIds) {
                return [];
            }

            $countByAnneeId = $this->getEtudiantsCountByAnnee($anneeIds, $context['filters'] ?? []);

            foreach ($countByAnneeId as $anneeId => $count) {
                if (!isset($annees[$anneeId])) {
                    continue;
                }

                $annees[$anneeId]['etudiantsCount'] = $count;
            }

            return array_values($annees);
        }

        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }

    private function getEtudiantsCountByAnnee(array $anneeIds, array $filters): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb
            ->select('annee.id AS anneeId, COUNT(DISTINCT scolarite.id) AS total')
            ->from(EtudiantScolarite::class, 'scolarite')
            ->join('scolarite.scolariteSemestre', 'scolariteSemestre')
            ->join('scolariteSemestre.semestre', 'semestre')
            ->join('semestre.annee', 'annee')
            ->andWhere('annee.id IN (:anneeIds)')
            ->setParameter('anneeIds', $anneeIds)
            ->groupBy('annee.id');

        $departement = $this->resolveFilterValue($filters, 'departement');
        if (null !== $departement && '' !== $departement) {
            $qb
                ->join('scolarite.departement', 'departement')
                ->andWhere('departement.id = :departement')
                ->setParameter('departement', $departement);
        }

        $anneeUniversitaire = $this->resolveFilterValue($filters, 'anneeUniversitaire');
        if (null !== $anneeUniversitaire && '' !== $anneeUniversitaire) {
            $qb
                ->join('scolarite.anneeUniversitaire', 'anneeUniversitaire')
                ->andWhere('anneeUniversitaire.id = :anneeUniversitaire')
                ->setParameter('anneeUniversitaire', $anneeUniversitaire);
        }

        $actif = $this->normalizeBoolean($this->resolveFilterValue($filters, 'actif'));
        if (null !== $actif) {
            $qb
                ->andWhere('scolarite.actif = :actif')
                ->setParameter('actif', $actif);
        }

        $counts = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $counts[(int) $row['anneeId']] = (int) $row['total'];
        }

        return $counts;
    }

    private function resolveFilterValue(array $filters, string $key): mixed
    {
        $value = $filters[$key] ?? null;

        if (!is_array($value)) {
            return $value;
        }

        return $value[0] ?? null;
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
