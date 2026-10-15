<?php

namespace IntranetBundle\Repository\Etudiant;

use App\Entity\Structure\StructureSemestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use IntranetBundle\Entity\Etudiant\MoyenneEnseignement;

/**
 * @extends ServiceEntityRepository<MoyenneEnseignement>
 */
class MoyenneEnseignementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MoyenneEnseignement::class);
    }

    /**
     * @return array<int, array<int, float>> [scolariteSemestreId][enseignementId] => moyenne
     */
    public function findIndexedBySemestre(StructureSemestre $semestre): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.etudiantScolariteSemestre) AS scolId', 'IDENTITY(m.enseignement) AS ensId', 'm.value AS value')
            ->join('m.etudiantScolariteSemestre', 'ess')
            ->where('ess.semestre = :semestre')
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['scolId']][(int) $row['ensId']] = (float) $row['value'];
        }

        return $result;
    }

    //    /**
    //     * @return MoyenneEnseignement[] Returns an array of MoyenneEnseignement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MoyenneEnseignement
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
