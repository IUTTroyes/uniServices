<?php

namespace UnifolioBundle\Repository;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use UnifolioBundle\Entity\ParametrageFormation;

/** @extends ServiceEntityRepository<ParametrageFormation> */
class ParametrageFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParametrageFormation::class);
    }

    /** Paramétrage en vigueur pour une année : le plus récent dont l'année d'application est antérieure ou égale. */
    public function findApplicable(StructureDiplome $diplome, StructureAnneeUniversitaire $annee): ?ParametrageFormation
    {
        return $this->createQueryBuilder('p')
            ->join('p.applicableDepuis', 'a')
            ->andWhere('p.diplome = :diplome')->setParameter('diplome', $diplome)
            ->andWhere('a.annee <= :annee')->setParameter('annee', $annee->getAnnee())
            ->orderBy('a.annee', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return ParametrageFormation[] historique, du plus récent au plus ancien */
    public function findHistorique(StructureDiplome $diplome): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.applicableDepuis', 'a')->addSelect('a')
            ->andWhere('p.diplome = :diplome')->setParameter('diplome', $diplome)
            ->orderBy('a.annee', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
