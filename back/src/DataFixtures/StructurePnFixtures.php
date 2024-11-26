<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructurePn;
use App\Repository\StructureDiplomeRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StructurePnFixtures extends Fixture
{
    private StructureDiplomeRepository $diplomeRepository;

    public function __construct(
        StructureDiplomeRepository $diplomeRepository
    )
    {
        $this->diplomeRepository = $diplomeRepository;
    }

    public function getOrder()
    {
        return 1;
    }

    public function load(ObjectManager $manager): void
    {
        $diplome = $this->diplomeRepository->findOneBy(['libelle' => 'MMI']);

        $pn1 = new StructurePn();
        $pn1->setLibelle('PN BUT MMI ')
            ->setAnneePublication('2022')
            ->setDiplome($diplome)
        ;
        $manager->persist($pn1);

        $manager->flush();
    }
}
