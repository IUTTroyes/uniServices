<?php

namespace IntranetBundle\Controller;

use App\Repository\EtudiantRepository;
use App\Repository\EtudiantScolariteRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DemissionEtudiantController extends AbstractController
{
    public function __construct(
        private readonly EtudiantScolariteRepository $etudiantScolariteRepository,
        private readonly EtudiantRepository $etudiantRepository,
    )
    {
    }

    #[Route('/api/etudiant_scolarites/demission/{id}', name: 'app_etudiant_demission', methods: ['PATCH'])]
    public function demission(string $id): Response
    {
        $etudiantScolarite = $this->etudiantScolariteRepository->find($id);
        if (!$etudiantScolarite) {
            return $this->json(['message' => 'Scolarité non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $etudiantScolarite->setActif(false);
        $this->etudiantScolariteRepository->save($etudiantScolarite, true);

        // récupérer l'année calendaire actuelle
        $anneeSortie = (int) date('Y');
        $etudiant = $etudiantScolarite->getEtudiant();
        $etudiant->setAnneeSortie($anneeSortie);
        $this->etudiantRepository->save($etudiant, true);

        return $this->json(['message' => 'Étudiant mis en démission avec succès'], Response::HTTP_OK);
    }
}
