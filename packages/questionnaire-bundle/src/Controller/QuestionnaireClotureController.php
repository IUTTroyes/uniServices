<?php

namespace QuestionnaireBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use QuestionnaireBundle\Services\Diffusion\DiffusionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Clôture manuelle d'un questionnaire publié (nécessaire quand le département a désactivé la clôture automatique).
 * Plus aucune réponse n'est acceptée ; la diffusion des résultats est pré-remplie (relecture) ; le bilan part au prochain
 * passage de app:questionnaire:check-completed.
 */
class QuestionnaireClotureController extends AbstractController
{
    public function __construct(
        private readonly QuestionnaireResolver $questionnaires,
        private readonly EntityManagerInterface $em,
        private readonly DiffusionService $diffusion,
    ) {
    }

    #[Route('/api/questionnaires/{uuid}/cloture', name: 'api_questionnaire_cloturer', methods: ['POST'])]
    public function cloturer(string $uuid): JsonResponse
    {
        $questionnaire = $this->questionnaires->charger($uuid, QuestionnaireVoter::CAN_PUBLISH_QUESTIONNAIRE);
        if (!$questionnaire->isPublished()) {
            throw new ConflictHttpException('Seul un questionnaire publié peut être clôturé.');
        }
        $questionnaire->cloturer();
        $this->em->flush();
        // Début de la relecture : la diffusion des résultats est pré-remplie, à valider par l'équipe.
        if (!$questionnaire->isDiffusionInitialisee()) {
            $this->diffusion->preremplir($questionnaire);
        }

        return new JsonResponse(['status' => QuestStatutEnum::CLOSED->value]);
    }
}
