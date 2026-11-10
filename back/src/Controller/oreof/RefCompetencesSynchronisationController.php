<?php

namespace App\Controller\oreof;

use App\Service\OReOF\SynchroRefCompetences;
use App\Repository\Structure\StructureDiplomeRepository;
use App\Utils\JsonRequest;
use App\Utils\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/oreof/ref-competences/synchronisation', name: 'oreof_competences_synchronisation', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class RefCompetencesSynchronisationController extends AbstractController
{
    public function __invoke(
        SynchroRefCompetences $synchroRefCompetences,
        Request               $request,
        StructureDiplomeRepository $diplomes,
    ): Response
    {
        $data = JsonRequest::getValuesFromString($request->getContent());
        $diploma = $diplomes->find($data['diplomeId']);
        if ($diploma === null) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('CAN_EDIT_DIPLOME', $diploma);
        if ($diploma->getDepartement()?->getId() !== (int) $data['departementId']) {
            throw $this->createAccessDeniedException();
        }

        $synchro = $synchroRefCompetences->synchroniser($data['departementId'], $data['diplomeId']);

        return JsonResponse::Success('Synchronisation des compétences terminée', [
            'synchronisation' => $synchro,
        ]);
    }
}
