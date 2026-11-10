<?php

namespace UnifolioBundle\Repository;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\CampagneAffectation;

/** @extends ServiceEntityRepository<CampagneAffectation> */
class CampagneAffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampagneAffectation::class);
    }

    public function existe(Campagne $campagne, Personnel $evaluateur, Etudiant $etudiant): bool
    {
        return $this->count(['campagne' => $campagne, 'evaluateur' => $evaluateur, 'etudiant' => $etudiant]) > 0;
    }

    /** L'évaluateur est affecté à cet étudiant dans au moins une campagne. */
    public function estAffecte(Personnel $evaluateur, Etudiant $etudiant): bool
    {
        return $this->count(['evaluateur' => $evaluateur, 'etudiant' => $etudiant]) > 0;
    }

    /** @return CampagneAffectation[] */
    public function findParCampagne(Campagne $campagne): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.evaluateur', 'p')->addSelect('p')
            ->join('a.etudiant', 'e')->addSelect('e')
            ->andWhere('a.campagne = :campagne')->setParameter('campagne', $campagne)
            ->orderBy('e.nom', 'ASC')->addOrderBy('e.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return CampagneAffectation[] */
    public function findParEvaluateur(Personnel $evaluateur, ?Campagne $campagne = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.etudiant', 'e')->addSelect('e')
            ->join('a.campagne', 'c')->addSelect('c')
            ->andWhere('a.evaluateur = :evaluateur')->setParameter('evaluateur', $evaluateur)
            ->orderBy('e.nom', 'ASC')->addOrderBy('e.prenom', 'ASC');
        if ($campagne !== null) {
            $qb->andWhere('a.campagne = :campagne')->setParameter('campagne', $campagne);
        }

        return $qb->getQuery()->getResult();
    }

    /** @return Personnel[] évaluateurs affectés à un étudiant (toutes campagnes) */
    public function evaluateursDe(Etudiant $etudiant): array
    {
        return array_map(
            static fn (CampagneAffectation $a) => $a->getEvaluateur(),
            $this->findBy(['etudiant' => $etudiant])
        );
    }
}
