<?php

namespace UnifolioBundle\Repository;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Entity\Campagne;

/** @extends ServiceEntityRepository<Campagne> */
class CampagneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campagne::class);
    }

    public function findOneByUuid(string $uuid): ?Campagne
    {
        return Uuid::isValid($uuid) ? $this->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
    }

    /**
     * Campagnes des formations d'un département (null = toutes), les plus récentes d'abord.
     *
     * @return Campagne[]
     */
    public function findPourDepartement(?StructureDepartement $departement): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.diplome', 'd')->addSelect('d')
            ->join('c.anneeUniversitaire', 'a')->addSelect('a')
            ->orderBy('a.annee', 'DESC')
            ->addOrderBy('c.ouvertureLe', 'DESC');
        if ($departement !== null) {
            $qb->andWhere('d.departement = :departement')->setParameter('departement', $departement);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Campagnes d'une formation pour une année universitaire (toutes années de BUT confondues).
     *
     * @return Campagne[]
     */
    public function findPour(StructureDiplome $diplome, StructureAnneeUniversitaire $annee): array
    {
        return $this->findBy(['diplome' => $diplome, 'anneeUniversitaire' => $annee]);
    }

    /**
     * Campagnes ouvertes à un instant donné (ouvertes et non clôturées).
     *
     * @return Campagne[]
     */
    public function findOuvertes(?\DateTimeImmutable $maintenant = null): array
    {
        $maintenant ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('c')
            ->andWhere('c.ouvertureLe <= :maintenant')
            ->andWhere('c.clotureLe IS NULL OR c.clotureLe > :maintenant')
            ->setParameter('maintenant', $maintenant)
            ->getQuery()
            ->getResult();
    }
}
