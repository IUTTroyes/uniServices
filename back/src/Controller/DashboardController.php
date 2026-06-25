<?php

namespace App\Controller;

use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use App\Repository\Dashboard\DashboardPreferenceRepository;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use App\Services\Dashboard\Core\DashboardRegistry;
use App\Services\Dashboard\Core\WidgetDataRegistry;
use App\Services\Dashboard\Core\WidgetRegistry as CoreWidgetRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly CoreWidgetRegistry $coreWidgetRegistry,
        private readonly WidgetDataRegistry $widgetDataRegistry,
        private readonly DashboardPreferenceRepository $preferenceRepository,
        private readonly StructureDepartementPersonnelRepository $structureDepartementPersonnelRepository,
        private readonly DashboardRegistry $dashboardRegistry,
    ) {}


    #[Route('/api/widgets/catalog', name: 'api_widgets_catalog', methods: ['GET'])]
    public function getWidgetsCatalog(Request $request): JsonResponse
    {
        $user = $this->getCurrentPersonnel();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        // on recupere les preferences de l'utilisateur si elles existent
        $dashboardCode = $request->query->get('dashboardCode', 'portail');
        $preferences = [];
        foreach ($this->preferenceRepository->findByPersonnel($user, null, $dashboardCode) as $preference) {
            $preferences[$preference->getWidgetKey()] = $preference;
        }

        $widgets = [];
        $dashboard = $this->dashboardRegistry->get($dashboardCode);
        foreach ($dashboard->getWidgets() as $layout) {
            $widgetDefinition =
                $this->coreWidgetRegistry->get(
                    $layout->widgetCode
                );
            $definition = $widgetDefinition->toArray();

            $definition['position'] = $layout->position;
            $definition['size'] = $layout->size;
            $definition['enabled'] = $layout->enabled;

            $widgets[] = $definition;
        }

        return new JsonResponse([
            'bundles' => $this->coreWidgetRegistry->getBundles(),
            'widgets' => $widgets,
        ]);
    }

    #[Route('/api/widgets/{code}/data', name: 'api_widgets_data', methods: ['GET'])]
    public function getWidgetData(string $code): JsonResponse
    {
        $user = $this->getCurrentPersonnel();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        if (null === $this->coreWidgetRegistry->get($code)) {
            return new JsonResponse(['message' => 'Widget introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $this->widgetDataRegistry->get($code, $user);
        if (null === $data) {
            return new JsonResponse(['message' => 'Aucune donnée disponible pour ce widget'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($data);
    }

    private function getCurrentPersonnel(): ?Personnel
    {
        $user = $this->getUser();

        return $user instanceof Personnel ? $user : null;
    }

    private function resolveDepartementId(Personnel $user, Request $request, ?StructureDepartementPersonnel $structureDepartementPersonnel = null): ?int
    {
        if (null !== $structureDepartementPersonnel && null !== $structureDepartementPersonnel->getDepartement()) {
            return $structureDepartementPersonnel->getDepartement()->getId();
        }

        $fromRequest = $request->query->getInt('departementId');
        if ($fromRequest > 0) {
            return $fromRequest;
        }

        foreach ($user->getDepartementPersonnels() as $departementPersonnel) {
            if ($departementPersonnel->isDefaut() && null !== $departementPersonnel->getDepartement()) {
                return $departementPersonnel->getDepartement()->getId();
            }
        }

        return null;
    }

    private function resolveStructureDepartementPersonnel(Personnel $user, Request $request): ?StructureDepartementPersonnel
    {
        $structureDepartementPersonnelId = $request->query->getInt('structureDepartementPersonnelId');
        if ($structureDepartementPersonnelId > 0) {
            $structureDepartementPersonnel = $this->structureDepartementPersonnelRepository->find($structureDepartementPersonnelId);
            if (null !== $structureDepartementPersonnel && $structureDepartementPersonnel->getPersonnel()?->getId() === $user->getId()) {
                return $structureDepartementPersonnel;
            }
        }

        foreach ($user->getDepartementPersonnels() as $departementPersonnel) {
            if ($departementPersonnel->isDefaut()) {
                return $departementPersonnel;
            }
        }

        $departementPersonnels = $user->getDepartementPersonnels();
        if (!$departementPersonnels->isEmpty()) {
            return $departementPersonnels->first();
        }

        return null;
    }
}
