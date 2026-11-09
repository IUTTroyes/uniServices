<?php

namespace UnifolioBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPublication;
use UnifolioBundle\Enum\StatutRenduEnum;

/** @extends ServiceEntityRepository<PortfolioPublication> */
class PortfolioPublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioPublication::class);
    }

    public function findOneByUuid(string $uuid): ?PortfolioPublication
    {
        return Uuid::isValid($uuid) ? $this->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
    }

    public function findActive(Portfolio $portfolio): ?PortfolioPublication
    {
        return $this->findOneBy(['portfolio' => $portfolio, 'active' => true]);
    }

    /** Dernière publication générée (active ou non) : sert à l'aperçu et aux exports du propriétaire. */
    public function findDerniereGeneree(Portfolio $portfolio): ?PortfolioPublication
    {
        return $this->findOneBy(['portfolio' => $portfolio, 'statut' => StatutRenduEnum::GENERE], ['numero' => 'DESC']);
    }

    public function prochainNumero(Portfolio $portfolio): int
    {
        $max = $this->createQueryBuilder('p')->select('MAX(p.numero)')
            ->andWhere('p.portfolio = :portfolio')->setParameter('portfolio', $portfolio)
            ->getQuery()->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    /** @return list<PortfolioPublication> du plus récent au plus ancien */
    public function historique(Portfolio $portfolio): array
    {
        return $this->findBy(['portfolio' => $portfolio], ['numero' => 'DESC']);
    }

    /** @return list<PortfolioPublication> publications actives de portfolios publics et indexables (sitemap) */
    public function findPourSitemap(): array
    {
        return $this->createQueryBuilder('pub')
            ->innerJoin('pub.portfolio', 'p')->addSelect('p')
            ->andWhere('pub.active = true')->andWhere('pub.motifModeration IS NULL')
            ->andWhere("p.visibilite = 'public'")->andWhere('p.indexable = true')->andWhere('p.slug IS NOT NULL')
            ->orderBy('p.slug', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return list<PortfolioPublication> publications actives à modérer (ou déjà modérées) */
    public function findActivesPourModeration(): array
    {
        return $this->createQueryBuilder('pub')
            ->innerJoin('pub.portfolio', 'p')->addSelect('p')
            ->andWhere('pub.active = true')
            ->andWhere("p.visibilite <> 'prive'")
            ->orderBy('pub.updatedAt', 'DESC')
            ->getQuery()->getResult();
    }
}
