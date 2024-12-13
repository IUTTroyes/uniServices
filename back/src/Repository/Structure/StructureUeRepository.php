<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureUe;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureUe>
 */
class StructureUeRepository extends ServiceEntityRepository
{
    use findAllByIdArrayTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureUe::class);
    }
}
