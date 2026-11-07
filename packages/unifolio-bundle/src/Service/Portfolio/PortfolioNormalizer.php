<?php

namespace UnifolioBundle\Service\Portfolio;

use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPage;
use UnifolioBundle\Service\Campagne\CampagneNormalizer;
use UnifolioBundle\Service\Campagne\CampagneResolver;
use UnifolioBundle\Service\Trace\TraceNormalizer;

class PortfolioNormalizer
{
    public function __construct(
        private readonly PortfolioManager $manager,
        private readonly CampagneResolver $campagnes,
        private readonly CampagneNormalizer $campagneNormalizer,
        private readonly TraceNormalizer $traceNormalizer,
    ) {
    }

    /** @return array<string, mixed> */
    public function resume(Portfolio $p): array
    {
        $campagne = $p->getScolarite() !== null ? $this->campagnes->pourScolarite($p->getScolarite()) : null;

        return [
            'uuid' => $p->getUuidString(),
            'type' => ['value' => $p->getType()->value, 'libelle' => $p->getType()->getLibelle()],
            'titre' => $p->getTitre(),
            'visibilite' => ['value' => $p->getVisibilite()->value, 'libelle' => $p->getVisibilite()->getLibelle()],
            'scolarite' => $this->traceNormalizer->scolarite($p->getScolarite()),
            'campagne' => $campagne !== null ? $this->campagneNormalizer->resume($campagne) : null,
            'modele' => $p->getModele()?->getLibelle(),
            'modifiable' => $this->manager->estModifiable($p),
            'nbPages' => $p->getPages()->count(),
            'misAJourLe' => $p->getUpdatedAt()?->format(\DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function detail(Portfolio $p): array
    {
        return $this->resume($p) + [
            'theme' => $p->getTheme(),
            'pages' => array_map($this->page(...), $p->getPages()->toArray()),
        ];
    }

    /** @return array<string, mixed> */
    public function page(PortfolioPage $page): array
    {
        return [
            'uuid' => $page->getUuidString(),
            'titre' => $page->getTitre(),
            'ordre' => $page->getOrdre(),
            'verrouillee' => $page->isVerrouillee(),
            'verrouilles' => $page->getVerrouilles(),
            'version' => $page->getVersion(),
            'blocs' => $page->getBlocs(),
            'incomplets' => $this->manager->incomplets($page),
        ];
    }
}
