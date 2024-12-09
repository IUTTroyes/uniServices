<?php

namespace App\Repository;

use App\Entity\Scolarite\ScolEnseignement;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScolEnseignement>
 */
class ScolEnseignementRepository extends ServiceEntityRepository
{
    use FindAllByIdArrayTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScolEnseignement::class);
    }
}
