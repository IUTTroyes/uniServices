<?php

namespace App\Migration\IntranetV3\Structure;

use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureSemestre;
use Doctrine\ORM\EntityManagerInterface;

final class SnapshotResolver
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findPn(StructureDiplome $diplome, StructureAnneeUniversitaire $anneeUniversitaire): ?StructurePn
    {
        return $this->entityManager->getRepository(StructurePn::class)->findOneBy([
            'diplome' => $diplome,
            'anneeUniversitaire' => $anneeUniversitaire,
        ]);
    }

    public function findAnnee(int $oldId, StructurePn $pn): ?StructureAnnee
    {
        return $this->entityManager->getRepository(StructureAnnee::class)->findOneBy([
            'oldId' => $oldId,
            'pn' => $pn,
        ]);
    }

    public function findSemestre(int $oldId, StructurePn $pn): ?StructureSemestre
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(StructureSemestre::class, 's')
            ->innerJoin('s.annee', 'a')
            ->andWhere('s.oldId = :oldId')
            ->andWhere('a.pn = :pn')
            ->setParameter('oldId', $oldId)
            ->setParameter('pn', $pn)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findGroupe(int $oldId, StructurePn $pn): ?StructureGroupe
    {
        return $this->entityManager->createQueryBuilder()
            ->select('g')
            ->from(StructureGroupe::class, 'g')
            ->innerJoin('g.semestres', 's')
            ->innerJoin('s.annee', 'a')
            ->andWhere('g.oldId = :oldId')
            ->andWhere('a.pn = :pn')
            ->setParameter('oldId', $oldId)
            ->setParameter('pn', $pn)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
