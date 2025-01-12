<?php

namespace App\DataFixtures;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StructureUserFixtures extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $encoder,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 1;
    }

    public function load(ObjectManager $manager): void
    {
        // ----------- PERSONNEL
        $personnel = new Personnel();
        $password = $this->encoder->hashPassword($personnel, 'test');
        $personnel->setUsername('personnel')
            ->setMailUniv('personnel.user@univ-reims.fr')
            ->setPassword($password)
            ->setRoles(["ROLE_PERMANENT", "ROLE_EDUSIGN", "ROLE_ASSISTANT"])
            ->setPrenom('John')
            ->setNom('DOE')
            ->setApplications(['uniTranet'])
            ->setPhotoName('noimage.png')
        ;
        $manager->persist($personnel);

        // ----------- ETUDIANT
        $etudiant = new Etudiant();
        $password = $this->encoder->hashPassword($etudiant, 'test');
        $etudiant->setUsername('etudiant')
            ->setMailUniv('etudiant.user@etudiant.univ-reims.fr')
            ->setPassword($password)
            ->setRoles(['ROLE_ETUDIANT'])
            ->setPrenom('Jane')
            ->setNom('Doe')
            ->setPhotoName('noimage.png')
        ;
        $manager->persist($etudiant);

        $manager->flush();
    }
}
