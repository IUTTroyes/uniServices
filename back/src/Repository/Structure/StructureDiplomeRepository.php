<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureDiplome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureDiplome>
 */
class StructureDiplomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureDiplome::class);
    }

    // en passant par le lien diplome->pns (collection)->anneeUniversitaire
    public function findByAnneeUniversitaire(int $anneeUniversitaire)
    {
        return $this->createQueryBuilder('d')
            ->join('d.pns', 'p')
            ->join('p.anneeUniversitaire', 'au')
            ->andWhere('au.id = :anneeUniversitaire')
            ->setParameter('anneeUniversitaire', $anneeUniversitaire)
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return StructureDiplome[] Returns an array of StructureDiplome objects
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

//    public function findOneBySomeField($value): ?StructureDiplome
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
