<?php

namespace StageBundle\Repository\Stages;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use StageBundle\Entity\Stages\StageConventionTemplate;

/**
 * @extends ServiceEntityRepository<StageConventionTemplate>
 */
class StageConventionTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StageConventionTemplate::class);
    }
}
