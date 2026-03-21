<?php

namespace IUTTroyes\IntranetBundle\Controller;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class AnneeUniversitaireDiplomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/structure_annee_universitaire/{anneeUnivId}/diplomes/{diplomeId}', name: 'api_annee_univ_remove_diplome', methods: ['DELETE'])]
    #[IsGranted('CAN_EDIT_ANNEE_UNIV')]
    public function removeDiplomeFromAnneeUniv(int $anneeUnivId, int $diplomeId): JsonResponse
    {
        $anneeUniversitaire = $this->entityManager->getRepository(StructureAnneeUniversitaire::class)->find($anneeUnivId);

        if (!$anneeUniversitaire) {
            return new JsonResponse(['error' => 'Année universitaire non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $diplome = $this->entityManager->getRepository(StructureDiplome::class)->find($diplomeId);

        if (!$diplome) {
            return new JsonResponse(['error' => 'Diplôme non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le diplôme est bien associé à l'année universitaire
        if (!$anneeUniversitaire->getDiplomes()->contains($diplome)) {
            return new JsonResponse(['error' => 'Ce diplôme n\'est pas associé à cette année universitaire'], Response::HTTP_BAD_REQUEST);
        }

        // Supprimer l'association
        $anneeUniversitaire->removeDiplome($diplome);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Diplôme retiré de l\'année universitaire avec succès'], Response::HTTP_OK);
    }
}


