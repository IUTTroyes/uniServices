<?php

namespace QuestionnaireBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireInvitationRepository;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository;
use QuestionnaireBundle\Services\Notification\QuestionnaireNotifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Relance automatique des participants (questionnaires publiés et ouverts) qui n'ont pas répondu, au plus une fois
 * tous les --days jours. Les liens des e-mails pointent vers URL_FRONTEND.
 */
#[AsCommand(
    name: 'app:questionnaire:send-reminders',
    description: 'Relance les participants des questionnaires ouverts qui n\'ont pas encore répondu',
)]
class SendSurveyRemindersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireNotifier $notifier,
        private readonly QuestionnaireRepository $questionnaireRepository,
        private readonly QuestionnaireInvitationRepository $invitationRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Nombre minimal de jours depuis l\'invitation ou la dernière relance', '3')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Compter sans rien envoyer ni enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $simulation = (bool) $input->getOption('dry-run');
        if ($days <= 0) {
            $io->error('L\'option --days doit être un entier strictement positif.');

            return Command::INVALID;
        }

        $io->title('Relances automatiques des questionnaires'.($simulation ? ' [SIMULATION]' : ''));
        $olderThan = new \DateTimeImmutable(sprintf('-%d days', $days));
        $envoyes = 0;
        $echecs = 0;

        foreach ($this->questionnaireRepository->findActivePublished() as $q) {
            $invitations = $this->invitationRepository->findPendingInvitationsForQuestionnaire($q, $olderThan);
            if ($invitations === []) {
                continue;
            }
            $io->section(sprintf('%s : %d participant(s) à relancer', $q->getTitle(), count($invitations)));
            if ($simulation) {
                $envoyes += count($invitations);
                continue;
            }
            foreach ($invitations as $invitation) {
                if ($this->notifier->relance($invitation)) {
                    $invitation->incrementRemindersCount();
                    ++$envoyes;
                } else {
                    ++$echecs;
                }
            }
            $this->em->flush();
        }

        $io->success(sprintf('%d relance(s) %s, %d échec(s).', $envoyes, $simulation ? 'à envoyer' : 'envoyée(s)', $echecs));

        return Command::SUCCESS;
    }
}
