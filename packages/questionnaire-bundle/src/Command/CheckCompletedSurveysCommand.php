<?php

namespace QuestionnaireBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireInvitationRepository;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository;
use App\Entity\Users\Personnel;
use QuestionnaireBundle\Services\Notification\QuestionnaireNotifier;
use QuestionnaireBundle\Services\Diffusion\DiffusionService;
use QuestionnaireBundle\Services\QuestionnaireReglages;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clôture les questionnaires publiés dont la date limite est passée ou dont tous les participants ont répondu
 * (sauf si le département a désactivé la clôture automatique), envoie le bilan à l'auteur et au référent qualité du
 * département (une seule fois par questionnaire) et pré-remplit la diffusion des résultats (relecture). Un questionnaire
 * clôturé à la main reçoit aussi son bilan. Enfin, publie les résultats dont la relecture est terminée.
 */
#[AsCommand(
    name: 'app:questionnaire:check-completed',
    description: 'Clôture les questionnaires terminés et envoie le bilan à leur auteur',
)]
class CheckCompletedSurveysCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireNotifier $notifier,
        private readonly QuestionnaireRepository $questionnaireRepository,
        private readonly QuestionnaireInvitationRepository $invitationRepository,
        private readonly QuestionnaireReglages $reglages,
        private readonly DiffusionService $diffusion,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans envoyer d\'e-mail ni enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $simulation = (bool) $input->getOption('dry-run');
        $io->title('Clôture des questionnaires terminés'.($simulation ? ' [SIMULATION]' : ''));

        $maintenant = new \DateTime();
        $traites = 0;
        $envoyes = 0;
        $publications = 0;

        foreach ($this->questionnaireRepository->findPendingCompletionNotification() as $q) {
            $stats = $this->invitationRepository->getParticipationStats($q);
            $dateAtteinte = $q->getClosingDate() !== null && $q->getClosingDate() <= $maintenant;
            $complet = $stats['total'] > 0 && $stats['submitted'] >= $stats['total'];
            $dejaFerme = $q->getStatus() === QuestStatutEnum::CLOSED;
            $reglages = $this->reglages->pour($q->getDepartement());
            $automatique = $reglages['cloture_auto'] && ($dateAtteinte || $complet);
            if (!$automatique && !$dejaFerme) {
                continue;
            }

            $io->section(sprintf('%s : %d/%d réponse(s) (%.1f %%)', $q->getTitle(), $stats['submitted'], $stats['total'], $stats['rate']));
            ++$traites;

            // Bilan : auteur et référent qualité du département (notification, e-mail selon leurs préférences).
            $destinataires = $this->destinataires($q->getAuthor(), $reglages['responsable_qualite']);
            if ($destinataires === []) {
                $io->warning('Ni auteur ni référent qualité : bilan non envoyé.');
            }
            foreach ($destinataires as $personne) {
                if ($simulation) {
                    $io->note(sprintf('Bilan pour %s', $personne->getDisplay()));
                } elseif ($this->notifier->bilan($q, $personne, $stats, $complet, $dateAtteinte)) {
                    ++$envoyes;
                }
            }

            if (!$simulation) {
                $q->cloturer();
                $q->setCompletionNotified(true);
                $q->setCompletionNotifiedAt(new \DateTimeImmutable());
                $this->em->flush();
                // Début de la relecture : diffusion des résultats pré-remplie, à valider par l'équipe.
                if (!$q->isDiffusionInitialisee()) {
                    $this->diffusion->preremplir($q);
                }
            }
        }

        // Fin de relecture : publication automatique des résultats (diffusion validée, réglage du département).
        $maintenant = new \DateTimeImmutable();
        foreach ($this->questionnaireRepository->findBy(['status' => QuestStatutEnum::CLOSED, 'resultatsPubliesLe' => null]) as $q) {
            if ($simulation) {
                continue;
            }
            if ($this->diffusion->publierSiEcheance($q, $maintenant)) {
                ++$publications;
                $io->text(sprintf('Résultats publiés : %s', $q->getTitle()));
            }
        }

        if (!$simulation) {
            $this->em->flush();
        }
        $io->success(sprintf('%d questionnaire(s) clôturé(s), %d bilan(s) envoyé(s), %d publication(s) de résultats.', $traites, $envoyes, $publications));

        return Command::SUCCESS;
    }

    /** @return array<int, Personnel> auteur, puis référent qualité s'il est différent (indexés par identifiant) */
    private function destinataires(?Personnel $auteur, ?int $responsableId): array
    {
        $personnes = [$auteur];
        if ($responsableId !== null) {
            $personnes[] = $this->em->getRepository(Personnel::class)->find($responsableId);
        }
        $out = [];
        foreach ($personnes as $personne) {
            if ($personne instanceof Personnel && $personne->getId() !== null) {
                $out[$personne->getId()] ??= $personne;
            }
        }

        return $out;
    }
}
