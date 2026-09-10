<?php

namespace IntranetBundle\Repository\Etudiant;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use IntranetBundle\Entity\Etudiant\EtudiantAbsenceJustificatif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtudiantAbsenceJustificatif>
 */
class EtudiantAbsenceJustificatifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtudiantAbsenceJustificatif::class);
    }

    /**
     * @return EtudiantAbsenceJustificatif[]
     */
    public function findByScolariteSemestre(EtudiantScolariteSemestre $scolariteSemestre): array
    {
        return $this->createQueryBuilder('justificatif')
            ->andWhere('justificatif.scolariteSemestre = :scolariteSemestre')
            ->setParameter('scolariteSemestre', $scolariteSemestre)
            ->orderBy('justificatif.debut', 'ASC')
            ->addOrderBy('justificatif.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return EtudiantAbsenceJustificatif[] Returns an array of EtudiantAbsenceJustificatif objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?EtudiantAbsenceJustificatif
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
