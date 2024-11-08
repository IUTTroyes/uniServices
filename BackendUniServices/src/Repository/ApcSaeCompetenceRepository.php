<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Repository/ApcSaeCompetenceRepository.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 23/02/2024 18:43
 */

namespace App\Repository;

use App\Entity\ApcCompetence;
use App\Entity\ApcSae;
use App\Entity\ApcSaeCompetence;
use App\Entity\Semestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApcSaeCompetence|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApcSaeCompetence|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApcSaeCompetence[]    findAll()
 * @method ApcSaeCompetence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<ApcSaeCompetence>
 */
class ApcSaeCompetenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApcSaeCompetence::class);
    }

    public function findfBySemestre(Semestre $semestre): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin(ApcSae::class, 'r', 'WITH', 'a.sae = r.id')
            ->innerJoin(ApcCompetence::class, 'c', 'WITH', 'a.competence = c.id')
            ->innerJoin('r.semestres', 's')
            ->where('s.id = :semestre')
            ->andWhere('c.apcReferentiel = :referentiel')
            ->setParameter('semestre', $semestre)
            ->setParameter('referentiel', $semestre->getDiplome()->getReferentiel())
            ->getQuery()
            ->getResult();
    }

    public function findBySemestreArray(Semestre $semestre): array
    {
        $datas = $this->findfBySemestre($semestre);
        $array = [];
        foreach ($datas as $data) {
            $array[$data->getCompetence()->getId()][$data->getSae()->getCodeElement()] = $data;
        }

        return $array;
    }

    public function getBySae(mixed $matiere)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin(ApcCompetence::class, 'c', 'WITH', 'a.competence = c.id')
            ->join('c.ue', 'u')
            ->addSelect('c')
            ->addSelect('u')
            ->where('a.sae = :sae')
            ->setParameter('sae', $matiere)
            ->getQuery()
            ->getResult();
    }
}
