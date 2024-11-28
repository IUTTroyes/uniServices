<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureDiplome;
use App\Repository\StructureDepartementRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StructureDiplomeFixtures extends Fixture implements OrderedFixtureInterface
{
    private StructureDepartementRepository $departementRepository;

    public function __construct(
        StructureDepartementRepository $departementRepository
    )
    {
        $this->departementRepository = $departementRepository;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 3;
    }

    public function load(ObjectManager $manager): void
    {
        $departement = $this->departementRepository->findOneBy(['libelle' => 'MMI']);

        $diplome1 = new StructureDiplome();
        $diplome1->setLibelle('Métiers du Multimédia et de l\'Internet')
            ->setVolumeHoraire(0)
            ->setCodeCelcatDepartement(123)
            ->setSigle('MMI')
            ->setActif(true)
            ->setLogoPartenaire('logo_partenaire.png')
            ->setDepartement($departement)
        ;
        $manager->persist($diplome1);

        $diplome2 = new StructureDiplome();
        $diplome2->setLibelle('Métiers du Multimédia et de l\'Internet Parcours : Développement web et dispositifs interactifs FC')
            ->setVolumeHoraire(0)
            ->setCodeCelcatDepartement(124)
            ->setSigle('MMI DWeb-Di FC')
            ->setActif(true)
            ->setLogoPartenaire('logo_partenaire.png')
            ->setParent($diplome1)
            ->setDepartement($departement)
        ;
        $manager->persist($diplome2);

        $diplome3 = new StructureDiplome();
        $diplome3->setLibelle('Métiers du Multimédia et de l\'Internet Parcours : Création numérique')
            ->setVolumeHoraire(0)
            ->setCodeCelcatDepartement(125)
            ->setSigle('MMI DWeb-Di')
            ->setActif(true)
            ->setLogoPartenaire('logo_partenaire.png')
            ->setParent($diplome1)
            ->setDepartement($departement)
        ;
        $manager->persist($diplome3);

        $diplome1->addEnfant($diplome2);
        $diplome1->addEnfant($diplome3);
        $manager->persist($diplome1);

        $manager->flush();
    }
}
