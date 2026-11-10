<?php

namespace FinanceBundle\Controller;

use App\Repository\EtablissementRepository;
use App\Service\Pdf\PdfExportOptions;
use App\Service\Pdf\PdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Entity\FinanceBonCommande;
use App\Entity\Users\Personnel;
use FinanceBundle\Security\FinanceVoter;
use FinanceBundle\Service\Notification\FinanceNotifier;
use FinanceBundle\Workflow\ActionRefuseeException;
use FinanceBundle\Workflow\FinanceWorkflow;
use FinanceBundle\Service\FinanceDocumentStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Documents d'une demande d'achat : formulaire PDF généré (par étape) et document signé déposé.
 * Lecture : personnes qui voient la demande ; dépôt : demandeur avant l'avis, service financier (cf. FinanceAccess).
 */
#[\Symfony\Component\Security\Http\Attribute\IsGranted('IS_AUTHENTICATED_FULLY')]
class FinancePdfController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PdfExportService $pdfExportService,
        private readonly FinanceDocumentStorage $storage,
        private readonly EtablissementRepository $etablissements,
        private readonly FinanceWorkflow $workflow,
        private readonly FinanceNotifier $notifier,
    ) {
    }

    #[Route('/api/finance_bon_commandes/{id}/pdf', name: 'api_finance_bon_commande_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getBonCommandePdf(int $id, Request $request): Response
    {
        $commande = $this->commande($id);
        $this->denyAccessUnlessGranted(FinanceVoter::BC_VOIR, $commande);

        $step = $request->query->getInt('step', 5);
        if ($step < 1 || $step > 5) {
            $step = 5; // Document complet par défaut
        }

        $num = $commande->getNumeroBonCommande() ?: ('demande_'.$commande->getId());
        $filename = sprintf('demande_achat_%s_%s_etape_%d', preg_replace('/[^A-Za-z0-9_-]/', '', $num), date('Ymd'), $step);

        $options = PdfExportOptions::create()
            ->setWithHeader(false)
            ->setWithFooter(false)
            ->setInline(true)
            ->setMargins(0.8, 0.8, 0.8, 0.8);

        return $this->pdfExportService->generateStreamedResponse(
            '@Finance/pdf/formulaire_bon_commande.html.twig',
            ['commande' => $commande, 'step' => $step, 'etablissement' => $this->etablissement()],
            $filename,
            $options
        );
    }

    /**
     * Dépôt du document signé. Champs facultatifs : `decision` (favorable | defavorable) et `commentaire`.
     * Avec une décision, le dépôt vaut avis : favorable → la demande passe à la vérification financière, refus → la demande
     * est rejetée (motif obligatoire, document facultatif). Sans décision, le document est seulement enregistré.
     */
    #[Route('/api/finance_bon_commandes/{id}/upload-signed-pdf', name: 'api_finance_bon_commande_upload_signed_pdf', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadSignedPdf(int $id, Request $request): JsonResponse
    {
        $commande = $this->commande($id);
        $this->denyAccessUnlessGranted(FinanceVoter::BC_DEPOSER_DOCUMENT, $commande);
        $user = $this->getUser();
        \assert($user instanceof Personnel);

        $decision = $request->request->get('decision');
        $decision = \is_string($decision) && $decision !== '' ? $decision : null;
        $commentaire = (string) $request->request->get('commentaire', '');
        $fichier = $request->files->get('file');
        if ($fichier !== null && !$fichier instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Fichier invalide.'], Response::HTTP_BAD_REQUEST);
        }
        if ($fichier === null && $decision !== FinanceWorkflow::DECISION_DEFAVORABLE) {
            return new JsonResponse(['error' => 'Aucun fichier fourni'], Response::HTTP_BAD_REQUEST);
        }

        $action = null;
        try {
            if ($decision !== null) {
                $action = $this->workflow->appliquerDecisionDocument($user, $commande, $decision, $commentaire);
            }
        } catch (ActionRefuseeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->porteSurLesDroits() ? Response::HTTP_FORBIDDEN : Response::HTTP_CONFLICT);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ancien = $commande->getPdfFilePath();
        if ($fichier instanceof UploadedFile) {
            try {
                $chemin = $this->storage->enregistrer($fichier);
            } catch (\InvalidArgumentException $e) {
                return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY); // rien n'est enregistré
            }
            $commande->setPdfFilePath($chemin);
        }
        $this->em->flush();
        if ($fichier instanceof UploadedFile && $ancien !== null && $ancien !== $commande->getPdfFilePath()) {
            $this->storage->supprimer($ancien);
        }
        if ($action !== null) {
            $this->notifier->apresAction($commande, $action, $user);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Document PDF signé enregistré avec succès',
            'pdfSigne' => $commande->hasPdfSigne(),
            'statut' => $commande->getStatut()->value,
        ]);
    }

    #[Route('/api/finance_bon_commandes/{id}/download-signed-pdf', name: 'api_finance_bon_commande_download_signed_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadSignedPdf(int $id): Response
    {
        $commande = $this->commande($id);
        $this->denyAccessUnlessGranted(FinanceVoter::BC_VOIR, $commande);

        $fichier = $this->storage->localiser($commande->getPdfFilePath());
        if ($fichier === null) {
            throw new NotFoundHttpException('Aucun document signé n\'est associé à cette demande.');
        }

        $reponse = new BinaryFileResponse($fichier, Response::HTTP_OK, ['Content-Type' => FinanceDocumentStorage::MIME]);
        $reponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, sprintf('demande_%d_signee.pdf', $id));
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');

        return $reponse;
    }

    /** En-tête du formulaire : données de l'établissement principal (aucun nom en dur dans le gabarit). */
    private function etablissement(): array
    {
        $etab = $this->etablissements->findPrincipal();

        return [
            'libelle' => $etab?->getLibelle() ?? '',
            'universite' => $etab?->getUniversite()?->getLibelle() ?? '',
        ];
    }

    private function commande(int $id): FinanceBonCommande
    {
        $commande = $this->em->getRepository(FinanceBonCommande::class)->find($id);
        if (!$commande instanceof FinanceBonCommande) {
            throw new NotFoundHttpException('Demande / bon de commande non trouvé.');
        }

        return $commande;
    }
}
