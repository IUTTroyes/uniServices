<?php

namespace IntranetBundle\Repository\Etudiant;

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
}
