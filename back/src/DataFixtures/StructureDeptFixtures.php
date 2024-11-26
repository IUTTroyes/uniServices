<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureDepartement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StructureDeptFixtures extends Fixture
{
    public function getOrder()
    {
        return 1;
    }

    public function load(ObjectManager $manager): void
    {
        $departement1 = new StructureDepartement();
        $departement1->setLibelle('MMI')
            ->setLogoName('mmi.png')
            ->setTelContact('03 25 42 71 29')
            ->setCouleur('#0096ff')
            ->setSiteWeb('https://mmi.iut-troyes.univ-reims.fr/but-mmi/')
            ->setDescription('Le département  Métiers de l’Internet et du Multimédia  de l’IUT de Troyes (précédemment Services et Réseaux de Communication) forme les acteurs de l’Internet, des médias numériques, de la communication plurimédia, de la création.')
            ->setActif(true)
            ->setOpt(['update_celcat' => '1']);
        ;
        $manager->persist($departement1);

        $departement2 = new StructureDepartement();
        $departement2->setLibelle('GEA')
            ->setLogoName('gea.png')
            ->setTelContact('03 25 42 46 09')
            ->setCouleur('#007861')
            ->setSiteWeb(null)
            ->setDescription('La gestion des entreprises et des administrations représente à la fois le coeur et l’esprit de toute structure humaine dont l’objectif est de proposer un service à un public. Le coeur, car au fondement de toute organisation se trouvent des hommes et des femmes, et par conséquent un besoin de les diriger. L’esprit, car au sein de cet ensemble dynamique circulent des données et des flux financiers, et par conséquent un besoin de les comprendre et de les manipuler. Discipline immuable, la gestion des entreprises et des administrations ne représente un savoir-faire vivant : elle évolue avec le monde dans lequel elle s’inscrit.')
            ->setActif(true)
            ->setOpt(['update_celcat' => '1']);
        ;
        $manager->persist($departement2);

        $manager->flush();
    }
}
