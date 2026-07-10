<?php

namespace App\DataFixtures;

use App\Entity\Etablissement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EtablissementFixtures extends Fixture implements OrderedFixtureInterface
{
    public function getOrder(): int
    {
        return 1;
    }

    public function load(ObjectManager $manager): void
    {
        $etablissement = new Etablissement();
        $etablissement->setLibelle('IUT de Troyes')
            ->setLogoName('logo_iut_6a1ffc53785581.43873686.png')
            ->setAdresse([
                'pays' => 'France',
                'ville' => 'Rosières-près-Troyes',
                'adresse' => '9 Rue de Québec 10430 Rosières-près-Troyes',
                'codePostal' => '',
                'complement1' => '',
                'complement2' => ''
            ])
            ->setSiteWeb('https://www.univ-reims.fr/iut-troyes')
            ->setIsMain(true);

        $manager->persist($etablissement);
        $manager->flush();
    }
}
