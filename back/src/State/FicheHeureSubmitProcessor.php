<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\FicheHeure;
use App\Enum\FicheHeureStatutEnum;

final class FicheHeureSubmitProcessor implements ProcessorInterface
{
    public function __construct(private ProcessorInterface $persistProcessor)
    {
    }

    /**
     * @param mixed $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return FicheHeure
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FicheHeure
    {
        if (!$data instanceof FicheHeure) {
            // Or handle error appropriately
            return $data;
        }

        // This processor is specifically for a PATCH operation changing the status
        // The 'submit' custom operation will be a PATCH.
        // We double-check the current status as a safeguard.
        if ($operation->getMethod() === 'PATCH' && $data->getStatut() === FicheHeureStatutEnum::BROUILLON) {
            $data->setStatut(FicheHeureStatutEnum::SOUMISE);
            $data->setDateSoumission(new \DateTimeImmutable());

            // Delegate to the standard persist processor to save the entity
            $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }
        // If conditions are not met, the original data is returned without changes by this processor,
        // or the persistProcessor might handle it if it's a standard PATCH.
        // For a custom operation, this processor might be the only one called.

        return $data;
    }
}
