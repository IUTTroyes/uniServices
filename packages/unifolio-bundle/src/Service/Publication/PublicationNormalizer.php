<?php

namespace UnifolioBundle\Service\Publication;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPublication;
use UnifolioBundle\Enum\VisibilitePortfolioEnum;

class PublicationNormalizer
{
    public function __construct(private readonly UrlGeneratorInterface $urls)
    {
    }

    /** @return array<string, mixed> */
    public function resume(PortfolioPublication $p): array
    {
        return [
            'uuid' => $p->getUuidString(),
            'numero' => $p->getNumero(),
            'statut' => ['value' => $p->getStatut()->value, 'libelle' => $p->getStatut()->getLibelle()],
            'active' => $p->isActive(),
            'moderee' => $p->isModeree(),
            'motifModeration' => $p->getMotifModeration(),
            'erreur' => $p->getErreur(),
            'pages' => \count($p->getInstantane()['pages'] ?? []),
            'genereLe' => $p->getGenereLe()?->format(\DATE_ATOM),
            'creeLe' => $p->getCreatedAt()?->format(\DATE_ATOM),
        ];
    }

    /** Ligne de la liste de modération. */
    public function pourModeration(PortfolioPublication $p): array
    {
        $portfolio = $p->getPortfolio();
        $etudiant = $portfolio->getEtudiant();

        return $this->resume($p) + [
            'portfolio' => [
                'uuid' => $portfolio->getUuidString(),
                'titre' => $portfolio->getTitre(),
                'type' => ['value' => $portfolio->getType()->value, 'libelle' => $portfolio->getType()->getLibelle()],
                'visibilite' => ['value' => $portfolio->getVisibilite()->value, 'libelle' => $portfolio->getVisibilite()->getLibelle()],
                'etudiant' => trim($etudiant->getPrenom().' '.$etudiant->getNom()),
            ],
            'url' => $this->urlPublique($portfolio),
        ];
    }

    /** Réglages de partage — réservé au propriétaire (contient le secret du lien). */
    public function partage(Portfolio $portfolio, ?PortfolioPublication $active, array $historique, bool $renduDisponible): array
    {
        return [
            'visibilite' => ['value' => $portfolio->getVisibilite()->value, 'libelle' => $portfolio->getVisibilite()->getLibelle()],
            'visibilitesAutorisees' => array_map(
                static fn (VisibilitePortfolioEnum $v) => ['value' => $v->value, 'libelle' => $v->getLibelle()],
                $portfolio->getType()->visibilitesAutorisees()
            ),
            'slug' => $portfolio->getSlug(),
            'indexable' => $portfolio->isIndexable(),
            'description' => $portfolio->getDescription(),
            'url' => $this->urlPublique($portfolio),
            'apercu' => $this->urls->generate('unifolio_publication_apercu', ['uuid' => $portfolio->getUuidString(), 'fichier' => 'index.html'], UrlGeneratorInterface::ABSOLUTE_URL),
            'renduDisponible' => $renduDisponible,
            'publicationActive' => $active !== null ? $this->resume($active) : null,
            'historique' => array_map($this->resume(...), $historique),
        ];
    }

    /** Adresse à partager selon la visibilité courante (null si le portfolio n'est pas accessible hors compte). */
    public function urlPublique(Portfolio $portfolio): ?string
    {
        return match (true) {
            $portfolio->getVisibilite() === VisibilitePortfolioEnum::PUBLIC && $portfolio->getSlug() !== null => $this->urls->generate('unifolio_public_page', ['slug' => $portfolio->getSlug(), 'fichier' => 'index.html'], UrlGeneratorInterface::ABSOLUTE_URL),
            $portfolio->getVisibilite() === VisibilitePortfolioEnum::LIEN_SECRET && $portfolio->getTokenPartage() !== null => $this->urls->generate('unifolio_partage_page', ['token' => $portfolio->getTokenPartage(), 'fichier' => 'index.html'], UrlGeneratorInterface::ABSOLUTE_URL),
            $portfolio->getVisibilite() === VisibilitePortfolioEnum::ETABLISSEMENT => $this->urls->generate('unifolio_publication_apercu', ['uuid' => $portfolio->getUuidString(), 'fichier' => 'index.html'], UrlGeneratorInterface::ABSOLUTE_URL),
            default => null,
        };
    }
}
