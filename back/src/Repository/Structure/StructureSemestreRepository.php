<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureSemestre;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureSemestre>
 */
class StructureSemestreRepository extends ServiceEntityRepository
{
    use FindAllByIdArrayTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureSemestre::class);
    }

    public function findSemestresByDepartement($departementId)
    {
        return $this->createQueryBuilder('s')
            ->join('s.annee', 'a')
            ->join('a.structureDiplome', 'd')
            ->where('d.departement = :departementId')
            ->setParameter('departementId', $departementId)
            ->getQuery()
            ->getResult();
    }
}
