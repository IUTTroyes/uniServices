<?php

namespace App\Repository;

use App\Entity\Etudiant\EtudiantNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtudiantNote>
 */
class EtudiantNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtudiantNote::class);
    }

    public function countByEvaluation(\App\Entity\Scolarite\ScolEvaluation $evaluation): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.evaluation = :evaluation')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompletedByEvaluation(\App\Entity\Scolarite\ScolEvaluation $evaluation): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.evaluation = :evaluation')
            ->andWhere('(n.note IS NOT NULL OR n.absenceJustifiee = true)')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
