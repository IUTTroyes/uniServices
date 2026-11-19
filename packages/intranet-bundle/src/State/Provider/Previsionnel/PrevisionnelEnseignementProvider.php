<?php

declare(strict_types=1);

namespace IntranetBundle\State\Provider\Previsionnel;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use IntranetBundle\Dto\Previsionnel\PrevisionnelEnseignementDto;

final readonly class PrevisionnelEnseignementProvider implements ProviderInterface
{
    public function __construct(private PrevisionnelSemestreProvider $semestreProvider) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        [$lignes, , , $verification, $totaux, $equivalentTd] = $this->semestreProvider->provide($operation, $uriVariables, $context);
        $previsionnels = [];
        foreach ($lignes as $ligne) {
            $dto = new PrevisionnelEnseignementDto();
            $dto->setTypeEnseignement($ligne->getTypeEnseignement());
            $dto->setLibelle($ligne->getLibelleEnseignement());
            $dto->setPersonnel($ligne->getPersonnels()[0]);
            $dto->setHeures($ligne->getHeures());
            $previsionnels[] = $dto;
        }

        return [
            $previsionnels,
            $verification,
            ['TotalCM' => $totaux['CM'], 'TotalTD' => $totaux['TD'], 'TotalTP' => $totaux['TP']],
            $equivalentTd,
        ];
    }
}
