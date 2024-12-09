<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use Doctrine\ORM\EntityManagerInterface;

class ChangeDepartementProcessor implements ProcessorInterface
{
    public function __construct(
        protected StructureDepartementPersonnelRepository $structureDepartementPersonnelRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Handle the state
        if ($data instanceof StructureDepartementPersonnel) {
            //récupérer tous les départements de mon user
            $departements = $this->structureDepartementPersonnelRepository->findBy(['personnel' => $data->getPersonnel()]);
            foreach ($departements as $departement) {
                $departement->setDefaut(false);
            }
            $data->setDefaut(true);
            $this->entityManager->flush();
        }

//        dd($data);
        return $this->structureDepartementPersonnelRepository->findBy(['personnel' => $data->getPersonnel()]);
    }
}
