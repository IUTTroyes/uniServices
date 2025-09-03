<?php

declare(strict_types=1);

namespace App\Controller\Etudiant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateEtudiantController extends AbstractController
{
    #[Route('/api/etudiants/create', methods: ['POST'], name: 'create_etudiant')]
    public function create(Request $request): Response
    {
        // Récupérer les données JSON envoyées dans la requête
        $data = $request->toArray();

        dump($data['file']);
        return new Response('ok');
    }
}
