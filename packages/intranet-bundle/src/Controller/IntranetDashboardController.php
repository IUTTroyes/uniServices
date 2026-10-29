<?php

namespace IntranetBundle\Controller;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use IntranetBundle\Service\Dashboard\IntranetDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class IntranetDashboardController extends AbstractController
{
    #[Route('/api/intranet/dashboard/{section}', name: 'api_intranet_business_dashboard', methods: ['GET'])]
    public function __invoke(IntranetDashboardService $dashboard, string $section): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Personnel && !$user instanceof Etudiant) return new JsonResponse(['message' => 'Utilisateur non autorisé'], 403);
        return new JsonResponse($dashboard->getSection($user, $section));
    }
}
