<?php

namespace FinanceBundle\Controller;

use App\Service\Pdf\PdfExportOptions;
use App\Service\Pdf\PdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\StatutCommandeEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class FinancePdfController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PdfExportService $pdfExportService,
        private readonly KernelInterface $kernel,
        private readonly Environment $twig
    ) {}

    #[Route('/api/finance_bon_commandes/{id}/pdf', name: 'api_finance_bon_commande_pdf', methods: ['GET'])]
    public function getBonCommandePdf(int $id, Request $request): Response
    {
        $commande = $this->em->getRepository(FinanceBonCommande::class)->find($id);
        if (!$commande) {
            return new Response('Demande / Bon de commande non trouvé', Response::HTTP_NOT_FOUND);
        }

        $step = $request->query->getInt('step', 0);
        if ($step < 1 || $step > 5) {
            $step = 5; // Document complet par défaut
        }

        $num = $commande->getNumeroBonCommande() ?: ('demande_' . $commande->getId());
        $filename = sprintf('demande_achat_%s_%s%s', $num, date('Ymd'), $step > 0 ? ('_etape_' . $step) : '');

        $options = PdfExportOptions::create()
            ->setWithHeader(false)
            ->setWithFooter(false)
            ->setInline(true)
            ->setMargins(0.8, 0.8, 0.8, 0.8);

        return $this->pdfExportService->generateStreamedResponse(
            '@Finance/pdf/formulaire_bon_commande.html.twig',
            [
                'commande' => $commande,
                'step' => $step,
            ],
            $filename,
            $options
        );
    }

    #[Route('/api/finance_bon_commandes/{id}/upload-signed-pdf', name: 'api_finance_bon_commande_upload_signed_pdf', methods: ['POST'])]
    public function uploadSignedPdf(int $id, Request $request): JsonResponse
    {
        $commande = $this->em->getRepository(FinanceBonCommande::class)->find($id);
        if (!$commande) {
            return new JsonResponse(['error' => 'Demande / Bon de commande non trouvé'], Response::HTTP_NOT_FOUND);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier fourni'], Response::HTTP_BAD_REQUEST);
        }

        $mimeType = $file->getMimeType();
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($mimeType !== 'application/pdf' && $ext !== 'pdf') {
            return new JsonResponse(['error' => 'Le fichier doit obligatoirement être un document PDF'], Response::HTTP_BAD_REQUEST);
        }

        $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/finance';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $fileName = sprintf('demande_%d_signee_%s.pdf', $id, uniqid());
        $file->move($uploadDir, $fileName);

        $relativePath = 'uploads/finance/' . $fileName;
        $commande->setPdfFilePath($relativePath);

        // Si la commande était en brouillon, elle passe à SOUMIS
        if ($commande->getStatut() === StatutCommandeEnum::BROUILLON) {
            $commande->setStatut(StatutCommandeEnum::SOUMIS);
        }

        if (!$commande->getDateSignatureDemandeur()) {
            $commande->setDateSignatureDemandeur(new \DateTime());
        }

        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Document PDF signé enregistré avec succès',
            'pdfFilePath' => $relativePath,
            'statut' => $commande->getStatut()->value,
        ]);
    }

    #[Route('/api/finance_bon_commandes/{id}/download-signed-pdf', name: 'api_finance_bon_commande_download_signed_pdf', methods: ['GET'])]
    public function downloadSignedPdf(int $id): Response
    {
        $commande = $this->em->getRepository(FinanceBonCommande::class)->find($id);
        if (!$commande) {
            return new Response('Demande / Bon de commande non trouvé', Response::HTTP_NOT_FOUND);
        }

        $relPath = $commande->getPdfFilePath();
        if (!$relPath) {
            return new Response('Aucun document signé n\'est associé à cette demande', Response::HTTP_NOT_FOUND);
        }

        $fullPath = $this->kernel->getProjectDir() . '/public/' . ltrim($relPath, '/');
        if (!file_exists($fullPath)) {
            return new Response('Fichier introuvable sur le serveur', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($fullPath, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="demande_%d_signee.pdf"', $id),
        ]);
    }

    #[Route('/api/finance_bon_commandes/{id}/html', name: 'api_finance_bon_commande_html', methods: ['GET'])]
    public function getBonCommandeHtml(int $id, Request $request): Response
    {
        $commande = $this->em->getRepository(FinanceBonCommande::class)->find($id);
        if (!$commande) {
            return new Response('Demande / Bon de commande non trouvé', Response::HTTP_NOT_FOUND);
        }

        $step = $request->query->getInt('step', 0);
        if ($step < 1 || $step > 5) {
            $step = 5;
        }

        $html = $this->twig->render('@Finance/pdf/formulaire_bon_commande.html.twig', [
            'commande' => $commande,
            'step' => $step,
        ]);

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }
}
