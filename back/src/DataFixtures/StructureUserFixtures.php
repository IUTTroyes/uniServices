<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureEtudiant;
use App\Entity\Structure\StructurePersonnel;
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
        $personnel = new StructurePersonnel();
        $password = $this->encoder->hashPassword($personnel, 'test');
        $personnel->setUsername('hero0010')
            ->setMailUniv('cyndel.herolt@univ-reims.fr')
            ->setPassword($password)
            ->setRoles(['ROLE_PERSONNEL'])
            ->setPrenom('Cyndel')
            ->setNom('HEROLT')
            ->setPhotoName('22405118.jpg')
        ;
        $manager->persist($personnel);

        // ----------- ETUDIANT
        $etudiant = new StructureEtudiant();
        $password = $this->encoder->hashPassword($etudiant, 'test');
        $etudiant->setUsername('hero0005')
            ->setMailUniv('cyndel.herolt@etudiant.univ-reims.fr')
            ->setPassword($password)
            ->setRoles(['ROLE_ETUDIANT'])
            ->setPrenom('Cyndel')
            ->setNom('HEROLT')
            ->setPhotoName('22405118.jpg')
        ;
        $manager->persist($etudiant);

        $manager->flush();
    }
}
