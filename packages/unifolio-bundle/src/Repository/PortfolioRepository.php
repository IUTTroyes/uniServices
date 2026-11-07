<?php

namespace UnifolioBundle\Repository;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Enum\TypePortfolioEnum;

/** @extends ServiceEntityRepository<Portfolio> */
class PortfolioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    public function findOneByUuid(string $uuid): ?Portfolio
    {
        return Uuid::isValid($uuid) ? $this->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
    }

    /** @return Portfolio[] les portfolios d'un étudiant, académiques d'abord */
    public function findParEtudiant(Etudiant $etudiant): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.pages', 'pg')->addSelect('pg')
            ->andWhere('p.etudiant = :etudiant')->setParameter('etudiant', $etudiant)
            ->orderBy('p.type', 'ASC')->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAcademique(Etudiant $etudiant, EtudiantScolarite $scolarite): ?Portfolio
    {
        return $this->findOneBy(['etudiant' => $etudiant, 'scolarite' => $scolarite, 'type' => TypePortfolioEnum::ACADEMIQUE]);
    }
}
