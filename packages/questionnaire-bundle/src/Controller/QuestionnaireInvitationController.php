<?php

namespace QuestionnaireBundle\Controller;

use QuestionnaireBundle\Domain\Questionnaire\Publish\PublishQuestionnaireService;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Invitation de nouveaux participants à un questionnaire publié (envoi des e-mails d'invitation).
 * Corps : {"emails": ["a@exemple.fr", …]} ; les adresses déjà invitées sont ignorées.
 */
class QuestionnaireInvitationController extends AbstractController
{
    public function __construct(
        private readonly QuestionnaireResolver $questionnaires,
        private readonly PublishQuestionnaireService $publication,
    ) {
    }

    #[Route('/api/questionnaires/{uuid}/invitations', name: 'api_questionnaire_inviter', methods: ['POST'])]
    public function inviter(string $uuid, Request $request): JsonResponse
    {
        $questionnaire = $this->questionnaires->charger($uuid, QuestionnaireVoter::MANAGE);
        $data = json_decode($request->getContent(), true);
        $emails = is_array($data) && is_array($data['emails'] ?? null) ? $data['emails'] : [];
        if ($emails === []) {
            return new JsonResponse(['error' => 'Aucune adresse e-mail fournie.'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->publication->inviter($questionnaire, $emails), Response::HTTP_CREATED);
    }
}
