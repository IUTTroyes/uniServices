<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/DataFixtures/ConfigurationFixtures.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 15/06/2022 08:30
 */

namespace App\DataFixtures;

use App\Entity\Configuration;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ConfigurationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $conf = new Configuration();
        $conf->setCle('MAIL_FROM');
        $conf->setValeur('intranet-iut-troyes@univ-reims.fr');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('EXPORT_BONIFICATION');
        $conf->setValeur('NOTE_SUR_20'); //ou NOTE_SUR_05
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('ENQUETE_DIPLOME');
        $conf->setValeur(0);
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('MAIL_DEBUG');
        $conf->setValeur('mail@debug.com');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('SITE_UNIVERSITE');
        $conf->setValeur('https://www.univ-reims.fr');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('SITE_ENT');
        $conf->setValeur('https://ebureau.univ-reims.fr');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('CODE_IUT');
        $conf->setValeur('troyes');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('AFFICHAGE_TROMBI');
        $conf->setValeur(0);
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('APPLICATION_COVID');
        $conf->setValeur(0);
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('NOM_IUT');
        $conf->setValeur('IUT de Troyes');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('GRATIFICATION_HEURE_STAGE');
        $conf->setValeur('3.90');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('NOM_UNIVERSITE');
        $conf->setValeur('Université de Reims Champagne-Ardenne');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('LOGO_IUT');
        $conf->setValeur('logo_iut.png');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('LOGO_QUALTIE');
        $conf->setValeur('logo_iut_qualite.png');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('SITE_IUT');
        $conf->setValeur('https://iut-troyes.univ-reims.fr');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('SITE_INTRANET_IUT');
        $conf->setValeur('https://intranet.iut-troyes.univ-reims.fr');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('ADRESSE_IUT');
        $conf->setValeur('Iut de Troyes, 9, Rue de Québec, CS90396, 10026 Troyes Cedex');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('BASE_PATH');
        $conf->setValeur('upload/');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('LOGO_UNIVERSITE');
        $conf->setValeur('urca.jpeg');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('COLOR_IUT');
        $conf->setValeur('#FAB001');
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('MODIFICATION_PPN');
        $conf->setValeur(true);
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('MODIFICATION_GROUPE');
        $conf->setValeur(true);
        $manager->persist($conf);

        $conf = new Configuration();
        $conf->setCle('LOGO_INTRANET_IUT');
        $conf->setValeur('logo_intranet_iut_troyes.svg');
        $manager->persist($conf);

        $manager->flush();
    }
}
