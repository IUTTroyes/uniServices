<?php

namespace StageBundle\Repository\Stages;

use StageBundle\Entity\Stages\StagePeriodeInterruption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StagePeriodeInterruption>
 */
class StagePeriodeInterruptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StagePeriodeInterruption::class);
    }
}
