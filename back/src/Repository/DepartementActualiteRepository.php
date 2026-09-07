<?php

namespace App\Repository;

use App\Entity\DepartementActualite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepartementActualite>
 */
class DepartementActualiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepartementActualite::class);
    }

    public function findByDepartementAndPublic($departement, string $public): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.departement = :dept')
            ->andWhere('a.public LIKE :public')
            ->setParameter('dept', $departement)
            ->setParameter('public', '%"'.$public.'"%');

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return DepartementActualite[] Returns an array of DepartementActualite objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DepartementActualite
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
