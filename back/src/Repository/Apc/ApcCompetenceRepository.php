<?php

namespace App\Repository\Apc;

use App\Entity\Apc\ApcCompetence;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApcCompetence>
 */
class ApcCompetenceRepository extends ServiceEntityRepository
{
    use FindAllByIdArrayTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApcCompetence::class);
    }
}
