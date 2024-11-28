<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureSemestre;
use App\Repository\StructureAnneeRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StructureSemestreFixtures extends Fixture implements OrderedFixtureInterface
{
    private StructureAnneeRepository $anneeRepository;

    public function __construct(
        StructureAnneeRepository $anneeRepository
    )
    {
        $this->anneeRepository = $anneeRepository;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 6;
    }

    public function load(ObjectManager $manager): void
    {
        $annee1 = $this->anneeRepository->findOneBy(['libelle' => 'MMI 1']);
        $annee2 = $this->anneeRepository->findOneBy(['libelle' => 'MMI - DWeb-DI - 2']);

        $semestre1 = new StructureSemestre();
        $semestre1->setLibelle('S1')
            ->setOrdreAnnee(1)
            ->setOrdreLmd(1)
            ->setActif(true)
            ->setNbGroupesCm(1)
            ->setNbGroupesTd(3)
            ->setNbGroupesTp(6)
            ->setCodeElement('MMI123')
            ->setAnnee($annee1)
            ;
        $manager->persist($semestre1);

        $semestre2 = new StructureSemestre();
        $semestre2->setLibelle('S2')
            ->setOrdreAnnee(2)
            ->setOrdreLmd(2)
            ->setActif(false)
            ->setNbGroupesCm(1)
            ->setNbGroupesTd(3)
            ->setNbGroupesTp(6)
            ->setCodeElement('MMI123')
            ->setAnnee($annee1)
            ;
        $manager->persist($semestre2);

        $semestre3 = new StructureSemestre();
        $semestre3->setLibelle('S3 - DWeb-DI FC')
            ->setOrdreAnnee(1)
            ->setOrdreLmd(1)
            ->setActif(true)
            ->setNbGroupesCm(1)
            ->setNbGroupesTd(1)
            ->setNbGroupesTp(2)
            ->setCodeElement('MMI124')
            ->setAnnee($annee2)
            ;
        $manager->persist($semestre3);

        $semestre4 = new StructureSemestre();
        $semestre4->setLibelle('S4 - DWeb-DI FC')
            ->setOrdreAnnee(2)
            ->setOrdreLmd(2)
            ->setActif(false)
            ->setNbGroupesCm(1)
            ->setNbGroupesTd(1)
            ->setNbGroupesTp(2)
            ->setCodeElement('MMI124')
            ->setAnnee($annee2)
            ;
        $manager->persist($semestre4);

        $manager->flush();
    }
}
