<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureAnnee;
use App\Repository\StructurePnRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StructureAnneeFixtures extends Fixture
{
    private StructurePnRepository $pnRepository;

    public function __construct(
        StructurePnRepository $pnRepository
    )
    {
        $this->pnRepository = $pnRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $pn = $this->pnRepository->findOneBy(['libelle' => 'PN BUT MMI']);

        $annee1 = new StructureAnnee();
        $annee1->setLibelle('MMI 1')
            ->setCodeEtape('MMI123')
            ->setCodeVersion('1')
            ->setOrdre(1)
            ->setLibelleLong('Première année de BUT MMI')
            ->setActif(true)
            ->setCouleur('red')
            ->addPn($pn)
            ->setOpt(['alternance' => '1'])
            ;

        $manager->flush();
    }
}
