<?php

namespace IntranetBundle\Repository\Etudiant;

use App\Entity\Structure\StructureSemestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use IntranetBundle\Entity\Etudiant\MoyenneUe;

/**
 * @extends ServiceEntityRepository<MoyenneUe>
 */
class MoyenneUeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MoyenneUe::class);
    }

    /**
     * @return array<int, array<int, float>> [scolariteSemestreId][ueId] => moyenne
     */
    public function findIndexedBySemestre(StructureSemestre $semestre): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.etudiantScolariteSemestre) AS scolId', 'IDENTITY(m.ue) AS ueId', 'm.value AS value')
            ->join('m.etudiantScolariteSemestre', 'ess')
            ->where('ess.semestre = :semestre')
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['scolId']][(int) $row['ueId']] = (float) $row['value'];
        }

        return $result;
    }

    //    /**
    //     * @return MoyenneUe[] Returns an array of MoyenneUe objects
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

    //    public function findOneBySomeField($value): ?MoyenneUe
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
