<?php

namespace AuthBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDefinition;
use App\Domain\Dashboard\WidgetProviderInterface;
use App\Repository\EtablissementRepository;

class AuthWidgetProvider implements WidgetProviderInterface
{

    public function __construct(private EtablissementRepository $etablissementRepository)
    {

    }

    /**
     * @inheritDoc
     */
    public function getWidgets(): array
    {
        return [
                new WidgetDefinition('auth.actus_ext', 'auth', 'Actualités de ' . $this->getEtablissementLibelle(), 'pi pi-calendar', 'EmploiDuTempsWidget', 'large', true, defaultConfig: ['position' => 1]),
        ];
    }

    public function getEtablissementLibelle(): string
    {
        // Implementation for getting etablissement libelle. Il n'y a qu'un seul établissement dans la base de données
        $etablissement = $this->etablissementRepository->findOneBy([]);
        return $etablissement ? $etablissement->getLibelle() : '';
    }

    public function getBundleCode(): string
    {
        return 'auth';
    }

    public function getBundleLabel(): string
    {
        return 'Auth';
    }
}
