<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Repository\StructurePersonnelRepository;
use App\Repository\StructurePnRepository;
use App\Repository\StructureScolariteRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StructureAnneeUniversitaireFixtures extends Fixture
{
    private StructureScolariteRepository $scolariteRepository;
    private StructurePersonnelRepository $personnelRepository;
    private StructurePnRepository $pnRepository;

    public function __construct(
        StructureScolariteRepository $scolariteRepository,
        StructurePersonnelRepository $personnelRepository,
        StructurePnRepository $pnRepository
    )
    {
        $this->scolariteRepository = $scolariteRepository;
        $this->personnelRepository = $personnelRepository;
        $this->pnRepository = $pnRepository;
    }

    public function getOrder()
    {
        return 7;
    }

    public function load(ObjectManager $manager): void
    {
        $personnel = $this->personnelRepository->findOneBy(['username' => 'hero0010']);
        $scolarite1 = $this->scolariteRepository->findOneBy(['ordre' => 1]);
        $scolarite2 = $this->scolariteRepository->findOneBy(['ordre' => 2]);

        $pn1 = $this->pnRepository->findOneBy(['libelle' => 'PN BUT MMI ']);
        $pn2 = $this->pnRepository->findOneBy(['libelle' => 'PN BUT MMI DWEB']);

        $anneeUniversitaire1 = new StructureAnneeUniversitaire();
        $anneeUniversitaire1->setLibelle('2023/2024')
            ->setAnnee(2023)
            ->addScolarite($scolarite1)
            ->addPn($pn1)
            ->addPn($pn2)
            ->addPersonnel($personnel)
            ->setActif(false)
            ;
        $manager->persist($anneeUniversitaire1);

        $anneeUniversitaire2 = new StructureAnneeUniversitaire();
        $anneeUniversitaire2->setLibelle('2024/2025')
            ->setAnnee(2024)
            ->addScolarite($scolarite2)
            ->addPn($pn1)
            ->addPn($pn2)
            ->addPersonnel($personnel)
            ->setActif(true)
            ;
        $manager->persist($anneeUniversitaire2);

        $manager->flush();
    }
}
