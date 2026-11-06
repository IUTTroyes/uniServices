<?php

namespace UnifolioBundle\Repository;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Enum\GranulariteEnum;

/** @extends ServiceEntityRepository<Grille> */
class GrilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grille::class);
    }

    public function findOneByUuid(string $uuid): ?Grille
    {
        return Uuid::isValid($uuid) ? $this->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
    }

    /**
     * Grilles visibles d'un département : nationales (type de diplôme) + celles du département.
     * null = toutes (super administrateur).
     *
     * @return Grille[]
     */
    public function findVisibles(?StructureDepartement $departement): array
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.versions', 'v')->addSelect('v')
            ->orderBy('g.libelle', 'ASC');
        if ($departement !== null) {
            $qb->andWhere('g.departement IS NULL OR g.departement = :departement')->setParameter('departement', $departement);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Grilles actives applicables à un diplôme, des plus spécifiques aux plus générales.
     *
     * @return Grille[]
     */
    public function findApplicables(StructureDiplome $diplome, ?GranulariteEnum $granularite = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.versions', 'v')->addSelect('v')
            ->andWhere('g.actif = true')
            ->andWhere('g.typeDiplome = :type')->setParameter('type', $diplome->getTypeDiplome())
            ->andWhere('g.departement IS NULL OR g.departement = :departement')->setParameter('departement', $diplome->getDepartement())
            ->andWhere('g.diplome IS NULL OR g.diplome = :diplome')->setParameter('diplome', $diplome);
        if ($granularite !== null) {
            $qb->andWhere('g.granularite = :granularite')->setParameter('granularite', $granularite);
        }

        $grilles = $qb->getQuery()->getResult();
        usort($grilles, static fn (Grille $a, Grille $b) => [$b->getSpecificite(), $a->getLibelle()] <=> [$a->getSpecificite(), $b->getLibelle()]);

        return $grilles;
    }
}
