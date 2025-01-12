<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Repository\PersonnelRepository;
use App\Repository\Structure\StructurePnRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StructureAnneeUniversitaireFixtures extends Fixture implements OrderedFixtureInterface
{
    private PersonnelRepository $personnelRepository;
    private StructurePnRepository $pnRepository;

    public function __construct(
        PersonnelRepository          $personnelRepository,
        StructurePnRepository        $pnRepository
    )
    {
        $this->personnelRepository = $personnelRepository;
        $this->pnRepository = $pnRepository;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 7;
    }

    public function load(ObjectManager $manager): void
    {
        $personnel = $this->personnelRepository->findOneBy(['username' => 'personnel']);

        $pn1 = $this->pnRepository->findOneBy(['libelle' => 'PN BUT MMI ']);
        $pn2 = $this->pnRepository->findOneBy(['libelle' => 'PN BUT MMI DWEB']);

        $anneeUniversitaire1 = new StructureAnneeUniversitaire();
        $anneeUniversitaire1->setLibelle('2023/2024')
            ->setAnnee(2023)
            ->addPn($pn1)
            ->addPn($pn2)
            ->addPersonnel($personnel)
            ->setActif(false)
            ;
        $manager->persist($anneeUniversitaire1);

        $anneeUniversitaire2 = new StructureAnneeUniversitaire();
        $anneeUniversitaire2->setLibelle('2024/2025')
            ->setAnnee(2024)
            ->addPn($pn1)
            ->addPn($pn2)
            ->addPersonnel($personnel)
            ->setActif(true)
            ;
        $manager->persist($anneeUniversitaire2);

        $manager->flush();
    }
}
