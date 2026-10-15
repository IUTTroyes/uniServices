<?php

namespace IntranetBundle\Commission\Service;

use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureSemestre;
use App\Repository\EtudiantScolariteSemestreRepository;
use IntranetBundle\Commission\Contract\CommissionDataLoaderInterface;
use IntranetBundle\Commission\Dto\CommissionDataset;
use IntranetBundle\Repository\Etudiant\MoyenneEnseignementRepository;
use IntranetBundle\Repository\Etudiant\MoyenneUeRepository;

final readonly class CommissionDataLoader implements CommissionDataLoaderInterface
{
    public function __construct(
        private EtudiantScolariteSemestreRepository $scolariteSemestreRepository,
        private MoyenneUeRepository $moyenneUeRepository,
        private MoyenneEnseignementRepository $moyenneEnseignementRepository,
    ) {
    }

    public function loadDataset(StructureSemestre $semestre): CommissionDataset
    {
        /** @var EtudiantScolariteSemestre[] $scolarites */
        $scolarites = $this->scolariteSemestreRepository->createQueryBuilder('ess')
            ->leftJoin('ess.scolarite', 'scol')
            ->leftJoin('scol.etudiant', 'e')
            ->leftJoin('e.bac', 'b')
            ->addSelect('scol', 'e', 'b')
            ->where('ess.semestre = :semestre')
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getResult();

        $moyennesUe = $this->moyenneUeRepository->findIndexedBySemestre($semestre);
        $moyennesEnseignement = $this->moyenneEnseignementRepository->findIndexedBySemestre($semestre);

        return new CommissionDataset(
            $semestre,
            $scolarites,
            $moyennesUe,
            $moyennesEnseignement,
        );
    }
}
