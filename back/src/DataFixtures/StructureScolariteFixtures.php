<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureScolarite;
use App\Repository\StructureAnneeUniversitaireRepository;
use App\Repository\EtudiantRepository;
use App\Repository\StructureSemestreRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StructureScolariteFixtures extends Fixture implements OrderedFixtureInterface
{
    private StructureSemestreRepository $semestreRepository;
    private EtudiantRepository $etudiantRepository;

    private StructureAnneeUniversitaireRepository $anneeUniversitaireRepository;

    public function __construct(
        StructureSemestreRepository           $semestreRepository,
        EtudiantRepository                    $etudiantRepository,
        StructureAnneeUniversitaireRepository $anneeUniversitaireRepository
    )
    {
        $this->semestreRepository = $semestreRepository;
        $this->etudiantRepository = $etudiantRepository;
        $this->anneeUniversitaireRepository = $anneeUniversitaireRepository;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 8;
    }

    public function load(ObjectManager $manager): void
    {
        $semestre1 = $this->semestreRepository->findOneBy(['libelle' => 'S1']);
        $semestre2 = $this->semestreRepository->findOneBy(['libelle' => 'S2']);
        $semestre3 = $this->semestreRepository->findOneBy(['libelle' => 'S3 - DWeb-DI FC']);
        $semestre4 = $this->semestreRepository->findOneBy(['libelle' => 'S4 - DWeb-DI FC']);

        $etu1 = $this->etudiantRepository->findOneBy(['username' => 'hero0005']);

        $anneeUniversitaire1 = $this->anneeUniversitaireRepository->findOneBy(['libelle' => '2023/2024']);
        $anneeUniversitaire2 = $this->anneeUniversitaireRepository->findOneBy(['libelle' => '2024/2025']);

        $scolarite1 = new StructureScolarite();
        $scolarite1->setSemestre($semestre1)
            ->setEtudiant($etu1)
            ->setOrdre(1)
            ->setNbAbsences(0)
            ->setPublic(true)
            ->setActif(false)
            ->setStructureAnneeUniversitaire($anneeUniversitaire1)
            ;
        $manager->persist($scolarite1);

        $scolarite2 = new StructureScolarite();
        $scolarite2->setSemestre($semestre2)
            ->setEtudiant($etu1)
            ->setOrdre(2)
            ->setNbAbsences(0)
            ->setPublic(true)
            ->setActif(true)
            ->setStructureAnneeUniversitaire($anneeUniversitaire2)
            ;
        $manager->persist($scolarite2);

        $manager->flush();
    }
}
