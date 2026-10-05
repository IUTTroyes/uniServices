<?php

namespace StageBundle\Repository\Stages;

use StageBundle\Entity\Stages\StageEntreprise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StageEntreprise>
 */
class StageEntrepriseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StageEntreprise::class);
    }
}
