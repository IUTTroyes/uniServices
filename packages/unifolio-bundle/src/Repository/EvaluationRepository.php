<?php

namespace UnifolioBundle\Repository;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\StatutEvaluationEnum;

/** @extends ServiceEntityRepository<Evaluation> */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    public function findOneByUuid(string $uuid): ?Evaluation
    {
        return Uuid::isValid($uuid) ? $this->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
    }

    public function findOneParEvaluateurEtRattachement(Personnel $evaluateur, TraceRattachement $rattachement): ?Evaluation
    {
        return $this->findOneBy(['evaluateur' => $evaluateur, 'rattachement' => $rattachement]);
    }

    public function findBilan(Personnel $evaluateur, EtudiantScolarite $scolarite, GranulariteEnum $granularite, int $cibleId): ?Evaluation
    {
        return $this->requeteBilan($scolarite, $granularite, $cibleId)
            ->andWhere('e.evaluateur = :evaluateur')->setParameter('evaluateur', $evaluateur)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Une évaluation rendue existe-t-elle sur ce rattachement ? (verrouille l'auto-évaluation de l'étudiant) */
    public function existeRendueSurRattachement(TraceRattachement $rattachement): bool
    {
        return $this->count(['rattachement' => $rattachement, 'statut' => StatutEvaluationEnum::RENDUE]) > 0;
    }

    public function existeRendueSurBilan(EtudiantScolarite $scolarite, GranulariteEnum $granularite, int $cibleId): bool
    {
        return $this->requeteBilan($scolarite, $granularite, $cibleId)
            ->andWhere('e.statut = :statut')
            ->setParameter('statut', StatutEvaluationEnum::RENDUE)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /** @return Evaluation[] évaluations de tous les évaluateurs sur un élément du référentiel (bilan) */
    public function findBilans(EtudiantScolarite $scolarite, GranulariteEnum $granularite, int $cibleId): array
    {
        return $this->requeteBilan($scolarite, $granularite, $cibleId)->getQuery()->getResult();
    }

    /** @return Evaluation[] toutes les évaluations des rattachements d'une trace */
    public function findParTrace(Trace $trace): array
    {
        $ids = array_filter(array_map(static fn (TraceRattachement $r) => $r->getId(), $trace->getRattachements()->toArray()));
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('e')
            ->leftJoin('e.reponses', 'r')->addSelect('r')
            ->andWhere('e.rattachement IN (:ids)')->setParameter('ids', $ids)
            ->orderBy('e.rendueLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Evaluation[] évaluations (traces et bilans) d'une année d'un étudiant */
    public function findParScolarite(EtudiantScolarite $scolarite): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.reponses', 'r')->addSelect('r')
            ->andWhere('e.scolarite = :scolarite')->setParameter('scolarite', $scolarite)
            ->getQuery()
            ->getResult();
    }

    /** @return Evaluation[] évaluations d'un évaluateur pour une campagne */
    public function findParEvaluateurEtCampagne(Personnel $evaluateur, Campagne $campagne): array
    {
        return $this->findBy(['evaluateur' => $evaluateur, 'campagne' => $campagne]);
    }

    private function requeteBilan(EtudiantScolarite $scolarite, GranulariteEnum $granularite, int $cibleId): \Doctrine\ORM\QueryBuilder
    {
        $champ = match ($granularite) {
            GranulariteEnum::COMPETENCE => 'competence',
            GranulariteEnum::NIVEAU => 'niveau',
            GranulariteEnum::AC => 'apprentissageCritique',
        };

        return $this->createQueryBuilder('e')
            ->andWhere('e.scolarite = :scolarite')->setParameter('scolarite', $scolarite)
            ->andWhere('e.rattachement IS NULL')
            ->andWhere("IDENTITY(e.$champ) = :cible")->setParameter('cible', $cibleId);
    }
}
