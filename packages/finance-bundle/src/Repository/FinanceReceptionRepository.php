<?php

namespace FinanceBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FinanceBundle\Entity\FinanceReception;

/**
 * @extends ServiceEntityRepository<FinanceReception>
 */
class FinanceReceptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinanceReception::class);
    }
}
