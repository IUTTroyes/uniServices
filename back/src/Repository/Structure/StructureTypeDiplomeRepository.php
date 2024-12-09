<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureTypeDiplome;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureTypeDiplome>
 */
class StructureTypeDiplomeRepository extends ServiceEntityRepository
{
    use FindAllByIdArrayTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureTypeDiplome::class);
    }
}
