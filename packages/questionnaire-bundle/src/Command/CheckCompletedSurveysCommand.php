<?php

namespace QuestionnaireBundle\Command;

use App\Service\Email\EmailService;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireInvitationRepository;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:questionnaire:check-completed',
    description: 'Vérifie les questionnaires terminés (date expirée ou 100% de réponses) et notifie l\'auteur par e-mail avec le bilan.',
)]
class CheckCompletedSurveysCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface             $em,
        private readonly EmailService                       $emailService,
        private readonly QuestionnaireRepository            $questionnaireRepository,
        private readonly QuestionnaireInvitationRepository  $invitationRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'base-url',
                'b',
                InputOption::VALUE_REQUIRED,
                'URL de base du frontend (ex: http://localhost:3000)',
                'http://localhost:3000'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Simuler l\'exécution sans envoyer d\'emails ni enregistrer les modifications'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $baseUrl = rtrim($input->getOption('base-url'), '/');
        $isDryRun = (bool) $input->getOption('dry-run');

        $io->title('Vérification des questionnaires complétés / clôturés' . ($isDryRun ? ' [SIMULATION]' : ''));

        $questionnaires = $this->questionnaireRepository->findPendingCompletionNotification();
        $io->text(sprintf('Trouvé %d questionnaire(s) en attente de vérification de clôture.', count($questionnaires)));

        if (empty($questionnaires)) {
            $io->success('Aucun questionnaire à traiter.');
            return Command::SUCCESS;
        }

        $now = new \DateTime();
        $processedCount = 0;
        $notifiedCount = 0;

        foreach ($questionnaires as $q) {
            $stats = $this->invitationRepository->getParticipationStats($q);

            $isDateExpired = ($q->getClosingDate() !== null && $q->getClosingDate() <= $now);
            $isFullyCompleted = ($stats['total'] > 0 && $stats['submitted'] >= $stats['total']);
            $isAlreadyClosed = ($q->getStatus() === QuestStatutEnum::CLOSED);

            // Si le questionnaire n'a ni atteint sa date de fin, ni 100% de réponses, et n'est pas déjà fermé, on continue
            if (!$isDateExpired && !$isFullyCompleted && !$isAlreadyClosed) {
                continue;
            }

            $io->section(sprintf('Questionnaire : %s (ID: %d, UUID: %s)', $q->getTitle(), $q->getId(), $q->getUuidString()));
            $io->text(sprintf(
                '  - Participation : %d/%d (%.1f%%) | Date fin : %s | Raison : %s',
                $stats['submitted'],
                $stats['total'],
                $stats['rate'],
                $q->getClosingDate() ? $q->getClosingDate()->format('d/m/Y H:i') : 'Aucune',
                $isFullyCompleted ? '100% des réponses reçues' : ($isDateExpired ? 'Date limite atteinte' : 'Statut fermé')
            ));

            // Déterminer l'auteur et l'adresse email de notification
            $recipientEmail = null;
            $authorName = null;

            if ($q->getAuthor()) {
                $recipientEmail = $q->getAuthor()->getMailUniv() ?: $q->getAuthor()->getMailPerso();
                $authorName = trim($q->getAuthor()->getPrenom() . ' ' . $q->getAuthor()->getNom());
            }

            if (!$recipientEmail && !empty($q->getOpt()['authorEmail'])) {
                $recipientEmail = trim($q->getOpt()['authorEmail']);
            }

            $analyticsUrl = sprintf('%s/questionnaire/qualite/enquetes/analytics/%s', $baseUrl, $q->getUuidString());

            if ($recipientEmail) {
                $io->text(sprintf('  - Destinataire auteur : %s (%s)', $authorName ?: 'Auteur', $recipientEmail));

                if (!$isDryRun) {
                    try {
                        $this->emailService->send(
                            emailKey: 'questionnaire.completed',
                            to: $recipientEmail,
                            context: [
                                'questionnaire' => $q,
                                'authorName'    => $authorName,
                                'stats'         => [
                                    ...$stats,
                                    'isDateExpired'    => $isDateExpired,
                                    'isFullyCompleted' => $isFullyCompleted,
                                ],
                                'analyticsUrl'  => $analyticsUrl,
                            ]
                        );
                        $notifiedCount++;
                        $io->text('    * Email de bilan envoyé avec succès.');
                    } catch (\Exception $e) {
                        $io->error(sprintf('    * Échec d\'envoi de l\'email à %s : %s', $recipientEmail, $e->getMessage()));
                    }
                } else {
                    $io->note(sprintf('    [SIMULATION] Un email aurait été envoyé à %s', $recipientEmail));
                }
            } else {
                $io->warning('  - Aucun email d\'auteur trouvé pour ce questionnaire.');
            }

            if (!$isDryRun) {
                // Clôturer le questionnaire si date expirée ou complété
                if ($q->getStatus() === QuestStatutEnum::PUBLISHED && ($isDateExpired || $isFullyCompleted)) {
                    $q->setStatus(QuestStatutEnum::CLOSED);
                }

                $q->setCompletionNotified(true);
                $q->setCompletionNotifiedAt(new \DateTimeImmutable());
                $this->em->persist($q);
            }

            $processedCount++;
        }

        if (!$isDryRun && $processedCount > 0) {
            $this->em->flush();
        }

        $io->success(sprintf(
            'Traitement terminé : %d questionnaire(s) clôturé(s)/traité(s), %d email(s) envoyé(s).',
            $processedCount,
            $notifiedCount
        ));

        return Command::SUCCESS;
    }
}
