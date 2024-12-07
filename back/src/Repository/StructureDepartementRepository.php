<?php

namespace App\Repository;

use App\Entity\Structure\StructureDepartement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureDepartement>
 */
class StructureDepartementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureDepartement::class);
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
