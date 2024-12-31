<?php

namespace App\ApiDto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Entity\Structure\StructureCalendrier;
use App\Entity\Users\Personnel;
use App\State\UniEdt\PersonnelsContraintesProvider;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/edt/personnels-contraintes/{semaineFormation}'),
    ],
    openapi: new Operation(
        tags: ['Personnels Contraintes'],
        summary: 'Récupère les contraintes des personnels pour une semaine de formation donnée',
        parameters: [
           new Parameter(
               name: 'semaineFormation',
               in: 'path',
               required: true,
               schema: ['type' => 'integer'],
               description: 'La semaine de formation au format numerique',
           ),
        ],
        responses: [
            '200' => new Response(description: 'Ok'),
        ],
    ),
    //doc swagger
    provider: PersonnelsContraintesProvider::class,
)]
class PersonnelsContraintes
{
    protected array $contraintes = [];
    protected StructureCalendrier $semaineFormation;
    protected ?Personnel $personnel = null;

    public function getContraintes(): array
    {
        return $this->contraintes;
    }

    public function setContraintes(array $contraintes): void
    {
        $this->contraintes = $contraintes;
    }

    public function setSemaineFormation(StructureCalendrier $semaine)
    {
        $this->semaineFormation = $semaine;
    }

    public function setPersonnel(?Personnel $personnel = null)
    {
        $this->personnel = $personnel;
    }


}
