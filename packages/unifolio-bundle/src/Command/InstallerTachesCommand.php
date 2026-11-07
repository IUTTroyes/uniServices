<?php

namespace UnifolioBundle\Command;

use App\Entity\Scheduler\SchedulerTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'unifolio:taches:installer',
    description: 'Crée (sans doublon) la tâche planifiée d\'UniFolio : la relance des évaluateurs (les résumés de notifications : app:notifications:taches).',
)]
final class InstallerTachesCommand extends Command
{
    /** Nom → [commande, arguments, cron, description] */
    private const TACHES = [
        'UniFolio — relance des évaluateurs' => ['unifolio:campagnes:relancer', [], '0 7 * * 1-5', 'Rappelle les évaluations en attente à l\'approche (ou après) la limite d\'évaluation.'],
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('inactives', null, InputOption::VALUE_NONE, 'Crée les tâches désactivées (à activer ensuite depuis l\'administration des tâches).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repo = $this->em->getRepository(SchedulerTask::class);
        foreach (self::TACHES as $nom => [$commande, $arguments, $cron, $description]) {
            if ($repo->findOneBy(['name' => $nom]) !== null) {
                $io->writeln(sprintf(' • %s : déjà présente', $nom));
                continue;
            }
            $tache = (new SchedulerTask())->setName($nom)->setCommand($commande)->setArguments($arguments)->setCronExpression($cron)->setDescription($description)->setActive(!$input->getOption('inactives'));
            $this->em->persist($tache);
            $io->writeln(sprintf(' • %s : créée (%s)', $nom, $cron));
        }
        $this->em->flush();
        $io->success('Les tâches sont enregistrées ; le planificateur les prend en compte à son prochain rechargement.');

        return Command::SUCCESS;
    }
}
