<?php

namespace FinanceBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Services\FinancePdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FinancePdfController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FinancePdfService $pdfService
    ) {}

    #[Route('/api/finance_bon_commandes/{id}/pdf', name: 'api_finance_bon_commande_pdf', methods: ['GET'])]
    public function getBonCommandePdf(int $id): Response
    {
        $commande = $this->em->getRepository(FinanceBonCommande::class)->find($id);
        if (!$commande) {
            return new Response('Demande / Bon de commande non trouvé', Response::HTTP_NOT_FOUND);
        }

        try {
            $pdfContent = $this->pdfService->generatePdf($commande);
        } catch (\Exception $e) {
            return new Response('Erreur de génération : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $filename = sprintf(
            'bon_commande_%s_%s.pdf',
            $commande->getNumeroBonCommande() ?? 'demande_' . $commande->getId(),
            date('Ymd')
        );

        return new Response($pdfContent, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    #[Route('/api/finance_bon_commandes/{id}/html', name: 'api_finance_bon_commande_html', methods: ['GET'])]
    public function getBonCommandeHtml(int $id): Response
    {
        $commande = $this->em->getRepository(FinanceBonCommande::class)->find($id);
        if (!$commande) {
            return new Response('Demande / Bon de commande non trouvé', Response::HTTP_NOT_FOUND);
        }

        $html = $this->pdfService->renderHtml($commande);

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }
}
