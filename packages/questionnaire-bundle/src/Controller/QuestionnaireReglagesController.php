<?php

namespace QuestionnaireBundle\Controller;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use QuestionnaireBundle\Services\QuestionnaireReglages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Réglages des questionnaires d'un département (options du département, clé « questionnaire »).
 * Lecture et écriture réservées aux gestionnaires des questionnaires de ce département.
 *
 * GET /api/questionnaire/reglages/{departement} → réglages complets (valeurs par défaut comprises)
 * PUT /api/questionnaire/reglages/{departement} → champs partiels, 422 si clé inconnue ou valeur hors bornes
 */
class QuestionnaireReglagesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireAccess $access,
        private readonly QuestionnaireReglages $reglages,
    ) {
    }

    #[Route('/api/questionnaire/reglages/{id}', name: 'api_questionnaire_reglages_lire', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function lire(int $id): JsonResponse
    {
        return new JsonResponse($this->reglages->pour($this->departement($id)));
    }

    #[Route('/api/questionnaire/reglages/{id}', name: 'api_questionnaire_reglages_modifier', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function modifier(int $id, Request $request): JsonResponse
    {
        $departement = $this->departement($id);
        $donnees = json_decode($request->getContent(), true);
        if (!is_array($donnees)) {
            throw new UnprocessableEntityHttpException('Corps JSON attendu.');
        }
        // Le référent qualité doit être un personnel du département.
        if (($donnees['responsable_qualite'] ?? null) !== null && $donnees['responsable_qualite'] !== '') {
            $responsable = $this->em->getRepository(Personnel::class)->find((int) $donnees['responsable_qualite']);
            $rattache = false;
            foreach ($responsable?->getDepartementsPersonnel() ?? [] as $affectation) {
                $rattache = $rattache || $affectation->getDepartement()?->getId() === $departement->getId();
            }
            if (!$rattache) {
                throw new UnprocessableEntityHttpException('Le référent qualité doit être un personnel du département.');
            }
        } elseif (array_key_exists('responsable_qualite', $donnees)) {
            $donnees['responsable_qualite'] = null;
        }

        $resultat = $this->reglages->enregistrer($departement, $donnees);
        $this->em->flush();

        return new JsonResponse($resultat);
    }

    private function departement(int $id): StructureDepartement
    {
        $departement = $this->em->getRepository(StructureDepartement::class)->find($id);
        if (!$departement instanceof StructureDepartement) {
            throw new NotFoundHttpException('Département introuvable.');
        }
        if (!$this->access->estGestionnaire($this->getUser(), $departement)) {
            throw new AccessDeniedHttpException('Réglages réservés aux gestionnaires des questionnaires de ce département.');
        }

        return $departement;
    }
}
