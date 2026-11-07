<?php

namespace UnifolioBundle\Service\Portfolio;

use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\PortfolioModele;

class ModeleNormalizer
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return array<string, mixed> */
    public function resume(PortfolioModele $m, bool $modifiable = false): array
    {
        return [
            'uuid' => $m->getUuidString(),
            'libelle' => $m->getLibelle(),
            'description' => $m->getDescription(),
            'portee' => $m->getDiplome() !== null ? 'diplome' : ($m->getDepartement() !== null ? 'departement' : 'national'),
            'libellePortee' => $m->getDiplome()?->getLibelle() ?? $m->getDepartement()?->getLibelle() ?? 'Toutes les formations',
            'actif' => $m->isActif(),
            'nbPages' => \count($m->getPages()),
            'nbCampagnes' => $this->em->getRepository(Campagne::class)->count(['modele' => $m]),
            'modifiable' => $modifiable,
        ];
    }

    /** @return array<string, mixed> */
    public function detail(PortfolioModele $m, bool $modifiable = false): array
    {
        return $this->resume($m, $modifiable) + ['pages' => $m->getPages()];
    }
}
