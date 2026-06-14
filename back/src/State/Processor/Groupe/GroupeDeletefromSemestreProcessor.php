<?php

namespace App\State\Processor\Groupe;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre; // Ne pas oublier l'import
use Doctrine\ORM\EntityManagerInterface;

class GroupeDeletefromSemestreProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof StructureGroupe) {
            return $data;
        }

        $request = $context['request'] ?? null;
        if ($request) {
            $body = $request->toArray();
            $semestreId = $body['semestre'] ?? null;

            if ($semestreId) {
                // Récupération de l'entité StructureSemestre
                $semestre = $this->em->getRepository(StructureSemestre::class)->find($semestreId);

                if ($semestre) {
                    $data->removeSemestre($semestre);
                    $this->em->flush();
                }
            }
        }

        return $data;
    }
}
