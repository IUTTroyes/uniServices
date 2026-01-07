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
        $data = json_decode($content, true) ?? [];

        // Passer les données brutes au template Twig, la construction des feuilles se fait côté vue
        return $this->renderSpreadsheet(
            'export_tabler/edt_heures.html.twig',
            ['stats' => $data],
            'export_edt_heures.xlsx'
        );
    }
}
