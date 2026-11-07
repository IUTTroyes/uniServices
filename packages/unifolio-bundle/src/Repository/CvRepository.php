<?php

namespace UnifolioBundle\Repository;

use App\Entity\Users\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use UnifolioBundle\Entity\Cv;

/** @extends ServiceEntityRepository<Cv> */
class CvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cv::class);
    }

    public function findOneByEtudiant(Etudiant $etudiant): ?Cv
    {
        return $this->findOneBy(['etudiant' => $etudiant]);
    }
}
