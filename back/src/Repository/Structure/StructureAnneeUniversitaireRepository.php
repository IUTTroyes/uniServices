<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureAnneeUniversitaire>
 */
class StructureAnneeUniversitaireRepository extends ServiceEntityRepository
{
    use FindAllByIdArrayTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureAnneeUniversitaire::class);
    }

    public function setAllAnneeUnivInactifExcept(StructureAnneeUniversitaire $anneeUniversitaire): void
    {
        $qb = $this->createQueryBuilder('a')
            ->update()
            ->set('a.actif', ':inactif')
            ->where('a.id != :id')
            ->setParameter('inactif', false)
            ->setParameter('id', $anneeUniversitaire->getId());

        $qb->getQuery()->execute();
    }
}
