<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureGroupe;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureGroupe>
 */
class StructureGroupeRepository extends ServiceEntityRepository
{
    use FindAllByIdArrayTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureGroupe::class);
    }

    public function findBySemestreId($semestreId)
    {
        return $this->createQueryBuilder('g')
            ->join('g.semestres', 's')
            ->andWhere('s.id = :semestreId')
            ->andWhere('g.parent IS NULL')
            ->setParameter('semestreId', $semestreId)
            ->getQuery()
            ->getResult();
    }
}
