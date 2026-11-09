<?php

namespace QuestionnaireBundle\Controller;

use App\Service\Pdf\PdfExportOptions;
use App\Service\Pdf\PdfExportService;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\SpreadsheetRenderer;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\ApiDto\Questionnaire\Analytics\QuestionnaireAnalyticsDto;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use QuestionnaireBundle\Services\Analytics\QuestionnaireAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

class QuestionnaireExportController extends AbstractController
{
    public function __construct(
        private readonly QuestionnaireResolver $questionnaires,
        private readonly QuestionnaireAnalyticsService $analyticsService,
        private readonly QuestionnaireAccess $access,
        private readonly SpreadsheetRenderer $spreadsheetRenderer,
        private readonly PdfExportService $pdfExportService,
    ) {
    }

    /**
     * Export Excel des statistiques (HtmlToSpreadsheetBundle).
     * ?sections=12,13 limite l'export à ces sections (restitution individuelle) ; un destinataire des résultats n'obtient
     * que les siennes.
     */
    #[Route('/api/questionnaires/{uuid}/export-excel', name: 'api_questionnaire_export_excel', methods: ['GET'])]
    public function excel(string $uuid, Request $request): Response
    {
        [$q, $analytics] = $this->resultats($uuid, $request);

        return $this->spreadsheetRenderer->renderFromTemplate(
            '@Questionnaire/export/analytics.xlsx.twig',
            ['questionnaire' => $q, 'analytics' => $analytics],
            'Statistiques_'.$this->safeName($q).'.xlsx'
        );
    }

    /**
     * Export PDF des statistiques (Gotenberg), éventuellement limité à des sections (?sections=12,13).
     * Les graphiques Chart.js rendus côté client peuvent être transmis en images : {"charts": {"<clé>": "data:image/png;base64,..."}}.
     */
    #[Route('/api/questionnaires/{uuid}/export-pdf', name: 'api_questionnaire_export_pdf', methods: ['POST'])]
    public function pdf(string $uuid, Request $request): Response
    {
        [$q, $analytics] = $this->resultats($uuid, $request);

        $payload = json_decode($request->getContent(), true);
        $charts = [];
        if (is_array($payload) && is_array($payload['charts'] ?? null)) {
            foreach ($payload['charts'] as $key => $image) {
                // N'accepte que des images PNG/JPEG en data URI
                if (is_string($image) && preg_match('#^data:image/(png|jpeg);base64,[A-Za-z0-9+/=]+$#', $image)) {
                    $charts[(string) $key] = $image;
                }
            }
        }

        $options = PdfExportOptions::create()
            ->setWithHeader(false)
            ->setWithFooter(true)
            ->setTitle('Statistiques - '.$q->getTitle())
            ->setMargins(1.0, 1.2, 0.8, 0.8);

        return $this->pdfExportService->generateStreamedResponse(
            '@Questionnaire/export/analytics.html.twig',
            ['questionnaire' => $q, 'analytics' => $analytics, 'charts' => $charts],
            'Statistiques_'.$this->safeName($q),
            $options
        );
    }

    /**
     * Les exports livrent les résultats : même droit que les statistiques (404 / 403), seuil d'anonymat atteint (409),
     * sections limitées à celles que la personne peut voir.
     *
     * @return array{0: Questionnaire, 1: QuestionnaireAnalyticsDto}
     */
    private function resultats(string $uuid, Request $request): array
    {
        $q = $this->questionnaires->charger($uuid, QuestionnaireVoter::SYNTHESE);
        $sections = $this->access->sectionsVisibles($this->getUser(), $q);
        $demandees = array_values(array_filter(array_map('intval', explode(',', (string) $request->query->get('sections', '')))));
        if ($demandees !== []) {
            $sections = $sections === null ? $demandees : array_values(array_intersect($sections, $demandees));
        }
        $analytics = $this->analyticsService->getAnalytics($q, $sections);
        if (!$analytics->seuilAtteint) {
            throw new ConflictHttpException(sprintf('Questionnaire anonyme : les résultats sont disponibles à partir de %d réponses envoyées.', $q->seuilResultats()));
        }

        return [$q, $analytics];
    }

    private function safeName(Questionnaire $q): string
    {
        return preg_replace('/[^a-zA-Z0-9]+/', '_', (string) $q->getTitle()) ?: 'questionnaire';
    }
}
