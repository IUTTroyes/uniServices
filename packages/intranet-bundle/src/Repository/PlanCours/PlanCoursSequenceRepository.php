<?php

namespace IntranetBundle\Repository\PlanCours;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use IntranetBundle\Entity\PlanCours\PlanCoursSequence;

/**
 * @extends ServiceEntityRepository<PlanCoursSequence>
 */
class PlanCoursSequenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanCoursSequence::class);
    }
}
