<?php

namespace App\DataFixtures;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Repository\EtudiantRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureSemestreRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

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

        $etu1 = $this->etudiantRepository->findOneBy(['username' => 'etudiant']);

        $anneeUniversitaire1 = $this->anneeUniversitaireRepository->findOneBy(['libelle' => '2023/2024']);
        $anneeUniversitaire2 = $this->anneeUniversitaireRepository->findOneBy(['libelle' => '2024/2025']);

        $scolarite1 = new EtudiantScolarite();
        $scolarite1->setSemestre($semestre1)
            ->setEtudiant($etu1)
            ->setOrdre(1)
            ->setNbAbsences(0)
            ->setPublic(true)
            ->setActif(false)
            ->setStructureAnneeUniversitaire($anneeUniversitaire1)
            ->setUuid(Uuid::v4())
            ;
        $manager->persist($scolarite1);

        $scolarite2 = new EtudiantScolarite();
        $scolarite2->setSemestre($semestre2)
            ->setEtudiant($etu1)
            ->setOrdre(2)
            ->setNbAbsences(0)
            ->setPublic(true)
            ->setActif(true)
            ->setStructureAnneeUniversitaire($anneeUniversitaire2)
            ->setUuid(Uuid::v4())
            ;
        $manager->persist($scolarite2);

        $manager->flush();
    }
}
