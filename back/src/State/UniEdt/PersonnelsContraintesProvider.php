<?php

namespace App\State\UniEdt;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\PersonnelsContraintes;
use App\Repository\PersonnelRepository;
use App\Repository\Structure\StructureCalendrierRepository;

class PersonnelsContraintesProvider implements ProviderInterface
{
    public function __construct(
        protected StructureCalendrierRepository $structureCalendrierRepository,
        protected PersonnelRepository $personnelRepository
    ){}
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // récupérer le lundi de la semaineFormation,
        // caluler la date du vendredi.
        // identifier toutes les contraintes qui sont dans la plage de date
        // construire le DTO qui va bien

        if ($operation instanceof CollectionOperationInterface) {

        }

        $semaine = $uriVariables['semaineFormation'];
        $personnel = $context['request']->query->get('personnel');

        if ($semaine === null && $personnel === null) {
            return null;
        }

        if ($semaine !== null) {
            $semaine = $this->structureCalendrierRepository->find($semaine);
        }

        if ($personnel !== null) {
            $personnel = $this->personnelRepository->find($personnel);
        }

        $contraintes = new PersonnelsContraintes();
        $contraintes->setSemaineFormation($semaine);
        $contraintes->setPersonnel($personnel);



        // Retrieve the state from somewhere
        return $contraintes;
    }
}
