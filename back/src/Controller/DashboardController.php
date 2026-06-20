<?php

namespace App\Controller;

use App\Domain\Dashboard\DashboardContext;
use App\Entity\Dashboard\DashboardPreference;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use App\Repository\Dashboard\DashboardPreferenceRepository;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use App\Services\Dashboard\DashboardWidgetRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardWidgetRegistry $widgetRegistry,
        private readonly DashboardPreferenceRepository $preferenceRepository,
        private readonly StructureDepartementPersonnelRepository $structureDepartementPersonnelRepository,
    ) {
    }

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function getDashboard(Request $request): JsonResponse
    {
        $user = $this->getCurrentPersonnel();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $structureDepartementPersonnel = $this->resolveStructureDepartementPersonnel($user, $request);
        $context = new DashboardContext($this->resolveDepartementId($user, $request, $structureDepartementPersonnel), $structureDepartementPersonnel);
        $preferences = [];
        foreach ($this->preferenceRepository->findByPersonnel($user, $structureDepartementPersonnel) as $preference) {
            $preferences[$preference->getWidgetKey()] = $preference;
        }

        $usedPositions = [];
        foreach ($this->widgetRegistry->all() as $widget) {
            if (!$widget->supports($user, $context)) {
                continue;
            }
            $preference = $preferences[$widget->getKey()] ?? null;
            if ($preference !== null && $preference->getPosition() !== null) {
                $usedPositions[] = $preference->getPosition();
            }
        }

        $widgets = [];
        $position = 0;
        foreach ($this->widgetRegistry->all() as $widget) {
            if (!$widget->supports($user, $context)) {
                continue;
            }

            $preference = $preferences[$widget->getKey()] ?? null;
            
            if ($preference !== null && $preference->getPosition() !== null) {
                $widgetPosition = $preference->getPosition();
            } else {
                while (in_array($position, $usedPositions, true)) {
                    $position++;
                }
                $widgetPosition = $position++;
                $usedPositions[] = $widgetPosition;
            }

            $widgets[] = [
                'key' => $widget->getKey(),
                'label' => $widget->getLabel(),
                'icon' => $widget->getIcon(),
                'component' => $widget->getVueComponent(),
                'enabled' => $preference?->isEnabled() ?? $widget->isDefaultEnabled(),
                'collapsed' => $preference?->isCollapsed() ?? false,
                'position' => $widgetPosition,
                'size' => $preference?->getSize() ?? $widget->getDefaultSize(),
                'config' => array_merge($widget->getDefaultConfig(), $preference?->getConfig() ?? []),
                'dataUrl' => $widget->getDataUrl(),
            ];
        }

        usort($widgets, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        return new JsonResponse(['widgets' => $widgets]);
    }

    #[Route('/api/dashboard/widgets/{key}', name: 'api_dashboard_widget_data', methods: ['GET'])]
    public function getWidgetData(string $key, Request $request): JsonResponse
    {
        $user = $this->getCurrentPersonnel();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $widget = $this->widgetRegistry->get($key);
        if (null === $widget) {
            return new JsonResponse(['message' => 'Widget introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $structureDepartementPersonnel = $this->resolveStructureDepartementPersonnel($user, $request);
        $context = new DashboardContext($this->resolveDepartementId($user, $request, $structureDepartementPersonnel), $structureDepartementPersonnel);
        if (!$widget->supports($user, $context)) {
            return new JsonResponse(['message' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $preference = $this->preferenceRepository->findOneByPersonnelAndWidgetKey($user, $key, $structureDepartementPersonnel);
        $config = array_merge($widget->getDefaultConfig(), $preference?->getConfig() ?? []);

        return new JsonResponse($widget->getData($user, $context, $config));
    }

    #[Route('/api/dashboard/widgets/{key}/layout', name: 'api_dashboard_widget_layout_patch', methods: ['PATCH'])]
    public function patchWidgetLayout(string $key, Request $request): JsonResponse
    {
        $user = $this->getCurrentPersonnel();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $widget = $this->widgetRegistry->get($key);
        if (null === $widget) {
            return new JsonResponse(['message' => 'Widget introuvable'], JsonResponse::HTTP_NOT_FOUND);
        }

        $structureDepartementPersonnel = $this->resolveStructureDepartementPersonnel($user, $request);
        $context = new DashboardContext($this->resolveDepartementId($user, $request, $structureDepartementPersonnel), $structureDepartementPersonnel);
        if (!$widget->supports($user, $context)) {
            return new JsonResponse(['message' => 'Accès refusé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['message' => 'Payload invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $preference = $this->preferenceRepository->findOneByPersonnelAndWidgetKey($user, $key, $structureDepartementPersonnel) ?? (new DashboardPreference())
            ->setPersonnel($user)
            ->setStructureDepartementPersonnel($structureDepartementPersonnel)
            ->setWidgetKey($key);

        if (array_key_exists('enabled', $payload)) {
            $preference->setEnabled((bool) $payload['enabled']);
        }
        if (array_key_exists('collapsed', $payload)) {
            $preference->setCollapsed((bool) $payload['collapsed']);
        }
        if (array_key_exists('position', $payload)) {
            $preference->setPosition(max(0, (int) $payload['position']));
        }
        if (array_key_exists('size', $payload)) {
            $size = (string) $payload['size'];
            if (in_array($size, ['small', 'medium', 'large'], true)) {
                $preference->setSize($size);
            }
        }
        if (array_key_exists('config', $payload) && is_array($payload['config'])) {
            $preference->setConfig($payload['config']);
        }

        $this->preferenceRepository->save($preference, true);

        return new JsonResponse(['status' => 'ok']);
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
