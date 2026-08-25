<?php

namespace StageBundle\Repository\Stages;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use StageBundle\Entity\Stages\StageSoutenance;

/**
 * @extends ServiceEntityRepository<StageSoutenance>
 */
class StageSoutenanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StageSoutenance::class);
    }
}
