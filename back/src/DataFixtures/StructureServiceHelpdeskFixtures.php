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

        for ($i = 0; $i < 2; $i++) {
            $service = new StructureService();

            if ($i===0) {
                foreach ($listePersonnel as $index => $personne) {
                    if ($index % 2 === 0) {
                        $service->addPersonnel($personne);
                    }
                    $manager->persist($service);
                }
                $service->setLibelle('Scolarité');
            } else {
                foreach ($listePersonnel as $index => $personne) {
                    if ($index % 2 !== 0) {
                        $service->addPersonnel($personne);
                    }
                    $manager->persist($service);
                }
                $service->setLibelle('Audiovisuel');
            }
        }


        $manager->flush();
    }
}