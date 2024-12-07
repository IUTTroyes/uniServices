<?php

namespace App\Repository\Type;

use App\Entity\Structure\StructureTypeDiplome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureTypeDiplome>
 */
class TypeDiplomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureTypeDiplome::class);
    }

    public function findAllByIdArray(): array
    {
        $datas = $this->findAll();
        $result = [];

        foreach ($datas as $data) {
            $result[$data->getId()] = $data;
        }

        return $result;
    }
}
