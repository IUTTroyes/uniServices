<?php

declare(strict_types=1);

namespace App\Controller;

use Davidannebicque\HtmlToSpreadsheetBundle\Controller\SpreadsheetTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ExportTablerController extends AbstractController
{
    use SpreadsheetTrait;

    #[Route('/export', name: 'app_export', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        return new Response('OK');
    }

    #[Route('/export/download', name: 'app_export_download')]
    public function download($data, $type): Response
    {
        return $this->renderSpreadsheet(
            'export_tabler/'.$type.'.html.twig',
            ['sheets' => $data],
            'export_previsionnel.xlsx'
        );
    }

    // Endpoint pour traiter les données prévisionnelles et construire le tableau d'export
    #[Route('/export/previ', name: 'app_export_previ', methods: ['POST'])]
    public function exportPrevi(Request $request): Response
    {
        $content = (string) $request->getContent();
        $data = json_decode($content, true) ?? [];
//        dd($data);

        // Passer les données brutes au template Twig, la construction des feuilles se fait côté vue
        return $this->renderSpreadsheet(
            'export_tabler/previ.html.twig',
            ['stats' => $data],
            'export_previsionnel.xlsx'
        );
    }

    // Endpoint pour traiter les données prévisionnelles et construire le tableau d'export
    #[Route('/export/edt-heures', name: 'app_export_edt_heures', methods: ['POST'])]
    public function exportHeures(Request $request): Response
    {
        $content = (string) $request->getContent();
        $allData = json_decode($content, true) ?? [];

        $source = $allData['export_data'] ?? [];

        // Grouper les événements par semestre
        $bySemestre = [];
        foreach ($source as $item) {
            $semestre = $item['semestre'] ?? 'Inconnu';
            $bySemestre[$semestre][] = $item;
        }

        // Trier les événements par date puis heure de début au sein de chaque semestre
        foreach ($bySemestre as $semestre => &$events) {
            usort($events, function ($a, $b) {
                $dateA = $a['date'] ?? '';
                $dateB = $b['date'] ?? '';
                if ($dateA === $dateB) {
                    // Si les dates sont identiques, trier par heure de début
                    return strcmp($a['debut'] ?? '', $b['debut'] ?? '');
                }
                return strcmp($dateA, $dateB);
            });
        }
        unset($events);

        // Calculer le total d'heures par semestre (à partir de date+debut/fin)
        $totalHeuresParSemestre = [];
        foreach ($bySemestre as $semestre => $events) {
            $total = 0.0;
            foreach ($events as $ev) {
                $date = $ev['date'] ?? null;
                $debut = $ev['debut'] ?? null;
                $fin = $ev['fin'] ?? null;
                if ($debut && $fin) {
                    if ($date) {
                        $startTs = strtotime($date.' '.$debut);
                        $endTs = strtotime($date.' '.$fin);
                    } else {
                        $startTs = strtotime((string)$debut);
                        $endTs = strtotime((string)$fin);
                    }
                    if ($startTs && $endTs && $endTs > $startTs) {
                        $total += ($endTs - $startTs) / 3600.0;
                    }
                }
            }
            $totalHeuresParSemestre[$semestre] = round($total, 2);
        }

        // Construire les données pour la vue (préserver l'ordre d'insertion des semestres)
        $data = [
            'by_semestre' => $bySemestre,
            'total_heures_par_semestre' => $totalHeuresParSemestre,
            'totalHeures' => $allData['totalHeures'] ?? 0.0,
        ];

        $data['annee'] = $allData['annee'] ?? 'Inconnu';
        $data['periode'] = $allData['periode'] ?? 'Inconnu';

        // Passer les données au template Twig, la construction des feuilles se fait côté vue
        return $this->renderSpreadsheet(
            'export_tabler/edt_heures.html.twig',
            ['stats' => $data],
            'export_edt_heures.xlsx'
        );
    }
}
