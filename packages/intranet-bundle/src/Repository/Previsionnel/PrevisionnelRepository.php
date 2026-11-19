<?php

namespace IntranetBundle\Repository\Previsionnel;

use App\Entity\Structure\StructureSemestre;
use IntranetBundle\Entity\Previsionnel\Previsionnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Previsionnel>
 */
class PrevisionnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Previsionnel::class);
    }
    /** @return list<Previsionnel> */
    public function findForSemester(StructureSemestre $semestre): array
    {
        return $this->createQueryBuilder('previ')
            ->select('DISTINCT previ', 'enseignement', 'personnel')
            ->join('previ.enseignement', 'enseignement')
            ->leftJoin('previ.personnel', 'personnel')
            ->join('enseignement.enseignementUes', 'lien')
            ->join('lien.ue', 'ue')
            ->where('ue.semestre = :semestre')
            ->andWhere('previ.anneeUniversitaire = :annee')
            ->setParameter('semestre', $semestre)
            ->setParameter('annee', $semestre->getAnnee()?->getPn()?->getAnneeUniversitaire())
            ->getQuery()->getResult();
    }
}
