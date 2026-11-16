<?php

declare(strict_types=1);

namespace App\Controller\Edt;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Service\Calendrier\CalendrierUniversitaireService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CalendrierUniversitaireController extends AbstractController
{
    #[Route('/api/annees-universitaires/{id}/calendrier', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(#[MapEntity(id: 'id')] StructureAnneeUniversitaire $annee, Request $request, CalendrierUniversitaireService $service): JsonResponse
    {
        try {
            $dateString = $request->query->get('date');
            if ($dateString !== null) {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateString);
                if ($date === false || $date->format('Y-m-d') !== $dateString) {
                    return $this->json(['message' => 'Date attendue au format AAAA-MM-JJ.'], 400);
                }
                $numero = $service->getSemaineFormation($annee, $date);
                return $this->json(['semaine' => $numero === null ? null : $service->getSemaine($annee, $numero)]);
            }
            return $this->json(['semaines' => $service->getCalendrier($annee)]);
        } catch (\DomainException $exception) {
            return $this->json(['message' => $exception->getMessage()], 422);
        }
    }
}
