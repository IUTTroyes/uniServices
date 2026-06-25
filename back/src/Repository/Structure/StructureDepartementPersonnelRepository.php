<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDepartement;
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

    public function findOneForPersonnelAndDepartement(
        Personnel $personnel,
        StructureDepartement $departement,
    ): ?StructureDepartementPersonnel {
        return $this->createQueryBuilder('pd')
            ->andWhere('pd.personnel = :personnel')
            ->andWhere('pd.departement = :departement')
            ->setParameter('personnel', $personnel)
            ->setParameter('departement', $departement)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
