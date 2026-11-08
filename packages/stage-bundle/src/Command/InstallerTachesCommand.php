<?php

namespace StageBundle\Command;

use App\Entity\Scheduler\SchedulerTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'stage:taches:installer',
    description: 'Crée (sans doublon) les tâches planifiées du package Stages : relances hebdomadaires et purge des anciens dossiers.',
)]
final class InstallerTachesCommand extends Command
{
    /** Nom → [commande, arguments, cron, description] */
    private const TACHES = [
        'Stages — relances' => ['stage:relances', [], '0 7 * * 1', 'Chaque lundi : demandes en attente, conventions non revenues, rapports manquants.'],
        'Stages — purge des anciens dossiers' => ['stage:purger', [], '0 3 1 * *', 'Chaque mois : anonymise les dossiers terminés depuis plus de 5 ans et efface leurs rapports.'],
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
