<?php

namespace App\Repository\Structure;

use App\Entity\Structure\StructureDepartement;
use App\Repository\Traits\FindAllByIdArrayTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StructureDepartement>
 */
class StructureDepartementRepository extends ServiceEntityRepository
{
    use FindAllByIdArrayTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StructureDepartement::class);
    }

    public function findOneByEtudiant($etudiant)
    {
        // récupérer le département de etudiant.etudiantScolarite quand etudiantScolarite.actif = true
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.scolarites', 'es')
            ->where('es.etudiant = :etudiant')
            ->andWhere('es.actif = true')
            ->setParameter('etudiant', $etudiant)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
