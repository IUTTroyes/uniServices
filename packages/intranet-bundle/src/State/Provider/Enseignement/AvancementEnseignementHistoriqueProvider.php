<?php

namespace IntranetBundle\State\Provider\Enseignement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use IntranetBundle\Dto\Enseignement\AvancementEnseignementHistoriqueDto;
use IntranetBundle\Service\Enseignement\{AvancementEnseignementContexte, AvancementEnseignementService};

final class AvancementEnseignementHistoriqueProvider implements ProviderInterface
{
    public function __construct(
        private readonly AvancementEnseignementContexte $contexte,
        private readonly AvancementEnseignementService $service,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AvancementEnseignementHistoriqueDto
    {
        $data = $this->service->historique($this->contexte->charger(), $this->contexte->entier('groupe', true), $this->contexte->entier('avant'));
        return new AvancementEnseignementHistoriqueDto(...$data);
    }
}
