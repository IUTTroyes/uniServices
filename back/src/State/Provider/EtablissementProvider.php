<?php

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Etablissement;
use App\Repository\EtablissementRepository;

class EtablissementProvider implements ProviderInterface
{
    public function __construct(
        private EtablissementRepository $etablissementRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Pour une opération GET (item)
        if (empty($uriVariables)) {
            return $this->getSingleEtablissement();
        }

        // Pour une opération GET (collection) - bien que dans ton cas ce ne soit pas nécessaire
        return [$this->getSingleEtablissement()];
    }

    private function getSingleEtablissement(): Etablissement
    {
        $etablissement = $this->etablissementRepository->findSingleEtablissement();

        if (!$etablissement) {
            throw new \RuntimeException('Aucun établissement n\'a été configuré. Veuillez en créer un.');
        }

        return $etablissement;
    }

}
