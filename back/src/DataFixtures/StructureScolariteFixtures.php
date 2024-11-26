<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureScolarite;
use App\Repository\StructureEtudiantRepository;
use App\Repository\StructureSemestreRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StructureScolariteFixtures extends Fixture
{
    private StructureSemestreRepository $semestreRepository;
    private StructureEtudiantRepository $etudiantRepository;

    public function __construct(
        StructureSemestreRepository $semestreRepository,
        StructureEtudiantRepository $etudiantRepository
    )
    {
        $this->semestreRepository = $semestreRepository;
        $this->etudiantRepository = $etudiantRepository;
    }

    public function getOrder()
    {
        return 6;
    }

    public function load(ObjectManager $manager): void
    {
        $semestre1 = $this->semestreRepository->findOneBy(['libelle' => 'S1']);
        $semestre2 = $this->semestreRepository->findOneBy(['libelle' => 'S2']);
        $semestre3 = $this->semestreRepository->findOneBy(['libelle' => 'S3 - DWeb-DI FC']);
        $semestre4 = $this->semestreRepository->findOneBy(['libelle' => 'S4 - DWeb-DI FC']);

        $etu1 = $this->etudiantRepository->findOneBy(['username' => 'hero0005']);

        $scolarite1 = new StructureScolarite();
        $scolarite1->setSemestre($semestre1)
            ->setEtudiant($etu1)
            ->setOrdre(1)
            ->setNbAbsences(0)
            ->setPublic(true)
            ->setActif(false)
            ;
        $manager->persist($scolarite1);

        $scolarite2 = new StructureScolarite();
        $scolarite2->setSemestre($semestre2)
            ->setEtudiant($etu1)
            ->setOrdre(2)
            ->setNbAbsences(0)
            ->setPublic(true)
            ->setActif(true)
            ;
        $manager->persist($scolarite2);

        $manager->flush();
    }
}
