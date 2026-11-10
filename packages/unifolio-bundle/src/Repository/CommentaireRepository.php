<?php

namespace UnifolioBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Entity\Commentaire;
use UnifolioBundle\Entity\Trace;

/** @extends ServiceEntityRepository<Commentaire> */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    public function findOneByUuid(string $uuid): ?Commentaire
    {
        return Uuid::isValid($uuid) ? $this->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
    }

    /** @return Commentaire[] du plus ancien au plus récent */
    public function findParTrace(Trace $trace): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.trace = :trace')->setParameter('trace', $trace)
            ->orderBy('c.createdAt', 'ASC')->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
