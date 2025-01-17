<?php

namespace App\Repository\Previsionnel;

use App\Entity\Previsionnel\Previsionnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Previsionnel>
 */
class PrevisionnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Previsionnel::class);
    }
}
