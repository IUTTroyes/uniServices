<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructurePn>
 */
class StructurePnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructurePn::class);
    }

    public function findOneByAnneeUniversitaireAndDiplome(
        StructureAnneeUniversitaire $anneeUniversitaires,
        StructureDiplome $diplome
    ): ?StructurePn {

        return $this->createQueryBuilder('s')
            ->andWhere('s.diplome = :diplome')
            ->join('s.anneeUniversitaires', 'sau')
            ->andWhere('sau.id = :anneeUniversitaires')
            ->setParameter('diplome', $diplome)
            ->setParameter('anneeUniversitaires', $anneeUniversitaires)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
