<?php

namespace UnifolioBundle\Controller;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Sensiolabs\GotenbergBundle\GotenbergPdfInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPublication;
use UnifolioBundle\Enum\TypePortfolioEnum;
use UnifolioBundle\Enum\VisibilitePortfolioEnum;
use UnifolioBundle\Repository\PortfolioPublicationRepository;
use UnifolioBundle\Repository\PortfolioRepository;
use UnifolioBundle\Security\PortfolioVoter;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Portfolio\PortfolioManager;
use UnifolioBundle\Service\Portfolio\PortfolioNormalizer;
use UnifolioBundle\Service\Publication\LogoManager;
use UnifolioBundle\Service\Publication\PartageManager;
use UnifolioBundle\Service\Publication\ThemeNormalizer;
use UnifolioBundle\Service\Publication\PublicationManager;
use UnifolioBundle\Service\Publication\PublicationNormalizer;
use UnifolioBundle\Service\Publication\PublicationStorage;
use UnifolioBundle\Service\Publication\RendererClient;
use UnifolioBundle\Service\Publication\VitrineManager;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Vitrine, partage, publication et exports d'un portfolio (côté étudiant) + consultation authentifiée des publications. */
#[Route('/api/unifolio/portfolios')]
class PublicationController extends AbstractController
{
    private const UUID = '[0-9a-fA-F-]{36}';

    public function __construct(
        private readonly PortfolioRepository $portfolios,
        private readonly PortfolioPublicationRepository $publications,
        private readonly PortfolioNormalizer $normalizer,
        private readonly PublicationNormalizer $publicationNormalizer,
        private readonly PartageManager $partage,
        private readonly PublicationManager $manager,
        private readonly VitrineManager $vitrines,
        private readonly PublicationStorage $stockage,
        private readonly RendererClient $rendu,
        private readonly GotenbergPdfInterface $gotenberg,
        private readonly ThemeNormalizer $themes,
        private readonly LogoManager $logos,
        private readonly \Doctrine\ORM\EntityManagerInterface $em,
    ) {
    }

    // ---------- Vitrine ----------

    #[Route('/vitrine', name: 'unifolio_portfolios_vitrine', methods: ['POST'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function creerVitrine(Request $request): JsonResponse
    {
        $d = $this->corps($request);
        $portfolio = $this->vitrines->creer($this->etudiant(), isset($d['titre']) ? (string) $d['titre'] : null);

        return $this->json($this->normalizer->detail($portfolio), Response::HTTP_CREATED);
    }

    #[Route('/{uuid}/copier-vers-vitrine', name: 'unifolio_portfolios_copier', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function copierVersVitrine(string $uuid, Request $request): JsonResponse
    {
        $source = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $vitrine = $this->vitrines->trouver($this->etudiant()) ?? $this->vitrines->creer($this->etudiant());
        $pages = $this->corps($request)['pages'] ?? [];
        $nb = $this->vitrines->copier($source, $vitrine, \is_array($pages) ? array_map('strval', $pages) : []);

        return $this->json(['copiees' => $nb, 'vitrine' => $this->normalizer->resume($vitrine)]);
    }

    // ---------- Partage et publication ----------

    #[Route('/{uuid}/partage', name: 'unifolio_portfolios_partage', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function lirePartage(string $uuid): JsonResponse
    {
        return $this->json($this->etatPartage($this->portfolio($uuid, PortfolioVoter::MODIFIER)));
    }

    #[Route('/{uuid}/partage', name: 'unifolio_portfolios_partage_modifier', requirements: ['uuid' => self::UUID], methods: ['PATCH'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function modifierPartage(string $uuid, Request $request): JsonResponse
    {
        $portfolio = $this->partage->configurer($this->portfolio($uuid, PortfolioVoter::MODIFIER), $this->corps($request));

        return $this->json($this->etatPartage($portfolio));
    }

    #[Route('/{uuid}/publier', name: 'unifolio_portfolios_publier', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function publier(string $uuid): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $this->manager->publier($portfolio);

        return $this->json($this->etatPartage($portfolio), Response::HTTP_CREATED);
    }

    #[Route('/{uuid}/publication', name: 'unifolio_portfolios_depublier', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function depublier(string $uuid): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $this->manager->depublier($portfolio);

        return $this->json($this->etatPartage($portfolio));
    }

    // ---------- Apparence et logo ----------

    #[Route('/{uuid}/apparence', name: 'unifolio_portfolios_apparence', requirements: ['uuid' => self::UUID], methods: ['PUT'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function apparence(string $uuid, Request $request): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        try {
            $portfolio->setTheme($this->themes->normaliser($this->corps($request)));
        } catch (\InvalidArgumentException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
        $this->em->flush();

        return $this->json($this->normalizer->detail($portfolio));
    }

    #[Route('/{uuid}/logo', name: 'unifolio_portfolios_logo_definir', requirements: ['uuid' => self::UUID], methods: ['POST'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function definirLogo(string $uuid, Request $request): JsonResponse
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $fichier = $request->files->get('logo');
        if (!$fichier instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            throw UnifolioValidationException::un('Aucun fichier reçu.');
        }

        return $this->json($this->normalizer->detail($this->logos->definir($portfolio, $fichier)));
    }

    #[Route('/{uuid}/logo', name: 'unifolio_portfolios_logo_retirer', requirements: ['uuid' => self::UUID], methods: ['DELETE'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function retirerLogo(string $uuid): JsonResponse
    {
        return $this->json($this->normalizer->detail($this->logos->retirer($this->portfolio($uuid, PortfolioVoter::MODIFIER))));
    }

    /** Logo courant (aperçu dans l'éditeur) : propriétaire uniquement, isolé comme tout fichier déposé. */
    #[Route('/{uuid}/logo', name: 'unifolio_portfolios_logo', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function logo(string $uuid): Response
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $chemin = $this->logos->chemin($portfolio) ?? throw $this->createNotFoundException();
        $reponse = new BinaryFileResponse($chemin);
        $reponse->headers->set('Content-Type', (string) $portfolio->getLogoMime());
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');
        $reponse->headers->set('Content-Security-Policy', "sandbox; default-src 'none'");
        $reponse->headers->set('Cache-Control', 'private, no-cache');

        return $reponse;
    }

    // ---------- Consultation authentifiée ----------

    /**
     * Aperçu pour le propriétaire (même privé), consultation « établissement » pour tout compte connecté,
     * ou contrôle par un référent. Jamais de cache partagé.
     */
    #[Route('/{uuid}/publication/{fichier}', name: 'unifolio_publication_apercu', requirements: ['uuid' => self::UUID, 'fichier' => 'index\.html|[a-zA-Z0-9][a-zA-Z0-9._-]{0,120}|media/[a-zA-Z0-9][a-zA-Z0-9._-]{0,120}'], methods: ['GET'])]
    public function apercu(string $uuid, string $fichier): Response
    {
        $portfolio = $this->portfolios->findOneByUuid($uuid) ?? throw $this->createNotFoundException();
        $user = $this->getUser();
        $publication = match (true) {
            $user instanceof Etudiant && $portfolio->appartientA($user) => $this->publications->findActive($portfolio) ?? $this->publications->findDerniereGeneree($portfolio),
            $user instanceof Personnel && $this->isGranted(PortfolioVoter::VOIR, $portfolio) => $this->publications->findActive($portfolio),
            ($user instanceof Etudiant || $user instanceof Personnel) && $portfolio->getVisibilite() === VisibilitePortfolioEnum::ETABLISSEMENT => $this->publications->findActive($portfolio),
            default => null,
        };
        $proprietaire = $user instanceof Etudiant && $portfolio->appartientA($user);
        if ($publication === null || ($publication->isModeree() && !$proprietaire)) {
            throw $this->createNotFoundException();
        }

        return PublicPortfolioController::reponseFichier($this->stockage, $publication, $fichier, false, false);
    }

    // ---------- Exports (propriétaire) ----------

    #[Route('/{uuid}/export.zip', name: 'unifolio_portfolios_export_zip', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function exportZip(string $uuid): Response
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $publication = $this->derniere($portfolio);
        $tmp = tempnam(sys_get_temp_dir(), 'pfzip');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            throw UnifolioValidationException::un('Impossible de préparer l\'archive.');
        }
        foreach ($publication->getFichiers() as $fichier) {
            $chemin = $this->stockage->chemin($publication, $fichier);
            if ($chemin !== null && $fichier !== 'print.html') {
                $zip->addFile($chemin, $fichier);
            }
        }
        $zip->close();

        $reponse = new BinaryFileResponse($tmp);
        $reponse->deleteFileAfterSend(true);
        $reponse->setContentDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $this->nomExport($portfolio).'.zip');
        $reponse->headers->set('Content-Type', 'application/zip');
        $reponse->headers->set('Cache-Control', 'private, no-store');

        return $reponse;
    }

    #[Route('/{uuid}/export.pdf', name: 'unifolio_portfolios_export_pdf', requirements: ['uuid' => self::UUID], methods: ['GET'])]
    #[IsGranted(UnifolioVoter::ESPACE_ETUDIANT)]
    public function exportPdf(string $uuid): Response
    {
        $portfolio = $this->portfolio($uuid, PortfolioVoter::MODIFIER);
        $publication = $this->derniere($portfolio);
        $html = $this->stockage->lire($publication, 'print.html') ?? throw UnifolioValidationException::un('Cette publication ne peut pas être exportée en PDF.');

        // Gotenberg reçoit tous les fichiers « à plat » : on aplatit les adresses media/… de la copie d'impression.
        $dossier = sys_get_temp_dir().'/pfpdf-'.bin2hex(random_bytes(6));
        $fs = new Filesystem();
        $fs->mkdir($dossier);
        try {
            $assets = [];
            $fs->dumpFile($dossier.'/index.html', str_replace(['src="media/', 'href="media/'], ['src="', 'href="'], $html));
            foreach ($publication->getFichiers() as $fichier) {
                $chemin = $this->stockage->chemin($publication, $fichier);
                if ($chemin !== null && str_starts_with($fichier, 'media/')) {
                    $fs->copy($chemin, $dossier.'/'.basename($fichier));
                    $assets[] = $dossier.'/'.basename($fichier);
                }
            }
            $css = $this->stockage->chemin($publication, PublicationStorage::FEUILLE);
            if ($css !== null) {
                $fs->copy($css, $dossier.'/'.PublicationStorage::FEUILLE);
                $assets[] = $dossier.'/'.PublicationStorage::FEUILLE;
            }
            $builder = $this->gotenberg->html()->contentFile($dossier.'/index.html')->printBackground(true)->fileName($this->nomExport($portfolio), HeaderUtils::DISPOSITION_ATTACHMENT);
            if ($assets !== []) {
                $builder->assets(...$assets);
            }

            return $builder->generate()->stream();
        } catch (UnifolioValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw UnifolioValidationException::un('Le service de génération PDF est indisponible : réessayez plus tard.');
        } finally {
            $fs->remove($dossier);
        }
    }

    // ---------- Internes ----------

    /** @return array<string, mixed> */
    private function etatPartage(Portfolio $portfolio): array
    {
        $etat = $this->publicationNormalizer->partage(
            $portfolio,
            $this->publications->findActive($portfolio),
            $this->publications->historique($portfolio),
            $this->rendu->disponible()
        );
        // Le secret du lien n'est exposé qu'au propriétaire, et seulement en visibilité « lien secret » (déjà contenu dans l'URL).
        $etat['peutPublier'] = $portfolio->getVisibilite() !== VisibilitePortfolioEnum::PRIVE;
        $etat['type'] = $portfolio->getType() === TypePortfolioEnum::VITRINE ? 'vitrine' : 'academique';

        return $etat;
    }

    private function derniere(Portfolio $portfolio): PortfolioPublication
    {
        return $this->publications->findDerniereGeneree($portfolio)
            ?? throw UnifolioValidationException::un('Publiez d\'abord ce portfolio pour pouvoir l\'exporter.');
    }

    private function nomExport(Portfolio $portfolio): string
    {
        return 'portfolio-'.($portfolio->getSlug() ?? substr($portfolio->getUuidString(), 0, 8));
    }

    private function portfolio(string $uuid, string $droit): Portfolio
    {
        $portfolio = $this->portfolios->findOneByUuid($uuid) ?? throw $this->createNotFoundException('Portfolio introuvable.');
        $this->denyAccessUnlessGranted($droit, $portfolio);

        return $portfolio;
    }

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
