<?php

namespace App\State\Edt;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\Edt\EdtAgendaDto;

class EdtAgendaProvider implements ProviderInterface
{

    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $data = $this->collectionProvider->provide($operation, $uriVariables, $context);

            if (empty($data)) {
                return [];
            }

            foreach ($data as $item) {
                $output[] = $this->syntheseToDto($item);
            }
        } else {
            $data = $this->itemProvider->provide($operation, $uriVariables, $context);
        }
        return $this->syntheseToDto($data);
    }

    public function syntheseToDto($item): EdtAgendaDto
    {
        $edt = new EdtAgendaDto();
        $edt->setIdEnseignement($item->getEnseignement()?->getId() ?? 0);
        $edt->setCodeEnseignement($item->getCodeModule());
        $edt->setLibelleEnseignement($item->getLibModule());
        $edt->setTypeEnseignement($item->getEnseignement()?->getType() ?? null);
        $edt->setLibPersonnel($item->getLibPersonnel());
        $edt->setIdPersonnel($item->getPersonnel()?->getId() ?? 0);
        $edt->setDebut($item->getDebut());
        $edt->setFin($item->getFin());
        $edt->setLibGroupe($item->getLibGroupe());
        $edt->setTypeGroupe($item->getType());
        $edt->setEval($item->getEval());
        $edt->setSalle($item->getSalle());
        $edt->setCouleur($item->getCouleur());
        $edt->setAnneeUniversitaire($item->getAnneeUniversitaire());
        return $edt;
    }

}
