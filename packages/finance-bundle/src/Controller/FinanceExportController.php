<?php

namespace FinanceBundle\Controller;

use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\SpreadsheetRenderer;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Repository\FinanceBonCommandeRepository;
use FinanceBundle\Security\FinanceVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Export Excel (HtmlToSpreadsheetBundle) du tableau des demandes d'achat.
 * Le client envoie les identifiants des lignes telles qu'affichées (filtres et tri appliqués) ; le serveur recharge
 * les demandes et n'exporte que celles que l'utilisateur a le droit de voir, dans l'ordre reçu.
 */
class FinanceExportController extends AbstractController
{
    public const MAX_LIGNES = 5000;

    public function __construct(
        private readonly FinanceBonCommandeRepository $commandes,
        private readonly SpreadsheetRenderer $spreadsheetRenderer,
    ) {
    }

    #[Route('/api/finance_bon_commandes/export-excel', name: 'api_finance_bon_commandes_export_excel', methods: ['POST'], priority: 10)]
    public function excel(Request $request): Response
    {
        // Chaque demande exportée est autorisée par le voter local sur son département.
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $payload = json_decode((string) $request->getContent(), true);
        $ids = \is_array($payload) && \is_array($payload['ids'] ?? null) ? $payload['ids'] : null;
        if ($ids === null) {
            return new JsonResponse(['error' => 'Liste d\'identifiants attendue : {"ids": [1, 2, …]}.'], Response::HTTP_BAD_REQUEST);
        }
        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => \is_int($id) || (\is_string($id) && ctype_digit($id)) ? (int) $id : 0,
            $ids
        ), static fn (int $id): bool => $id > 0)));
        if (\count($ids) > self::MAX_LIGNES) {
            return new JsonResponse(['error' => sprintf('Export limité à %d demandes.', self::MAX_LIGNES)], Response::HTTP_BAD_REQUEST);
        }

        $parId = [];
        foreach ($ids === [] ? [] : $this->commandes->findBy(['id' => $ids]) as $commande) {
            $parId[$commande->getId()] = $commande;
        }
        $lignes = [];
        foreach ($ids as $id) {
            $commande = $parId[$id] ?? null;
            if ($commande instanceof FinanceBonCommande && $this->isGranted(FinanceVoter::BC_VOIR, $commande)) {
                $lignes[] = $commande;
            }
        }

        return $this->spreadsheetRenderer->renderFromTemplate(
            '@Finance/export/demandes.xlsx.twig',
            ['commandes' => $lignes, 'date' => new \DateTimeImmutable()],
            sprintf('demandes_achat_%s.xlsx', date('Ymd'))
        );
    }
}
