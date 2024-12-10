<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ChangeDepartementProcessor implements ProcessorInterface
{
    public function __construct(
        protected Security $security,
        protected StructureDepartementPersonnelRepository $structureDepartementPersonnelRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        // Handle the state
        if ($data instanceof StructureDepartementPersonnel && $data->getPersonnel() === $user) {
            //récupérer tous les départements de mon user
            $departements = $this->structureDepartementPersonnelRepository->findBy(['personnel' => $user]);
            foreach ($departements as $departement) {
                $departement->setDefaut(false);
            }
            $data->setDefaut(true);
            $this->entityManager->flush();
        }
        return $this->structureDepartementPersonnelRepository->findBy(['personnel' => $user]);
    }
}
