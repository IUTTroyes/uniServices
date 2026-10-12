<?php

namespace App\State\Processor\Departement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\StructureDepartement;
use Doctrine\ORM\EntityManagerInterface;

class DepartementUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof StructureDepartement) {
            $this->em->persist($data);
            $this->em->flush();
            return $data;
        }

        $this->em->flush();
        return $data;
    }
}
