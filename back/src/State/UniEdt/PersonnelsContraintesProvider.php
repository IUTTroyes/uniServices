<?php

namespace App\State\UniEdt;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiDto\PersonnelsContraintes;
use App\Entity\Structure\StructureCalendrier;
use App\Entity\Users\Personnel;
use App\Repository\PersonnelRepository;
use App\Repository\Structure\StructureCalendrierRepository;

class PersonnelsContraintesProvider implements ProviderInterface
{
    public function __construct(
        protected StructureCalendrierRepository $structureCalendrierRepository,
        protected PersonnelRepository           $personnelRepository
    )
    {
    }

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
            $semaine = $this->structureCalendrierRepository->findOneBy(['semaineFormation' => $semaine]); //todo: ajouter l'année universitaire courante
        }

        if ($personnel !== null) {
            $personnel = $this->personnelRepository->find($personnel);
        }

        $contraintes = new PersonnelsContraintes();
        $contraintes->setSemaineFormation($semaine);
        $contraintes->setPersonnel($personnel);
        $contraintes->setContraintes($this->getContraintes($semaine, $personnel));

        // Retrieve the state from somewhere
        return $contraintes;
    }

    private function getContraintes(StructureCalendrier $semaine, Personnel $personnel): array
    {
        //todo: si pas de semaine seule les contraintes all...

        //récupère les contraintes de la semaine pour le personnel + les contraintes de l'année pour le personnel
        $tContraintes = [];
        $contraintesPersonnels = $personnel->getContraintesEdt();
        foreach ($contraintesPersonnels as $typeContrainte => $contraintes) {
            foreach ($contraintes as $keySemaine => $contrainte) {
                if ($keySemaine === 'all') {
                    foreach ($contrainte as $contr) {
                        $tContraintes[$contr['day'] . '_' . $contr['time']]['type'] = $typeContrainte;
                        $tContraintes[$contr['day'] . '_' . $contr['time']]['contrainte'] = $contr;
                    }
                }
                if ((int)$keySemaine === $semaine->getSemaineFormation()) {
                    foreach ($contrainte as $contr) {
                        $tContraintes[$contr['day'] . '_' . $contr['time']]['type'] = $typeContrainte;
                        $tContraintes[$contr['day'] . '_' . $contr['time']]['contrainte'] = $contr;
                    }
                }
            }
        }


        return $tContraintes;
    }
}
