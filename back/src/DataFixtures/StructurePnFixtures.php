<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructurePn;
use App\Repository\StructureDiplomeRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StructurePnFixtures extends Fixture implements OrderedFixtureInterface
{
    private StructureDiplomeRepository $diplomeRepository;

    public function __construct(
        StructureDiplomeRepository $diplomeRepository
    )
    {
        $this->diplomeRepository = $diplomeRepository;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 4;
    }

    public function load(ObjectManager $manager): void
    {
        $diplome1 = $this->diplomeRepository->findOneBy(['sigle' => 'MMI']);
        $diplome2 = $this->diplomeRepository->findOneBy(['sigle' => 'MMI DWeb-Di FC']);

        $pn1 = new StructurePn();
        $pn1->setLibelle('PN BUT MMI ')
            ->setAnneePublication(2022)
            ->setDiplome($diplome1)
        ;
        $manager->persist($pn1);

        $pn2 = new StructurePn();
        $pn2->setLibelle('PN BUT MMI DWEB')
            ->setAnneePublication(2024)
            ->setDiplome($diplome2)
        ;
        $manager->persist($pn2);

        $manager->flush();
    }
}
