<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureAnnee;
use App\Repository\PersonnelRepository;
use App\Repository\Structure\StructurePnRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StructureAnneeFixtures extends Fixture implements OrderedFixtureInterface
{
    private StructurePnRepository $pnRepository;

    private PersonnelRepository $personnelRepository;

    public function __construct(
        StructurePnRepository $pnRepository,
        PersonnelRepository $personnelRepository
    )
    {
        $this->pnRepository = $pnRepository;
        $this->personnelRepository = $personnelRepository;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 5;
    }

    public function load(ObjectManager $manager): void
    {
        $pn1 = $this->pnRepository->findOneBy(['libelle' => 'PN BUT MMI']);
        $pn2 = $this->pnRepository->findOneBy(['libelle' => 'PN BUT MMI DWEB']);

        $personnel = $this->personnelRepository->findOneBy(['username' => 'hero0010']);

        $annee1 = new StructureAnnee();
        $annee1->setLibelle('MMI 1')
            ->setApogeeCodeEtape('MMI123')
            ->setApogeeCodeVersion('111')
            ->setOrdre(1)
            ->setLibelleLong('Première année de BUT MMI')
            ->setActif(true)
            ->setCouleur('red')
            ->setDiplome($pn1->getDiplome())
        ;
        $manager->persist($annee1);

        $annee2 = new StructureAnnee();
        $annee2->setLibelle('MMI - DWeb-DI - 2 FC')
            ->setApogeeCodeEtape('MMI124')
            ->setApogeeCodeVersion('121')
            ->setOrdre(2)
            ->setLibelleLong('Deuxiéme année de B.U.T. MMI - parcours Développement web et dispositifs interactifs')
            ->setActif(true)
            ->setCouleur('purple')
            ->setDiplome($pn2->getDiplome())
            ;
        $manager->persist($annee2);

        $manager->flush();
    }
}
