<?php

namespace App\Controller\oreof;

use App\Services\OReOF\SynchroRefCompetences;
use App\Services\OReOF\SynchroRefFormation;
use App\Utils\JsonRequest;
use App\Utils\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/oreof/ref-formation/synchronisation', name: 'oreof_programme_synchronisation')]
class RefProgrammeSynchronisationController extends AbstractController
{
    public function __invoke(
        SynchroRefFormation $synchroRefFormation,
        Request               $request
    ): Response
    {
        $data = JsonRequest::getValuesFromString($request->getContent());
        $synchro = $synchroRefFormation->synchroniser($data['selectedDiplome'], $data['anneeUniversitaire'], $data['oreofId']);

        return JsonResponse::Success('Synchronisation des compétences terminée', [
            'synchronisation' => $synchro,
        ]);
    }
}
