<?php

namespace StageBundle\Repository\Stages;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use StageBundle\Entity\Stages\StageAvenant;

/**
 * @extends ServiceEntityRepository<StageAvenant>
 */
class StageAvenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StageAvenant::class);
    }
}
