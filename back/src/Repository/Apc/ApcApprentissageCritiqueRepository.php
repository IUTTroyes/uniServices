<?php

namespace App\Repository\Apc;

use App\Entity\Apc\ApcApprentissageCritique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApcApprentissageCritique>
 */
class ApcApprentissageCritiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApcApprentissageCritique::class);
    }
}
