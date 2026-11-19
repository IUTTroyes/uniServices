<?php

namespace IntranetBundle\Repository\Etudiant;

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
}
