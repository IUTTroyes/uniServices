<?php

namespace App\Repository\Edt;

use App\Entity\Edt\EdtEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EdtEvent>
 */
class EdtEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EdtEvent::class);
    }
}
