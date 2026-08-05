<?php

namespace App\Repository\Scheduler;

use App\Entity\Scheduler\SchedulerTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SchedulerTask>
 */
class SchedulerTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchedulerTask::class);
    }
}
