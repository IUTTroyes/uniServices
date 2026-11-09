<?php

namespace IntranetBundle\State\Provider\Enseignement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use IntranetBundle\Dto\Enseignement\AvancementEnseignementListeDto;
use IntranetBundle\Service\Enseignement\{AvancementEnseignementContexte, AvancementEnseignementService};

final class AvancementEnseignementProvider implements ProviderInterface
{
    public function __construct(
        private readonly AvancementEnseignementContexte $contexte,
        private readonly AvancementEnseignementService $service,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AvancementEnseignementListeDto
    {
        $data = $this->service->liste($this->contexte->charger());
        return new AvancementEnseignementListeDto(...$data);
    }
}
