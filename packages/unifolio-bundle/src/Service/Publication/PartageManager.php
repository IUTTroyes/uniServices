<?php

namespace UnifolioBundle\Service\Publication;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Enum\VisibilitePortfolioEnum;
use UnifolioBundle\Repository\PortfolioRepository;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Réglages de partage d'un portfolio : visibilité, adresse, lien secret, indexation, description. */
class PartageManager
{
    private const RESERVES = ['sitemap', 'api', 'admin', 'public', 'partage', 'export', 'portfolio', 'media'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PortfolioRepository $portfolios,
    ) {
    }

    /**
     * @param array<string, mixed> $donnees visibilite?, slug?, indexable?, description?, regenererLien?
     */
    public function configurer(Portfolio $portfolio, array $donnees): Portfolio
    {
        try {
            if (isset($donnees['visibilite'])) {
                $visibilite = VisibilitePortfolioEnum::tryFrom((string) $donnees['visibilite'])
                    ?? throw new \InvalidArgumentException('Visibilité inconnue.');
                $portfolio->setVisibilite($visibilite);
            }
            if (\array_key_exists('description', $donnees)) {
                $portfolio->setDescription($donnees['description'] !== null ? (string) $donnees['description'] : null);
            }
            if (isset($donnees['slug']) && $donnees['slug'] !== '' && strtolower((string) $donnees['slug']) !== $portfolio->getSlug()) {
                $this->changerSlug($portfolio, (string) $donnees['slug']);
            }
            $visibilite = $portfolio->getVisibilite();
            if ($visibilite === VisibilitePortfolioEnum::PUBLIC || $visibilite === VisibilitePortfolioEnum::LIEN_SECRET) {
                $portfolio->getSlug() ?? $this->changerSlug($portfolio, $this->proposerSlug($portfolio));
            }
            if ($visibilite === VisibilitePortfolioEnum::LIEN_SECRET && ($portfolio->getTokenPartage() === null || !empty($donnees['regenererLien']))) {
                $portfolio->regenererTokenPartage();
            } elseif (!empty($donnees['regenererLien']) && $portfolio->getTokenPartage() !== null) {
                $portfolio->regenererTokenPartage();
            }
            // L'indexation est une démarche volontaire, réservée aux portfolios publics.
            $portfolio->setIndexable($visibilite === VisibilitePortfolioEnum::PUBLIC && !empty($donnees['indexable'] ?? $portfolio->isIndexable()));
        } catch (\InvalidArgumentException|\DomainException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
        $this->em->flush();

        return $portfolio;
    }

    private function changerSlug(Portfolio $portfolio, string $slug): void
    {
        $slug = strtolower(trim($slug));
        if (\in_array($slug, self::RESERVES, true)) {
            throw new \InvalidArgumentException('Cette adresse est réservée.');
        }
        $existant = $this->portfolios->findOneBy(['slug' => $slug]);
        if ($existant !== null && $existant !== $portfolio) {
            throw new \InvalidArgumentException('Cette adresse est déjà utilisée : choisissez-en une autre.');
        }
        $portfolio->setSlug($slug);
    }

    private function proposerSlug(Portfolio $portfolio): string
    {
        $etudiant = $portfolio->getEtudiant();
        $base = strtolower((new AsciiSlugger('fr'))->slug(trim($etudiant->getPrenom().' '.$etudiant->getNom()))->toString());
        $base = trim(substr($base, 0, 40), '-');
        if (\strlen($base) < 3 || \in_array($base, self::RESERVES, true)) {
            $base = 'portfolio';
        }
        $slug = $base;
        while ($this->portfolios->findOneBy(['slug' => $slug]) !== null || str_contains($slug, '--')) {
            $slug = $base.'-'.bin2hex(random_bytes(2));
        }

        return $slug;
    }
}
