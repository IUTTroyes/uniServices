<?php

namespace App\Repository;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Etudiant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EtudiantScolarite>
 */
class EtudiantScolariteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtudiantScolarite::class);
    }

    public function save(EtudiantScolarite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return EtudiantScolarite[] Returns an array of EtudiantScolarite objects
     */
    public function findByEtudiant(Etudiant $etudiant): array
    {
        return $this->createQueryBuilder('es')
            ->andWhere('es.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('es.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByEtudiantAndAnneeUniversitaire(
        Etudiant $etudiant,
        StructureAnneeUniversitaire $anneeUniversitaire
    ): ?EtudiantScolarite {
        return $this->createQueryBuilder('es')
            ->andWhere('es.etudiant = :etudiant')
            ->andWhere('es.anneeUniversitaire = :anneeUniversitaire')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('anneeUniversitaire', $anneeUniversitaire)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByEtudiant(Etudiant $etudiant): ?EtudiantScolarite
    {
        return $this->createQueryBuilder('es')
            ->andWhere('es.etudiant = :etudiant')
            ->andWhere('es.actif = true')
            ->setParameter('etudiant', $etudiant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return EtudiantScolarite[] Returns an array of active EtudiantScolarite objects for a given year
     */
    public function findActiveByAnneeUniversitaire(StructureAnneeUniversitaire $anneeUniversitaire): array
    {
        return $this->createQueryBuilder('es')
            ->andWhere('es.anneeUniversitaire = :anneeUniversitaire')
            ->andWhere('es.actif = true')
            ->setParameter('anneeUniversitaire', $anneeUniversitaire)
            ->orderBy('es.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

