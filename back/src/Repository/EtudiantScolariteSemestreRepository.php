<?php

namespace App\Repository;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtudiantScolariteSemestre>
 */
class EtudiantScolariteSemestreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtudiantScolariteSemestre::class);
    }

    /**
     * @return EtudiantScolariteSemestre[]
     */
    public function findByGroupeAndSemestre(int $groupeId, int $semestreId): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.groupes', 'g')
            ->andWhere('g.id = :groupeId')
            ->andWhere('e.semestre = :semestreId')
            ->setParameter('groupeId', $groupeId)
            ->setParameter('semestreId', $semestreId)
            ->getQuery()
            ->getResult()
        ;
    }
}
