<?php

namespace App\DataFixtures;

use App\Entity\FicheHeure;
use App\Entity\Users\Personnel;
use App\Enum\FicheHeureStatutEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class FicheHeureFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        // Assuming StructureUserFixtures creates and sets references for Personnel
        return [
            StructureUserFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Attempt to get references to users created in StructureUserFixtures
        // These reference names are illustrative and depend on their definition in StructureUserFixtures
        /** @var Personnel $biatssUser1 */
        $biatssUser1 = $this->getReference(StructureUserFixtures::BIATSS_USER_REFERENCE_1 ?? 'biatss-user-1');
        /** @var Personnel $biatssUser2 */
        $biatssUser2 = $this->getReference(StructureUserFixtures::BIATSS_USER_REFERENCE_2 ?? 'biatss-user-2');
        /** @var Personnel $validatorUser1 */
        $validatorUser1 = $this->getReference(StructureUserFixtures::VALIDATOR_USER_REFERENCE_1 ?? 'validator-user-1');

        if (!$biatssUser1 || !$biatssUser2 || !$validatorUser1) {
            // If references are not found, this fixture cannot run correctly.
            // Consider throwing an exception or logging a warning.
            // For this example, we'll simply return if essential users are missing.
            // In a real scenario, ensure StructureUserFixtures runs first and sets these references.
            echo "Warning: Required user references not found in FicheHeureFixtures. Skipping fixture loading.\n";
            return;
        }

        // 1. FicheHeure in BROUILLON status
        $ficheBrouillon = new FicheHeure();
        $ficheBrouillon->setPersonnel($biatssUser1);
        $ficheBrouillon->setSemaineAnnee('S23-2024'); // Week 23 of 2024
        $ficheBrouillon->setHeures([
            ['jour' => 'Lundi', 'heures' => 7, 'commentaire' => 'Travail normal'],
            ['jour' => 'Mardi', 'heures' => 7, 'commentaire' => ''],
        ]);
        $ficheBrouillon->setStatut(FicheHeureStatutEnum::BROUILLON);
        $manager->persist($ficheBrouillon);

        // 2. FicheHeure in SOUMISE status
        $ficheSoumise = new FicheHeure();
        $ficheSoumise->setPersonnel($biatssUser2);
        $ficheSoumise->setSemaineAnnee('S24-2024'); // Week 24 of 2024
        $ficheSoumise->setHeures([
            ['jour' => 'Lundi', 'heures' => 8, 'commentaire' => ''],
            ['jour' => 'Mardi', 'heures' => 8, 'commentaire' => 'Réunion projet'],
            ['jour' => 'Mercredi', 'heures' => 8, 'commentaire' => ''],
        ]);
        $ficheSoumise->setStatut(FicheHeureStatutEnum::SOUMISE);
        $ficheSoumise->setDateSoumission(new DateTimeImmutable('-3 days'));
        $manager->persist($ficheSoumise);

        // 3. FicheHeure in VALIDEE status
        $ficheValidee = new FicheHeure();
        $ficheValidee->setPersonnel($biatssUser1);
        $ficheValidee->setSemaineAnnee('S22-2024'); // Week 22 of 2024
        $ficheValidee->setHeures([
            ['jour' => 'Lundi', 'heures' => 7.5, 'commentaire' => ''],
            ['jour' => 'Mardi', 'heures' => 7.5, 'commentaire' => ''],
            ['jour' => 'Jeudi', 'heures' => 7.5, 'commentaire' => 'Formation'],
            ['jour' => 'Vendredi', 'heures' => 7.5, 'commentaire' => ''],
        ]);
        $ficheValidee->setStatut(FicheHeureStatutEnum::VALIDEE);
        $ficheValidee->setDateSoumission(new DateTimeImmutable('-7 days'));
        $ficheValidee->setValidateur($validatorUser1);
        $ficheValidee->setDateValidation(new DateTimeImmutable('-2 days'));
        $manager->persist($ficheValidee);

        // 4. FicheHeure in REJETEE status
        $ficheRejetee = new FicheHeure();
        $ficheRejetee->setPersonnel($biatssUser2);
        $ficheRejetee->setSemaineAnnee('S21-2024'); // Week 21 of 2024
        $ficheRejetee->setHeures([
            ['jour' => 'Mercredi', 'heures' => 4, 'commentaire' => 'Demie journée'],
            ['jour' => 'Jeudi', 'heures' => 0, 'commentaire' => 'Absence non justifiée?'],
        ]);
        $ficheRejetee->setStatut(FicheHeureStatutEnum::REJETEE);
        $ficheRejetee->setDateSoumission(new DateTimeImmutable('-10 days'));
        $ficheRejetee->setValidateur($validatorUser1);
        $ficheRejetee->setDateValidation(new DateTimeImmutable('-5 days'));
        $ficheRejetee->setCommentaireValidation('Merci de corriger les heures pour Jeudi.');
        $manager->persist($ficheRejetee);

        $manager->flush();
    }
}
