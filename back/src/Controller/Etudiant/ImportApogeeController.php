<?php

declare(strict_types=1);

namespace App\Controller\Etudiant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportApogeeController extends AbstractController
{
    #[Route('/api/etudiants/import_apogee')]
    public function import(?array $data): Response
    {
        return new Response('ok');
    }

    public function getEtudiantsFromApogee(): array
    {
        return [];
    }
}
