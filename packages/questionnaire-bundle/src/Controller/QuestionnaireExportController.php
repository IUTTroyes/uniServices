<?php

namespace QuestionnaireBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Services\Analytics\QuestionnaireAnalyticsService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuestionnaireExportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireAnalyticsService $analyticsService
    ) {}

    public function __invoke(string $uuid): Response
    {
        $q = $this->em->getRepository(Questionnaire::class)->findOneBy(['uuid' => $uuid]);
        if (!$q) {
            throw new NotFoundHttpException('Questionnaire not found');
        }

        $analytics = $this->analyticsService->getAnalytics($q);

        $spreadsheet = new Spreadsheet();

        // 1. Sheet 1: General KPIs
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Vue d\'ensemble');

        $sheet->setCellValue('A1', 'Questionnaire : ' . $q->getTitle());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $sheet->setCellValue('A3', 'Métrique');
        $sheet->setCellValue('B3', 'Valeur');
        $sheet->getStyle('A3:B3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Réponses totales (complétées)');
        $sheet->setCellValue('B4', $analytics->totalResponses);

        $sheet->setCellValue('A5', 'Participants invités');
        $sheet->setCellValue('B5', $analytics->totalInvited);

        $sheet->setCellValue('A6', 'Taux de complétion');
        $sheet->setCellValue('B6', $analytics->completionRate / 100);
        $sheet->getStyle('B6')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_3);

        $sheet->setCellValue('A7', 'Temps moyen (secondes)');
        $sheet->setCellValue('B7', $analytics->averageTimeSpent);

        // Status counts
        $sheet->setCellValue('A10', 'Statut de participation');
        $sheet->setCellValue('B10', 'Nombre');
        $sheet->getStyle('A10:B10')->getFont()->setBold(true);

        $sheet->setCellValue('A11', 'Terminé');
        $sheet->setCellValue('B11', $analytics->statusCounts['submitted'] ?? 0);

        $sheet->setCellValue('A12', 'En cours');
        $sheet->setCellValue('B12', $analytics->statusCounts['started'] ?? 0);

        $sheet->setCellValue('A13', 'Non commencé');
        $sheet->setCellValue('B13', $analytics->statusCounts['pending'] ?? 0);

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // 2. Sheet 2: Detailed Stats
        $statsSheet = $spreadsheet->createSheet();
        $statsSheet->setTitle('Statistiques détaillées');

        $rowIdx = 1;
        foreach ($analytics->sections as $sec) {
            $statsSheet->setCellValue('A' . $rowIdx, 'Section : ' . $sec->sectionTitle);
            $statsSheet->getStyle('A' . $rowIdx)->getFont()->setBold(true)->setSize(14);
            $rowIdx += 2;

            foreach ($sec->questions as $qt) {
                $statsSheet->setCellValue('A' . $rowIdx, $qt->questionLabel . ' (' . $qt->questionType . ')');
                $statsSheet->getStyle('A' . $rowIdx)->getFont()->setBold(true)->setSize(11);
                $rowIdx++;

                $statsSheet->setCellValue('A' . $rowIdx, 'Total des réponses : ' . $qt->totalResponses);
                $rowIdx += 2;

                $stats = $qt->stats;
                if ($qt->questionType === 'single_choice' || $qt->questionType === 'multiple_choice') {
                    $statsSheet->setCellValue('A' . $rowIdx, 'Option');
                    $statsSheet->setCellValue('B' . $rowIdx, 'Nombre');
                    $statsSheet->setCellValue('C' . $rowIdx, 'Pourcentage');
                    $statsSheet->getStyle('A' . $rowIdx . ':C' . $rowIdx)->getFont()->setBold(true);
                    $rowIdx++;

                    $choices = $stats['choices'] ?? [];
                    foreach ($choices as $choice) {
                        $statsSheet->setCellValue('A' . $rowIdx, $choice['text']);
                        $statsSheet->setCellValue('B' . $rowIdx, $choice['count']);
                        $statsSheet->setCellValue('C' . $rowIdx, $choice['percentage'] / 100);
                        $statsSheet->getStyle('C' . $rowIdx)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_3);
                        $rowIdx++;
                    }
                } elseif ($qt->questionType === 'scale') {
                    $statsSheet->setCellValue('A' . $rowIdx, 'Note moyenne');
                    $statsSheet->setCellValue('B' . $rowIdx, $stats['average'] ?? 0);
                    $rowIdx += 2;

                    $statsSheet->setCellValue('A' . $rowIdx, 'Note');
                    $statsSheet->setCellValue('B' . $rowIdx, 'Nombre');
                    $statsSheet->getStyle('A' . $rowIdx . ':B' . $rowIdx)->getFont()->setBold(true);
                    $rowIdx++;

                    $dist = $stats['distribution'] ?? [];
                    foreach ($dist as $val => $cnt) {
                        $statsSheet->setCellValue('A' . $rowIdx, $val);
                        $statsSheet->setCellValue('B' . $rowIdx, $cnt);
                        $rowIdx++;
                    }
                } elseif ($qt->questionType === 'ranking') {
                    $statsSheet->setCellValue('A' . $rowIdx, 'Option');
                    $statsSheet->setCellValue('B' . $rowIdx, 'Rang moyen');
                    $statsSheet->getStyle('A' . $rowIdx . ':B' . $rowIdx)->getFont()->setBold(true);
                    $rowIdx++;

                    $ranking = $stats['ranking'] ?? [];
                    foreach ($ranking as $item) {
                        $statsSheet->setCellValue('A' . $rowIdx, $item['text']);
                        $statsSheet->setCellValue('B' . $rowIdx, $item['averageRank']);
                        $rowIdx++;
                    }
                } elseif ($qt->questionType === 'matrix') {
                    $rows = $stats['rows'] ?? [];
                    $cols = $stats['columns'] ?? [];
                    $grid = $stats['grid'] ?? [];

                    // Header
                    $colLetter = 'B';
                    foreach ($cols as $col) {
                        $statsSheet->setCellValue($colLetter . $rowIdx, $col);
                        $statsSheet->getStyle($colLetter . $rowIdx)->getFont()->setBold(true);
                        $colLetter++;
                    }
                    $rowIdx++;

                    foreach ($rows as $row) {
                        $statsSheet->setCellValue('A' . $rowIdx, $row);
                        $colLetter = 'B';
                        foreach ($cols as $col) {
                            $statsSheet->setCellValue($colLetter . $rowIdx, $grid[$row][$col] ?? 0);
                            $colLetter++;
                        }
                        $rowIdx++;
                    }
                } elseif ($qt->questionType === 'text_short' || $qt->questionType === 'text_long') {
                    $statsSheet->setCellValue('A' . $rowIdx, 'Longueur moyenne des caractères');
                    $statsSheet->setCellValue('B' . $rowIdx, $stats['averageLength'] ?? 0);
                    $rowIdx += 2;
                }

                $rowIdx += 2; // spacer between questions
            }
        }

        $statsSheet->getColumnDimension('A')->setAutoSize(true);
        $statsSheet->getColumnDimension('B')->setAutoSize(true);

        // 3. Sheet 3: Text comments (for reading through comments)
        $commentsSheet = $spreadsheet->createSheet();
        $commentsSheet->setTitle('Commentaires');

        $commentsSheet->setCellValue('A1', 'Section');
        $commentsSheet->setCellValue('B1', 'Question');
        $commentsSheet->setCellValue('C1', 'Réponse / Commentaire');
        $commentsSheet->getStyle('A1:C1')->getFont()->setBold(true);

        $commentRow = 2;
        foreach ($analytics->sections as $sec) {
            foreach ($sec->questions as $qt) {
                if ($qt->questionType === 'text_short' || $qt->questionType === 'text_long') {
                    $samples = $qt->stats['samples'] ?? [];
                    foreach ($samples as $sample) {
                        $commentsSheet->setCellValue('A' . $commentRow, $sec->sectionTitle);
                        $commentsSheet->setCellValue('B' . $commentRow, $qt->questionLabel);
                        $commentsSheet->setCellValue('C' . $commentRow, $sample);
                        $commentRow++;
                    }
                }
            }
        }

        $commentsSheet->getColumnDimension('A')->setAutoSize(true);
        $commentsSheet->getColumnDimension('B')->setAutoSize(true);
        $commentsSheet->getColumnDimension('C')->setWidth(80);

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $safeTitle = preg_replace('/[^a-zA-Z0-9]/', '_', $q->getTitle());
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Statistiques_' . $safeTitle . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
