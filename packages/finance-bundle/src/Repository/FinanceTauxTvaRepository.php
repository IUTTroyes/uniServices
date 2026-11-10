<?php

namespace FinanceBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FinanceBundle\Entity\FinanceTauxTva;

/**
 * @extends ServiceEntityRepository<FinanceTauxTva>
 */
class FinanceTauxTvaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinanceTauxTva::class);
    }

    /**
     * Crée les taux par défaut absents (sans doublon, sans flush).
     *
     * @return int nombre de taux créés
     */
    public function initialiser(): int
    {
        $existants = array_map(static fn (FinanceTauxTva $t): string => (string) $t->getTaux(), $this->findAll());
        $crees = 0;
        foreach (FinanceTauxTva::TAUX_PAR_DEFAUT as $ordre => $defaut) {
            if (\in_array($defaut['taux'], $existants, true)) {
                continue;
            }
            $this->getEntityManager()->persist((new FinanceTauxTva())
                ->setTaux($defaut['taux'])->setLibelle($defaut['libelle'])
                ->setParDefaut($defaut['parDefaut'] && $existants === [])->setOrdre($ordre));
            ++$crees;
        }

        return $crees;
    }
}
