<?php

namespace UnifolioBundle\Repository;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Entity\PortfolioModele;

/** @extends ServiceEntityRepository<PortfolioModele> */
class PortfolioModeleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioModele::class);
    }

    public function findOneByUuid(string $uuid): ?PortfolioModele
    {
        return Uuid::isValid($uuid) ? $this->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
    }

    /**
     * Modèles visibles d'un département : nationaux + ceux du département. null = tous (super administrateur).
     *
     * @return PortfolioModele[]
     */
    public function findVisibles(?StructureDepartement $departement): array
    {
        $qb = $this->createQueryBuilder('m')->orderBy('m.libelle', 'ASC');
        if ($departement !== null) {
            $qb->andWhere('m.departement IS NULL OR m.departement = :departement')->setParameter('departement', $departement);
        }

        return $qb->getQuery()->getResult();
    }

    /** @return PortfolioModele[] modèles actifs applicables à une formation */
    public function findApplicables(StructureDiplome $diplome): array
    {
        return array_values(array_filter(
            $this->findBy(['actif' => true], ['libelle' => 'ASC']),
            static fn (PortfolioModele $m) => $m->estApplicableA($diplome)
        ));
    }
}
