<?php

namespace QuestionnaireBundle\Controller;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Domain\Questionnaire\Duplicate\DuplicateQuestionnaireService;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use QuestionnaireBundle\Services\Analytics\ComparaisonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Bibliothèque de modèles :
 *
 * POST /api/questionnaires/{uuid}/modele      enregistre une copie du questionnaire comme modèle
 *                                             {"title"?, "departement"?: id|null} (null : modèle d'établissement, super administrateur)
 * GET  /api/questionnaires/{uuid}/comparaison campagnes du même modèle comparées d'une année sur l'autre
 * POST /api/questionnaires/{uuid}/instancier  crée un questionnaire (brouillon de l'année en cours) depuis un modèle
 *                                             {"title"?, "departement": id} ; les campagnes d'un même modèle se comparent
 */
class QuestionnaireModeleController extends AbstractController
{
    public function __construct(
        private readonly QuestionnaireResolver $questionnaires,
        private readonly QuestionnaireAccess $access,
        private readonly DuplicateQuestionnaireService $duplication,
        private readonly EntityManagerInterface $em,
        private readonly ComparaisonService $comparaison,
    ) {
    }

    #[Route('/api/questionnaires/{uuid}/modele', name: 'api_questionnaire_en_modele', methods: ['POST'])]
    public function enregistrerCommeModele(string $uuid, Request $request): JsonResponse
    {
        $source = $this->questionnaires->charger($uuid, QuestionnaireVoter::VIEW);
        [$titre, $departement] = $this->cible($request, $source);
        $resultat = $this->duplication->duplicate($source, $titre ?? $source->getTitle(), $this->personnel(), [
            'departement' => $departement,
            'modele' => true,
        ]);

        return $this->reponse($resultat['questionnaire']);
    }

    #[Route('/api/questionnaires/{uuid}/instancier', name: 'api_questionnaire_instancier', methods: ['POST'])]
    public function instancier(string $uuid, Request $request): JsonResponse
    {
        $modele = $this->questionnaires->charger($uuid, QuestionnaireVoter::VIEW);
        if (!$modele->isEstModele()) {
            throw new ConflictHttpException('Ce questionnaire n\'est pas un modèle.');
        }
        [$titre, $departement] = $this->cible($request, $modele);
        $resultat = $this->duplication->duplicate($modele, $titre ?? $modele->getTitle(), $this->personnel(), [
            'departement' => $departement,
            'modeleOrigine' => $modele,
        ]);

        return $this->reponse($resultat['questionnaire']);
    }

    /** Comparaison des campagnes du même modèle (participation, moyennes, répartitions). */
    #[Route('/api/questionnaires/{uuid}/comparaison', name: 'api_questionnaire_comparaison', methods: ['GET'])]
    public function comparaison(string $uuid): JsonResponse
    {
        $q = $this->questionnaires->charger($uuid, QuestionnaireVoter::VIEW);

        return new JsonResponse($this->comparaison->comparer($q, $this->getUser()));
    }

    /**
     * Titre et département de la copie ; il faut pouvoir créer un questionnaire dans ce département.
     *
     * @return array{0: ?string, 1: ?StructureDepartement}
     */
    private function cible(Request $request, Questionnaire $source): array
    {
        $donnees = json_decode($request->getContent(), true);
        $donnees = \is_array($donnees) ? $donnees : [];
        $titre = trim((string) ($donnees['title'] ?? '')) ?: null;

        if (\array_key_exists('departement', $donnees)) {
            $departement = $donnees['departement'] === null ? null : $this->em->find(StructureDepartement::class, (int) $donnees['departement']);
            if ($donnees['departement'] !== null && $departement === null) {
                throw new NotFoundHttpException('Département introuvable.');
            }
        } else {
            $departement = $source->getDepartement();
        }
        if (!$this->access->peutCreer($this->getUser(), $departement)) {
            throw new AccessDeniedHttpException($departement === null
                ? 'Seul le super administrateur gère les modèles d\'établissement.'
                : 'Vous ne pouvez pas créer de questionnaire dans ce département.');
        }

        return [$titre, $departement];
    }

    private function personnel(): ?Personnel
    {
        $user = $this->getUser();

        return $user instanceof Personnel ? $user : null;
    }

    private function reponse(Questionnaire $q): JsonResponse
    {
        return new JsonResponse(['uuid' => $q->getUuidString(), 'title' => $q->getTitle(), 'estModele' => $q->isEstModele()], Response::HTTP_CREATED);
    }
}
