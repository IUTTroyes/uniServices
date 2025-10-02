<?php

namespace App\Controller\oreof;

use App\Services\OReOF\SynchroRefCompetences;
use App\Utils\JsonRequest;
use App\Utils\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/oreof/ref-competences/synchronisation', name: 'oreof_competences_synchronisation')]
class RefCompetencesSynchronisationController extends AbstractController
{
    public function __invoke(
        SynchroRefCompetences $synchroRefCompetences,
        Request               $request
    ): Response
    {
        $data = JsonRequest::getValuesFromString($request->getContent());
        $synchro = $synchroRefCompetences->synchroniser($data['departementId'], $data['diplomeId']);

        return JsonResponse::Success('Synchronisation des compétences terminée', [
            'synchronisation' => $synchro,
        ]);
    }
}
