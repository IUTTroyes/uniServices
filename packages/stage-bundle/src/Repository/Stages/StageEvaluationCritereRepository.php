<?php

namespace StageBundle\Repository\Stages;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use StageBundle\Entity\Stages\StageEvaluationCritere;

/**
 * @extends ServiceEntityRepository<StageEvaluationCritere>
 */
class StageEvaluationCritereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StageEvaluationCritere::class);
    }
}
