<?php

namespace UnifolioBundle\Controller;

use App\Entity\Users\Etudiant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPage;
use UnifolioBundle\Repository\PortfolioRepository;
use UnifolioBundle\Security\PortfolioVoter;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Portfolio\BlocRegistry;
use UnifolioBundle\Service\Portfolio\PortfolioDonnees;
use UnifolioBundle\Service\Portfolio\PortfolioManager;
use UnifolioBundle\Service\Portfolio\PortfolioNormalizer;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Portfolios de l'étudiant : liste, pages, blocs. Le portfolio académique est créé à la volée. */
#[Route('/api/unifolio/portfolios')]
#[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
class PortfolioController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly PortfolioRepository $portfolios,
        private readonly PortfolioManager $manager,
        private readonly PortfolioNormalizer $normalizer,
        private readonly PortfolioDonnees $donnees,
        private readonly BlocRegistry $registre,
    ) {
    }

    /** Schéma des blocs : types, attributs, imbrication — l'éditeur s'en sert pour se construire. */
    #[Route('/schema-blocs', name: 'unifolio_portfolios_schema', methods: ['GET'])]
    public function schema(): JsonResponse
    {
        return $this->json($this->registre->schema());
    }

    #[Route('', name: 'unifolio_portfolios_liste', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        $etudiant = $this->etudiant();
        $this->manager->assurerAcademiques($etudiant);

        return $this->json(array_map($this->normalizer->resume(...), $this->portfolios->findParEtudiant($etudiant)));
    }

    #[Route('/{uuid}', name: 'unifolio_portfolios_detail', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function detail(string $uuid): JsonResponse
    {
        return $this->json($this->normalizer->detail($this->portfolio($uuid, PortfolioVoter::VOIR)));
    }

    /** Données de rendu des blocs « métier » (traces, progression, CV, parcours). */
    #[Route('/{uuid}/donnees', name: 'unifolio_portfolios_donnees', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    public function donnees(string $uuid): JsonResponse
    {
        return $this->json($this->donnees->pour($this->portfolio($uuid, PortfolioVoter::VOIR)));
    }

    /** { titre } */
    #[Route('/{uuid}', name: 'unifolio_portfolios_renommer', requirements: ['uuid' => self::UUID], methods: ['PATCH'])]
    public function renommer(string $uuid, Request $request): JsonResponse
    {
        $portfolio = $this->manager->renommer($this->portfolio($uuid, PortfolioVoter::MODIFIER), (string) ($this->corps($request)['titre'] ?? ''));

        return $this->json($this->normalizer->resume($portfolio));
    }

    /** { titre } */
    #[Route('/{uuid}/pages', name: 'unifolio_portfolios_page_ajouter', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    public function ajouterPage(string $uuid, Request $request): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $page = $this->manager->ajouterPage($portfolio, (string) ($this->corps($request)['titre'] ?? 'Nouvelle page'));

        return $this->json($this->normalizer->page($page), Response::HTTP_CREATED);
    }

    /** { uuids: [string] } — nouvel ordre des pages. */
    #[Route('/{uuid}/pages/ordre', name: 'unifolio_portfolios_pages_ordre', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    public function ordre(string $uuid, Request $request): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $uuids = $this->corps($request)['uuids'] ?? [];
        $this->manager->reordonner($portfolio, \is_array($uuids) ? array_map('strval', $uuids) : []);

        return $this->json($this->normalizer->detail($portfolio));
    }

    /** { titre } */
    #[Route('/{uuid}/pages/{page}', name: 'unifolio_portfolios_page_renommer', requirements: ['uuid' => self::UUID, 'page' => self::UUID], methods: ['PATCH'])]
    public function renommerPage(string $uuid, string $page, Request $request): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $p = $this->manager->renommerPage($portfolio, $this->page($portfolio, $page), (string) ($this->corps($request)['titre'] ?? ''));

        return $this->json($this->normalizer->page($p));
    }

    #[Route('/{uuid}/pages/{page}', name: 'unifolio_portfolios_page_supprimer', requirements: ['uuid' => self::UUID, 'page' => self::UUID], methods: ['DELETE'])]
    public function supprimerPage(string $uuid, string $page): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $this->manager->supprimerPage($portfolio, $this->page($portfolio, $page));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /** { blocs: [...], version: int } — validation complète puis remplacement du contenu de la page. */
    #[Route('/{uuid}/pages/{page}/blocs', name: 'unifolio_portfolios_page_blocs', requirements: ['uuid' => self::UUID, 'page' => self::UUID], methods: ['PUT'])]
    public function blocs(string $uuid, string $page, Request $request): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $p = $this->page($portfolio, $page);
        $d = $this->corps($request);
        if (!isset($d['version']) || !is_numeric($d['version'])) {
            throw UnifolioValidationException::un('Version de la page manquante.');
        }
        $this->manager->enregistrerBlocs($portfolio, $p, $d['blocs'] ?? null, (int) $d['version']);

        return $this->json($this->normalizer->page($p));
    }

    private function portfolio(string $uuid, string $droit): Portfolio
    {
        $portfolio = $this->portfolios->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Portfolio introuvable.');
        $this->denyAccessUnlessGranted($droit, $portfolio);

        return $portfolio;
    }

    private function page(Portfolio $portfolio, string $uuid): PortfolioPage
    {
        return $portfolio->getPage($uuid) ?? throw $this->createNotFoundException('Page introuvable.');
    }

    /** @return array<string, mixed> */
    private function corps(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            throw UnifolioValidationException::un('Requête JSON invalide.');
        }
    }

    private function etudiant(): Etudiant
    {
        $user = $this->getUser();
        \assert($user instanceof Etudiant);

        return $user;
    }
}
