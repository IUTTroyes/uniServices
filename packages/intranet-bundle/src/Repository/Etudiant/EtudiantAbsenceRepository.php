<?php

namespace IntranetBundle\Repository\Etudiant;

use App\Entity\Users\Etudiant;
use IntranetBundle\Entity\Etudiant\EtudiantAbsence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtudiantAbsence>
 */
class EtudiantAbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtudiantAbsence::class);
    }

    /**
     * @return EtudiantAbsence[]
     */
    public function findWithoutJustificatifByEtudiant(Etudiant $etudiant): array
    {
        return $this->createQueryBuilder('absence')
            ->join('absence.scolariteSemestre', 'scolariteSemestre')
            ->join('scolariteSemestre.scolarite', 'scolarite')
            ->andWhere('scolarite.etudiant = :etudiant')
            ->andWhere('absence.absenceJustificatif IS NULL')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('absence.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return EtudiantAbsence[]
     */
    public function findWithoutJustificatifByEtudiantAndInterval(Etudiant $etudiant, \DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('absence')
            ->join('absence.scolariteSemestre', 'scolariteSemestre')
            ->join('scolariteSemestre.scolarite', 'scolarite')
            ->join('absence.event', 'event')
            ->andWhere('scolarite.etudiant = :etudiant')
            ->andWhere('absence.absenceJustificatif IS NULL')
            ->andWhere('event.debut <= :fin')
            ->andWhere('event.fin >= :debut')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('absence.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return EtudiantAbsence[] Returns an array of EtudiantAbsence objects
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

    //    public function findOneBySomeField($value): ?EtudiantAbsence
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
