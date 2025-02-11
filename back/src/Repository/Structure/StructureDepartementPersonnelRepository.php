<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureDepartementPersonnel>
 */
class StructureDepartementPersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureDepartementPersonnel::class);
    }

    public function findOneByPersonnelAffectation(int $personnelId): ?StructureDepartementPersonnel
    {
        return $this->createQueryBuilder('s')
            ->where('s.personnel = :personnel')
            ->andWhere('s.affectation = true')
            ->setParameter('personnel', $personnelId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return StructureDepartementPersonnel[] Returns an array of StructureDepartementPersonnel objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?StructureDepartementPersonnel
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
