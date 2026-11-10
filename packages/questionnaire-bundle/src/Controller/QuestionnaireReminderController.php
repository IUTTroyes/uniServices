<?php

namespace QuestionnaireBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use QuestionnaireBundle\Services\Notification\QuestionnaireNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Relance manuelle de participants qui n'ont pas encore envoyé leurs réponses.
 * Corps : {"invitations": [id, …], "subject"?: "…", "message"?: "…"}.
 */
#[\Symfony\Component\Security\Http\Attribute\IsGranted('IS_AUTHENTICATED_FULLY')]
class QuestionnaireReminderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireResolver $questionnaires,
        private readonly QuestionnaireNotifier $notifier,
    ) {
    }

    #[Route('/api/questionnaires/{uuid}/reminders', name: 'api_questionnaire_send_reminders', methods: ['POST'])]
    public function sendReminders(string $uuid, Request $request): JsonResponse
    {
        $questionnaire = $this->questionnaires->charger($uuid, QuestionnaireVoter::CAN_PUBLISH_QUESTIONNAIRE);
        if (!$questionnaire->estOuvert()) {
            return new JsonResponse(['error' => 'Ce questionnaire n\'est pas ouvert aux réponses : aucune relance possible.'], Response::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true);
        $data = is_array($data) ? $data : [];
        // Invitations désignées par leur identifiant (le jeton, clé d'accès du répondant, n'est jamais exposé).
        $ids = array_values(array_filter(array_map('intval', is_array($data['invitations'] ?? null) ? $data['invitations'] : [])));
        if ($ids === []) {
            return new JsonResponse(['error' => 'Aucun participant spécifié.'], Response::HTTP_BAD_REQUEST);
        }
        $objet = mb_substr(trim((string) ($data['subject'] ?? '')), 0, 255);
        $message = mb_substr(trim((string) ($data['message'] ?? '')), 0, 5000);

        $invitations = $this->em->getRepository(QuestionnaireInvitation::class)->findBy(['questionnaire' => $questionnaire, 'id' => $ids]);
        $type = count($invitations) > 1 ? 'bulk' : 'manual';

        $envoyees = 0;
        $echecs = 0;
        $misesAJour = [];
        foreach ($invitations as $invitation) {
            if ($invitation->isSubmitted()) {
                continue;
            }
            if (!$this->notifier->relance($invitation, $objet ?: null, $message ?: null)) {
                ++$echecs;
                continue;
            }
            $invitation->addReminder(
                subject: $objet !== '' ? $objet : sprintf('Rappel : questionnaire « %s » en attente', $questionnaire->getTitle()),
                message: $message !== '' ? $message : null,
                type: $type,
            );
            ++$envoyees;
            $misesAJour[] = [
                'id' => $invitation->getId(),
                'remindedAt' => $invitation->getRemindedAt()?->format(\DateTimeInterface::ATOM),
                'remindersCount' => $invitation->getRemindersCount(),
                'remindersHistory' => $invitation->getRemindersHistory(),
            ];
        }
        $this->em->flush();

        return new JsonResponse([
            'success' => $echecs === 0,
            'sentCount' => $envoyees,
            'failedCount' => $echecs,
            'invitations' => $misesAJour,
        ]);
    }
}
