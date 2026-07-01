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
                $definition['colSpan'] = $layout->colSpan;
                $definition['rowSpan'] = $layout->rowSpan;
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
                $definition['colSpan'] = $preference->getColSpan();
                $definition['rowSpan'] = $preference->getRowSpan();
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
                $config['colSpan'] = $layout->colSpan;
                $config['rowSpan'] = $layout->rowSpan;
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
                $config['colSpan'] = 1;      // taille par défaut: 1 colonne
                $config['rowSpan'] = 1;      // taille par défaut: 1 ligne
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
                    $config['colSpan'] = $pref->getColSpan();
                    $config['rowSpan'] = $pref->getRowSpan();
                    $config['enabled'] = $pref->isEnabled();
                } else {
                    $config['position'] = null;
                    $config['colSpan'] = 1;      // taille par défaut: 1 colonne
                    $config['rowSpan'] = 1;      // taille par défaut: 1 ligne
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

        $dashboard = $this->dashboardRegistry->get($dashboardCode);

        // Construire un index d'ordre du layout par défaut pour le tri
        $defaultOrder = [];
        foreach ($dashboard->getDefaultLayout() as $index => $layout) {
            $defaultOrder[$layout->widgetCode] = $index;
        }

        $preferences = $this->preferenceRepository->findByPersonnel($user, $structureDepartementPersonnel, $dashboardCode);

        if (empty($preferences)) {
            // Initialiser les préférences à partir des widgets disponibles du dashboard
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
                    $pref->setColSpan($layout->colSpan);
                    $pref->setRowSpan($layout->rowSpan);
                    $pref->setEnabled($layout->enabled);
                } else {
                    $pref->setColSpan(1);
                    $pref->setRowSpan(1);
                    $pref->setEnabled(false);
                }

                // Appliquer les changements du widget cliqué
                if ($code === $widgetKey) {
                    if (isset($data['enabled'])) {
                        $pref->setEnabled($data['enabled']);
                    }
                    if (isset($data['colSpan'])) {
                        $pref->setColSpan($data['colSpan']);
                    }
                    if (isset($data['rowSpan'])) {
                        $pref->setRowSpan($data['rowSpan']);
                    }
                }

                // Position temporaire, sera recalculée ci-dessous
                $pref->setPosition(null);
                $this->preferenceRepository->save($pref, true);
            }
        } else {
            // Les préférences existent déjà : mise à jour du widget ciblé
            $pref = $this->preferenceRepository->findOneByPersonnelAndWidgetKey($user, $widgetKey, $structureDepartementPersonnel, $dashboardCode);

            if (null === $pref) {
                $pref = new DashboardPreference();
                $pref->setPersonnel($user);
                $pref->setStructureDepartementPersonnel($structureDepartementPersonnel);
                $pref->setDashboardCode($dashboardCode);
                $pref->setWidgetKey($widgetKey);
                $pref->setColSpan($data['colSpan'] ?? 1);
                $pref->setRowSpan($data['rowSpan'] ?? 1);
                $pref->setEnabled($data['enabled'] ?? true);
                $pref->setPosition(null);
                $this->preferenceRepository->save($pref, true);
            } else {
                if (isset($data['enabled'])) {
                    $pref->setEnabled($data['enabled']);
                }
                if (isset($data['colSpan'])) {
                    $pref->setColSpan($data['colSpan']);
                }
                if (isset($data['rowSpan'])) {
                    $pref->setRowSpan($data['rowSpan']);
                }
                $this->preferenceRepository->save($pref, true);
            }
        }

        // Recalculer les positions de tous les widgets de ce dashboard
        // pour garantir un enchaînement contigu (0, 1, 2, ...) uniquement sur les widgets enabled
        $allPreferences = $this->preferenceRepository->findByPersonnel($user, $structureDepartementPersonnel, $dashboardCode);

        // Séparer enabled et disabled
        $enabledPrefs = [];
        $disabledPrefs = [];
        foreach ($allPreferences as $p) {
            if ($p->isEnabled()) {
                $enabledPrefs[] = $p;
            } else {
                $disabledPrefs[] = $p;
            }
        }

        // Trier les widgets enabled en respectant l'ordre du layout par défaut,
        // puis par leur position actuelle pour ceux qui ne sont pas dans le layout par défaut
        usort($enabledPrefs, function (DashboardPreference $a, DashboardPreference $b) use ($defaultOrder) {
            $orderA = $defaultOrder[$a->getWidgetKey()] ?? PHP_INT_MAX;
            $orderB = $defaultOrder[$b->getWidgetKey()] ?? PHP_INT_MAX;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            // Pour les widgets hors du layout par défaut, conserver l'ordre de position existant
            return ($a->getPosition() ?? PHP_INT_MAX) <=> ($b->getPosition() ?? PHP_INT_MAX);
        });

        // Attribuer les positions contiguës aux widgets enabled
        $position = 0;
        foreach ($enabledPrefs as $p) {
            $p->setPosition($position);
            $this->preferenceRepository->save($p, true);
            $position++;
        }

        // Les widgets disabled n'ont pas de position
        foreach ($disabledPrefs as $p) {
            $p->setPosition(null);
            $this->preferenceRepository->save($p, true);
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
