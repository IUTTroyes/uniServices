<?php

namespace IntranetBundle\State\Processor\Enseignement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use IntranetBundle\Dto\Enseignement\{AvancementEnseignementSaisieDto, AvancementEnseignementRevisionDto};
use IntranetBundle\Service\Enseignement\{AvancementEnseignementContexte, AvancementEnseignementService};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AvancementEnseignementProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AvancementEnseignementContexte $contexte,
        private readonly AvancementEnseignementService $service,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AvancementEnseignementRevisionDto
    {
        if (!$data instanceof AvancementEnseignementSaisieDto) throw new BadRequestHttpException('Saisie d’avancement invalide.');
        $revision = $this->service->enregistrer(
            $this->contexte->charger(), $this->contexte->entier('groupe', true), $this->contexte->enseignant(), $data->toArray(),
        );
        return new AvancementEnseignementRevisionDto(...$revision);
    }
}
