<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureGroupe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StructureGroupeFixtures extends Fixture implements OrderedFixtureInterface
{
    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 9;
    }

    public function load(ObjectManager $manager): void
    {

        $groupe1 = new StructureGroupe();
        $groupe1->setLibelle('CM')
            ->setCodeApogee('MMICM')
            ->setType('CM')
            ->setOrdre(1)
            ;

        $groupe2 = new StructureGroupe();
        $groupe2->setLibelle('AB')
            ->setCodeApogee('MMITDAB')
            ->setType('TD')
            ->setOrdre(2)
            ->setParent($groupe1)
            ;

        $groupe3 = new StructureGroupe();
        $groupe3->setLibelle('CD')
            ->setCodeApogee('MMITDCD')
            ->setType('TD')
            ->setOrdre(3)
            ->setParent($groupe1)
            ;

        $groupe4 = new StructureGroupe();
        $groupe4->setLibelle('A')
            ->setCodeApogee('MMITPA')
            ->setType('TP')
            ->setOrdre(4)
            ->setParent($groupe2)
            ;

        $groupe5 = new StructureGroupe();
        $groupe5->setLibelle('B')
            ->setCodeApogee('MMITPB')
            ->setType('TP')
            ->setOrdre(5)
            ->setParent($groupe2)
            ;

        $groupe6 = new StructureGroupe();
        $groupe6->setLibelle('C')
            ->setCodeApogee('MMITPC')
            ->setType('TP')
            ->setOrdre(6)
            ->setParent($groupe2)
            ;

        $groupe7 = new StructureGroupe();
        $groupe7->setLibelle('D')
            ->setCodeApogee('MMITPD')
            ->setType('TP')
            ->setOrdre(7)
            ->setParent($groupe2)
            ;

        $groupe1->addEnfant($groupe2);
        $groupe1->addEnfant($groupe3);

        $groupe2->addEnfant($groupe4);
        $groupe2->addEnfant($groupe5);

        $groupe3->addEnfant($groupe6);
        $groupe3->addEnfant($groupe7);

        $manager->persist($groupe1);
        $manager->persist($groupe2);
        $manager->persist($groupe3);
        $manager->persist($groupe4);
        $manager->persist($groupe5);
        $manager->persist($groupe6);
        $manager->persist($groupe7);

        $manager->flush();
    }
}
