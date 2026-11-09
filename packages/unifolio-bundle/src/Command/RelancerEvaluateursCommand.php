<?php

namespace UnifolioBundle\Command;

use App\Entity\Users\Personnel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Repository\CampagneRepository;
use UnifolioBundle\Service\Evaluation\EvaluationNotifier;
use UnifolioBundle\Service\Evaluation\FileEvaluation;

#[AsCommand(
    name: 'unifolio:campagnes:relancer',
    description: 'Relance par e-mail les évaluateurs qui ont des évaluations en attente à l\'approche (ou après) la limite d\'évaluation.',
)]
final class RelancerEvaluateursCommand extends Command
{
    public function __construct(
        private readonly CampagneRepository $campagnes,
        private readonly CampagneAffectationRepository $affectations,
        private readonly FileEvaluation $file,
        private readonly EvaluationNotifier $notifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('jours', null, InputOption::VALUE_REQUIRED, 'Relancer à partir de N jours avant la limite d\'évaluation.', '7')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Liste les relances sans envoyer d\'e-mail.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jours = max(0, (int) $input->getOption('jours'));
        $seuil = new \DateTimeImmutable(sprintf('+%d days', $jours));
        $dryRun = (bool) $input->getOption('dry-run');

        $lignes = [];
        $envoyes = 0;
        foreach ($this->campagnes->findOuvertes() as $campagne) {
            if ($campagne->getEvaluationLimite() === null || $campagne->getEvaluationLimite() > $seuil) {
                continue;
            }
            $parEvaluateur = [];
            foreach ($this->affectations->findParCampagne($campagne) as $a) {
                $parEvaluateur[$a->getEvaluateur()->getId()]['p'] = $a->getEvaluateur();
                $parEvaluateur[$a->getEvaluateur()->getId()]['etudiants'][] = $a->getEtudiant();
            }
            foreach ($parEvaluateur as ['p' => $evaluateur, 'etudiants' => $etudiants]) {
                \assert($evaluateur instanceof Personnel);
                $totaux = $this->file->campagne($evaluateur, $campagne, $etudiants)['totaux'];
                $enAttente = $totaux['aEvaluer'] + $totaux['enCours'] + $totaux['aReprendre'];
                if ($enAttente === 0) {
                    continue;
                }
                $envoye = $dryRun ? false : $this->notifier->relanceEvaluateur($evaluateur, $campagne, $enAttente);
                $envoyes += $envoye ? 1 : 0;
                $lignes[] = [$campagne->getLibelle(), $evaluateur->getDisplay(), $enAttente, $dryRun ? 'simulé' : ($envoye ? 'envoyé' : 'échec')];
            }
        }

        if ($lignes === []) {
            $io->success('Aucune relance nécessaire.');

            return Command::SUCCESS;
        }
        $io->table(['Campagne', 'Évaluateur', 'En attente', 'Relance'], $lignes);
        $io->success($dryRun ? sprintf('%d relance(s) à envoyer.', \count($lignes)) : sprintf('%d relance(s) envoyée(s) sur %d.', $envoyes, \count($lignes)));

        return Command::SUCCESS;
    }
}
