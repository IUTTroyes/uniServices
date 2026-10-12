<?php

namespace FinanceBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FinanceBundle\Entity\FinanceBonCommande;

/**
 * @extends ServiceEntityRepository<FinanceBonCommande>
 */
class FinanceBonCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinanceBonCommande::class);
    }
}
