<?php

namespace UnifolioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPublication;
use UnifolioBundle\Enum\VisibilitePortfolioEnum;
use UnifolioBundle\Repository\PortfolioPublicationRepository;
use UnifolioBundle\Repository\PortfolioRepository;
use UnifolioBundle\Service\Publication\PublicationStorage;

/**
 * Service des portfolios publiés, sans authentification : visibilité « public » (par adresse) ou « lien secret » (par jeton).
 * La visibilité est relue sur le portfolio à chaque requête : repasser en « privé » coupe l'accès immédiatement.
 * Toute réponse inconnue, privée ou modérée est un 404 identique (aucune fuite d'existence).
 */
#[Route('/api/unifolio')]
class PublicPortfolioController extends AbstractController
{
    private const FICHIER = 'index\.html|[a-zA-Z0-9][a-zA-Z0-9._-]{0,120}|media/[a-zA-Z0-9][a-zA-Z0-9._-]{0,120}';

    public function __construct(
        private readonly PortfolioRepository $portfolios,
        private readonly PortfolioPublicationRepository $publications,
        private readonly PublicationStorage $stockage,
    ) {
    }

    #[Route('/public/sitemap.xml', name: 'unifolio_public_sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        $urls = [];
        foreach ($this->publications->findPourSitemap() as $publication) {
            $loc = $this->generateUrl('unifolio_public_page', ['slug' => $publication->getPortfolio()->getSlug(), 'fichier' => 'index.html'], UrlGeneratorInterface::ABSOLUTE_URL);
            $urls[] = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod></url>', htmlspecialchars($loc, \ENT_XML1 | \ENT_QUOTES), ($publication->getGenereLe() ?? new \DateTimeImmutable())->format('Y-m-d'));
        }

        return new Response(
            '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.implode('', $urls).'</urlset>',
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=utf-8', 'Cache-Control' => 'public, max-age=3600']
        );
    }

    #[Route('/public/{slug}', name: 'unifolio_public_racine', requirements: ['slug' => '[a-z0-9-]{3,60}'], methods: ['GET'])]
    public function racinePublique(string $slug): RedirectResponse
    {
        return $this->redirectToRoute('unifolio_public_page', ['slug' => $slug, 'fichier' => 'index.html'], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/public/{slug}/{fichier}', name: 'unifolio_public_page', requirements: ['slug' => '[a-z0-9-]{3,60}', 'fichier' => self::FICHIER], methods: ['GET'])]
    public function pagePublique(string $slug, string $fichier): Response
    {
        $portfolio = $this->portfolios->findOneBy(['slug' => $slug]);
        if ($portfolio === null || $portfolio->getVisibilite() !== VisibilitePortfolioEnum::PUBLIC) {
            throw $this->createNotFoundException();
        }

        return $this->servir($portfolio, $fichier, true);
    }

    #[Route('/partage/{token}', name: 'unifolio_partage_racine', requirements: ['token' => '[a-f0-9]{48}'], methods: ['GET'])]
    public function racinePartage(string $token): RedirectResponse
    {
        return $this->redirectToRoute('unifolio_partage_page', ['token' => $token, 'fichier' => 'index.html'], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/partage/{token}/{fichier}', name: 'unifolio_partage_page', requirements: ['token' => '[a-f0-9]{48}', 'fichier' => self::FICHIER], methods: ['GET'])]
    public function pagePartage(string $token, string $fichier): Response
    {
        $portfolio = $this->portfolios->findOneBy(['tokenPartage' => $token]);
        if ($portfolio === null || $portfolio->getVisibilite() !== VisibilitePortfolioEnum::LIEN_SECRET) {
            throw $this->createNotFoundException();
        }

        return $this->servir($portfolio, $fichier, false);
    }

    private function servir(Portfolio $portfolio, string $fichier, bool $public): Response
    {
        $publication = $this->publications->findActive($portfolio);
        if ($publication === null || $publication->isModeree()) {
            throw $this->createNotFoundException();
        }

        return self::reponseFichier($this->stockage, $publication, $fichier, $public, $public && $portfolio->isIndexable());
    }

    /**
     * Réponse sûre pour un fichier de publication. Les pages HTML n'exécutent aucun script (CSP) ; les fichiers déposés par
     * l'étudiant sont isolés (sandbox, nosniff) et seuls les formats d'image/audio/vidéo sont affichés en ligne.
     */
    public static function reponseFichier(PublicationStorage $stockage, PortfolioPublication $publication, string $fichier, bool $cachePublic, bool $indexable): Response
    {
        $chemin = $publication->aFichier($fichier) ? $stockage->chemin($publication, $fichier) : null;
        if ($chemin === null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $extension = strtolower(pathinfo($fichier, \PATHINFO_EXTENSION));
        $types = [
            'html' => 'text/html; charset=utf-8', 'css' => 'text/css; charset=utf-8',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif',
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mp3' => 'audio/mpeg',
        ];
        $type = $types[$extension] ?? 'application/octet-stream';
        $reponse = new BinaryFileResponse($chemin);
        $reponse->headers->set('Content-Type', $type);
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');
        $reponse->headers->set('Referrer-Policy', 'no-referrer');
        if (!isset($types[$extension])) {
            $reponse->setContentDisposition('attachment', 'fichier.'.($extension ?: 'bin'));
        }
        if ($extension === 'html') {
            $reponse->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self' https: data:; media-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self' data:; frame-src https:; base-uri 'none'; form-action 'none'");
        } elseif ($extension !== 'css') {
            $reponse->headers->set('Content-Security-Policy', "sandbox; default-src 'none'");
        }
        if (!$indexable) {
            $reponse->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        $reponse->headers->set('Cache-Control', $cachePublic ? 'public, max-age=300' : 'private, no-store');

        return $reponse;
    }
}
