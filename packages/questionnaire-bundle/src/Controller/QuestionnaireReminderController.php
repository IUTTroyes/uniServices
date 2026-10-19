<?php

namespace QuestionnaireBundle\Controller;

use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireInvitationRepository;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class QuestionnaireReminderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmailService $emailService,
        private readonly QuestionnaireRepository $questionnaireRepository,
        private readonly QuestionnaireInvitationRepository $invitationRepository,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    #[Route('/api/questionnaires/{uuid}/reminders', name: 'api_questionnaire_send_reminders', methods: ['POST'])]
    public function sendReminders(string $uuid, Request $request): JsonResponse
    {
        $questionnaire = $this->questionnaireRepository->findOneBy(['uuid' => $uuid]);
        if (!$questionnaire) {
            throw new NotFoundHttpException('Questionnaire introuvable.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $tokens = $data['tokens'] ?? [];
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if (empty($tokens) || !is_array($tokens)) {
            return new JsonResponse([
                'error' => 'Aucun participant spécifié.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($subject === '') {
            $subject = sprintf('Rappel : questionnaire "%s" en attente', $questionnaire->getTitle());
        }

        $invitations = $this->em->getRepository(QuestionnaireInvitation::class)->findBy([
            'questionnaire' => $questionnaire,
            'token' => $tokens,
        ]);

        $type = count($invitations) > 1 ? 'bulk' : 'manual';
        $sentCount = 0;
        $baseUrl = $request->getSchemeAndHttpHost();

        $updatedInvitations = [];

        foreach ($invitations as $invitation) {
            if ($invitation->isSubmitted()) {
                continue;
            }

            // Enregistrer l'historique de relance
            $invitation->addReminder(
                subject: $subject,
                message: $message !== '' ? $message : null,
                type: $type
            );

            // Envoi de l'email si adresse présente
            $email = $invitation->getEmail();
            if ($email !== null && trim($email) !== '') {
                $surveyUrl = sprintf('%s/take/%s', $baseUrl, $invitation->getToken());
                try {
                    $this->emailService->send(
                        emailKey: 'questionnaire.reminder',
                        to: $email,
                        context: [
                            'invitation'    => $invitation,
                            'questionnaire' => $questionnaire,
                            'surveyUrl'     => $surveyUrl,
                            'expiresAt'     => $questionnaire->getClosingDate(),
                            'customSubject' => $subject,
                            'customMessage' => $message,
                        ]
                    );
                } catch (\Throwable $e) {
                    $this->logger?->warning(sprintf('Échec envoi email relance questionnaire à %s: %s', $email, $e->getMessage()));
                }
            }

            $this->em->persist($invitation);
            $sentCount++;

            $updatedInvitations[] = [
                'token' => $invitation->getToken(),
                'remindedAt' => $invitation->getRemindedAt()?->format(\DateTimeInterface::ATOM),
                'remindersCount' => $invitation->getRemindersCount(),
                'remindersHistory' => $invitation->getRemindersHistory(),
            ];
        }

        if ($sentCount > 0) {
            $this->em->flush();
        }

        return new JsonResponse([
            'success' => true,
            'sentCount' => $sentCount,
            'invitations' => $updatedInvitations,
        ]);
    }
}
