<?php

declare(strict_types=1);

namespace App\Controller\Edt;

use App\Entity\Edt\EdtContraintesSemestre;
use App\Repository\Edt\EdtContraintesSemestreRepository;
use App\Repository\Structure\StructureCalendrierRepository;
use App\Repository\Structure\StructureSemestreRepository;
use App\Utils\JsonRequest;
use App\Utils\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EdtCalendrierContraintesController extends AbstractController
{
    #[Route('/api/edt-calendrier-contraintes', name: 'edt_calendrier_contraintes', methods: ['POST'])]
    public function index(
        EntityManagerInterface           $entityManager,
        StructureSemestreRepository      $structureSemestreRepository,
        StructureCalendrierRepository    $structureCalendrierRepository,
        EdtContraintesSemestreRepository $edtContraintesSemestreRepository,
        Request                          $request
    ): Response
    {
        //todo: tester securité et droits

        $data = JsonRequest::getValuesFromString($request->getContent());

        $semestre = $structureSemestreRepository->find($data['semestreId']);

        if (!$semestre) {
            return $this->json(['message' => 'Semestre not found'], Response::HTTP_NOT_FOUND);
        }

        $semaine = $structureCalendrierRepository->find($data['weekId']);

        if (!$semaine) {
            return $this->json(['message' => 'Week not found'], Response::HTTP_NOT_FOUND);
        }

        $contraintes = $edtContraintesSemestreRepository->findOneBy(['semestre' => $semestre]); //todo: ajouter AnneeUniversitaire

        if (!$contraintes) {
            $contraintes = new EdtContraintesSemestre();
            $contraintes->setSemestre($semestre);
            $entityManager->persist($contraintes);
        }

        $tContraintes = $contraintes->getContraintes() ?? [];
        $tContraintes[$semaine->getId()] = ['type' => $data['contrainte']];
        $contraintes->setContraintes($tContraintes);

        $entityManager->flush();

        return JsonResponse::Success('Contrainte ajoutée', $semestre);

    }
}
