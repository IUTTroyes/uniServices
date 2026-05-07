<?php

namespace App\DataFixtures;

use App\Entity\Structure\StructureService;
use App\Repository\PersonnelRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class StructureServiceHelpdeskFixtures extends Fixture implements OrderedFixtureInterface
{
    private PersonnelRepository $personnelRepository;

    public function __construct(PersonnelRepository $personnelRepository)
    {
        $this->personnelRepository = $personnelRepository;
    }

    public function getOrder(): int
    {
        return 8;
    }

    public function load(ObjectManager $manager): void
    {
        $listePersonnel = $this->personnelRepository->findAll();

        // On ajoute $index pour savoir si on est sur la ligne 0, 1, 2, 3...
        foreach ($listePersonnel as $index => $personne) {

            $identifiant = $personne->getUsername();
            $service = new StructureService();

            // Si l'index est pair (0, 2, 4...), on met 'Scolarité'
            // Si l'index est impair (1, 3, 5...), on met 'Audiovisuel'
            if ($index % 2 === 0) {
                $service->setLibelle('Scolarité');
            } else {
                $service->setLibelle('Audiovisuel');
            }

            $service->setPersonnel($identifiant);
            $manager->persist($service);
        }

        $manager->flush();
    }
}