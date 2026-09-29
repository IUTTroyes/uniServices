<?php

namespace FinanceBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FinanceBundle\Entity\FinanceFournisseur;

/**
 * @extends ServiceEntityRepository<FinanceFournisseur>
 */
class FinanceFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinanceFournisseur::class);
    }
}
