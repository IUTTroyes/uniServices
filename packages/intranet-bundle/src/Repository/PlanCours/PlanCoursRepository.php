<?php

namespace IntranetBundle\Repository\PlanCours;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use IntranetBundle\Entity\PlanCours\PlanCours;

/**
 * @extends ServiceEntityRepository<PlanCours>
 */
class PlanCoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanCours::class);
    }
}
