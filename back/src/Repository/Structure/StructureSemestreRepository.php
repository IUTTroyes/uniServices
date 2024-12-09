<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureSemestre;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureSemestre>
 */
class StructureSemestreRepository extends ServiceEntityRepository
{
    use FindAllByIdArrayTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureSemestre::class);
    }
}
