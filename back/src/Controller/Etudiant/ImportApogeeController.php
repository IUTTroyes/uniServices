<?php

declare(strict_types=1);

namespace App\Controller\Etudiant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportApogeeController extends AbstractController
{
    #[Route('/import-apogee')]
    public function index(): Response
    {
        return $this->render('import_apogee/index.html.twig');
    }
}
