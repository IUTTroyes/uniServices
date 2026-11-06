<?php

namespace UnifolioBundle\Repository;

use App\Entity\Etudiant\EtudiantScolarite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Entity\AutoEvaluation;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;

/** @extends ServiceEntityRepository<AutoEvaluation> */
class AutoEvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutoEvaluation::class);
    }

    public function findOneByUuid(string $uuid): ?AutoEvaluation
    {
        return Uuid::isValid($uuid) ? $this->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
    }

    public function findOneByRattachement(TraceRattachement $rattachement): ?AutoEvaluation
    {
        return $this->findOneBy(['rattachement' => $rattachement]);
    }

    /**
     * @param TraceRattachement[] $rattachements
     *
     * @return array<int, AutoEvaluation> indexées par identifiant de rattachement
     */
    public function findParRattachements(array $rattachements): array
    {
        $ids = array_filter(array_map(static fn (TraceRattachement $r) => $r->getId(), $rattachements));
        if ($ids === []) {
            return [];
        }
        $resultat = [];
        foreach ($this->createQueryBuilder('a')->andWhere('a.rattachement IN (:ids)')->setParameter('ids', $ids)->getQuery()->getResult() as $a) {
            $resultat[$a->getRattachement()->getId()] = $a;
        }

        return $resultat;
    }

    public function findBilan(EtudiantScolarite $scolarite, GranulariteEnum $granularite, int $cibleId): ?AutoEvaluation
    {
        $champ = match ($granularite) {
            GranulariteEnum::COMPETENCE => 'competence',
            GranulariteEnum::NIVEAU => 'niveau',
            GranulariteEnum::AC => 'apprentissageCritique',
        };

        return $this->createQueryBuilder('a')
            ->andWhere('a.scolarite = :scolarite')->setParameter('scolarite', $scolarite)
            ->andWhere('a.rattachement IS NULL')
            ->andWhere("IDENTITY(a.$champ) = :cible")->setParameter('cible', $cibleId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return AutoEvaluation[] toutes les auto-évaluations (traces et bilans) d'une année */
    public function findParScolarite(EtudiantScolarite $scolarite): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.reponses', 'r')->addSelect('r')
            ->andWhere('a.scolarite = :scolarite')->setParameter('scolarite', $scolarite)
            ->getQuery()
            ->getResult();
    }
}
