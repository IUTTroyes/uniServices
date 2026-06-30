<?php

namespace App\Controller;

use App\Entity\Dashboard\DashboardPreference;
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

        // on recupere le code du dashboard depuis la requete, par defaut 'portail'
        $dashboardCode = $request->query->get('dashboardCode', 'portail');
        $structureDepartementPersonnelId = $request->query->get('structureDepartementPersonnelId');

        $structureDepartementPersonnel = null;
        if ($structureDepartementPersonnelId) {
            $structureDepartementPersonnel = $this->structureDepartementPersonnelRepository->find($structureDepartementPersonnelId);
        }

        // on recupere les preferences de l'utilisateur si elles existent
        $preferences = $this->preferenceRepository->findByPersonnel($user, $structureDepartementPersonnel, $dashboardCode);

        $widgets = [];
        $dashboard = $this->dashboardRegistry->get($dashboardCode);

        if (empty($preferences)) {
            // Si aucune préférence n'existe, on charge le layout par défaut
            foreach ($dashboard->getDefaultLayout() as $layout) {
                $widgetDefinition = $this->coreWidgetRegistry->get($layout->widgetCode);
                if (null === $widgetDefinition) {
                    continue;
                }
                $definition = $widgetDefinition->toArray();

                $definition['position'] = $layout->position;
                $definition['size'] = $layout->size;
                $definition['enabled'] = $layout->enabled;
                $definition['key'] = $layout->widgetCode;

                $widgets[] = $definition;
            }
        } else {
            // Si des préférences existent, on utilise le layout sauvegardé
            foreach ($preferences as $preference) {
                if (!$preference->isEnabled()) {
                    continue;
                }
                $widgetDefinition = $this->coreWidgetRegistry->get($preference->getWidgetKey());
                if (null === $widgetDefinition) {
                    continue;
                }
                $definition = $widgetDefinition->toArray();

                $definition['position'] = $preference->getPosition();
                $definition['size'] = $preference->getSize();
                $definition['enabled'] = $preference->isEnabled();
                $definition['key'] = $preference->getWidgetKey();

                $widgets[] = $definition;
            }
        }

        return new JsonResponse([
            'bundles' => $this->coreWidgetRegistry->getBundles(),
            'widgets' => $widgets,
        ]);
    }

    #[Route('/api/widgets/available/{dashboardCode}', name: 'api_widgets_available', methods: ['GET'])]
    public function getWidgetsAvailable(Request $request, string $dashboardCode): JsonResponse
    {
        $user = $this->getCurrentPersonnel();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $structureDepartementPersonnelId = $request->query->get('structureDepartementPersonnelId');
        $structureDepartementPersonnel = null;
        if ($structureDepartementPersonnelId) {
            $structureDepartementPersonnel = $this->structureDepartementPersonnelRepository->find($structureDepartementPersonnelId);
        }

        $dashboard = $this->dashboardRegistry->get($dashboardCode);

        // Récupération des préférences
        $preferences = [];
        foreach ($this->preferenceRepository->findByPersonnel($user, $structureDepartementPersonnel, $dashboardCode) as $preference) {
            $preferences[$preference->getWidgetKey()] = $preference;
        }

        // Construire la liste des widgets dispo
        $widgets = [];
        foreach ($dashboard->getAvailableWidgets() as $layout) {
            $widgetDefinition = $this->coreWidgetRegistry->get($layout->widgetCode);
            if ($widgetDefinition) {
                $widgets[] = $widgetDefinition->toArray();
            }
        }

        $responseWidgets = [];
        if (empty($preferences)) {
            // On commence par les widgets du layout par défaut
            foreach ($dashboard->getDefaultLayout() as $layout) {
                $widgetDefinition = $this->coreWidgetRegistry->get($layout->widgetCode);
                if (null === $widgetDefinition) {
                    continue;
                }

                $config = $widgetDefinition->toArray();
                $config['position'] = $layout->position;
                $config['size'] = $layout->size;
                $config['enabled'] = $layout->enabled;
                $config['key'] = $layout->widgetCode;

                $responseWidgets[] = $config;
            }

            // On ajoute les widgets disponibles qui ne sont pas dans le layout par défaut
            foreach ($widgets as $widget) {
                // Si le widget existe déjà (il était dans le layout par défaut), on ne le rajoute pas
                if (in_array($widget['code'], array_column($responseWidgets, 'code'))) {
                    continue;
                }

                // On définit des valeurs par défaut pour ces widgets additionnels
                $config = $widget;
                $config['position'] = null;  // pas encore positionné
                $config['size'] = 'small';   // taille par défaut
                $config['enabled'] = false;  // inactif par défaut
                $config['key'] = $widget['code'];

                $responseWidgets[] = $config;
            }
        } else {
            // Utiliser les préférences de l'utilisateur
            foreach ($widgets as $widget) {
                $config = $widget;
                $config['key'] = $widget['code'];

                if (isset($preferences[$widget['code']])) {
                    $pref = $preferences[$widget['code']];
                    $config['position'] = $pref->getPosition();
                    $config['size'] = $pref->getSize();
                    $config['enabled'] = $pref->isEnabled();
                } else {
                    $config['position'] = null;
                    $config['size'] = 'small';
                    $config['enabled'] = false;
                }
                $responseWidgets[] = $config;
            }

            // Trier responseWidgets par position asc (les widgets non positionnés à la fin)
            usort($responseWidgets, function ($a, $b) {
                if ($a['position'] === null && $b['position'] === null) {
                    return 0;
                }
                if ($a['position'] === null) {
                    return 1;
                }
                if ($b['position'] === null) {
                    return -1;
                }
                return $a['position'] <=> $b['position'];
            });
        }

        return new JsonResponse([
            'widgets' => $responseWidgets,
        ]);
    }

    #[Route('/api/dashboard/widgets/{widgetKey}/layout', name: 'api_dashboard_widgets_layout_update', methods: ['PATCH'])]
    public function updateDashboardWidgetLayout(Request $request, string $widgetKey): JsonResponse
    {
        $user = $this->getCurrentPersonnel();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non autorisé'], JsonResponse::HTTP_FORBIDDEN);
        }

        $dashboardCode = $request->query->get('dashboardCode', 'intranet');
        $structureDepartementPersonnelId = $request->query->get('structureDepartementPersonnelId');

        $structureDepartementPersonnel = null;
        if ($structureDepartementPersonnelId) {
            $structureDepartementPersonnel = $this->structureDepartementPersonnelRepository->find($structureDepartementPersonnelId);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $preferences = $this->preferenceRepository->findByPersonnel($user, $structureDepartementPersonnel, $dashboardCode);

        if (empty($preferences)) {
            // on initialise les préférences à partir du layout par défaut du dashboard
            $dashboard = $this->dashboardRegistry->get($dashboardCode);
            $availableWidgets = $dashboard->getAvailableWidgets();
            $defaultLayouts = [];
            foreach ($dashboard->getDefaultLayout() as $layout) {
                $defaultLayouts[$layout->widgetCode] = $layout;
            }

            foreach ($availableWidgets as $availableWidget) {
                $code = $availableWidget->widgetCode;
                $pref = new DashboardPreference();
                $pref->setPersonnel($user);
                $pref->setStructureDepartementPersonnel($structureDepartementPersonnel);
                $pref->setDashboardCode($dashboardCode);
                $pref->setWidgetKey($code);

                if (isset($defaultLayouts[$code])) {
                    $layout = $defaultLayouts[$code];
                    $pref->setPosition($layout->position);
                    $pref->setSize($layout->size);
                    $pref->setEnabled($layout->enabled);
                } else {
                    $pref->setPosition(99);
                    $pref->setSize('small');
                    $pref->setEnabled(false);
                }

                // Appliquer les changements du widget cliqué
                if ($code === $widgetKey) {
                    if (isset($data['enabled'])) {
                        $pref->setEnabled($data['enabled']);
                    }
                    if (isset($data['size'])) {
                        $pref->setSize($data['size']);
                    }
                    if (isset($data['position'])) {
                        $pref->setPosition($data['position']);
                    }
                }

                $this->preferenceRepository->save($pref, true);
            }
        } else {
            // Les préférences existent déjà : mise à jour
            $pref = $this->preferenceRepository->findOneByPersonnelAndWidgetKey($user, $widgetKey, $structureDepartementPersonnel, $dashboardCode);

            if (null === $pref) {
                $pref = new DashboardPreference();
                $pref->setPersonnel($user);
                $pref->setStructureDepartementPersonnel($structureDepartementPersonnel);
                $pref->setDashboardCode($dashboardCode);
                $pref->setWidgetKey($widgetKey);
                $pref->setPosition($data['position'] ?? 99);
                $pref->setSize($data['size'] ?? 'small');
                $pref->setEnabled($data['enabled'] ?? true);
            } else {
                if (isset($data['enabled'])) {
                    $pref->setEnabled($data['enabled']);
                }
                if (isset($data['size'])) {
                    $pref->setSize($data['size']);
                }
                if (isset($data['position'])) {
                    $pref->setPosition($data['position']);
                }
            }

            $this->preferenceRepository->save($pref, true);
        }

        return new JsonResponse(['message' => 'Préférences du widget sauvegardées']);
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
}
