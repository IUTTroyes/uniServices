<?php

namespace IntranetBundle\Repository\PlanCours;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use IntranetBundle\Entity\PlanCours\PlanCoursRealise;

/**
 * @extends ServiceEntityRepository<PlanCoursRealise>
 */
class PlanCoursRealiseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanCoursRealise::class);
    }
}
