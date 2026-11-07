<?php

namespace App\Command;

use App\Entity\Scheduler\SchedulerTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notifications:taches',
    description: 'Crée (sans doublon) les tâches planifiées des résumés de notifications : quotidien (18 h) et hebdomadaire (lundi 8 h).',
)]
final class NotificationTachesCommand extends Command
{
    private const TACHES = [
        'Notifications — résumé quotidien' => [['--frequence=quotidien'], '0 18 * * *', 'Envoie chaque soir le résumé des notifications aux abonnés « quotidien ».'],
        'Notifications — résumé hebdomadaire' => [['--frequence=hebdomadaire'], '0 8 * * 1', 'Envoie chaque lundi matin le résumé des notifications aux abonnés « hebdomadaire ».'],
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
        foreach (self::TACHES as $nom => [$arguments, $cron, $description]) {
            if ($repo->findOneBy(['name' => $nom]) !== null) {
                $io->writeln(sprintf(' • %s : déjà présente', $nom));
                continue;
            }
            $this->em->persist((new SchedulerTask())->setName($nom)->setCommand('app:notifications:resume')->setArguments($arguments)->setCronExpression($cron)->setDescription($description)->setActive(!$input->getOption('inactives')));
            $io->writeln(sprintf(' • %s : créée (%s)', $nom, $cron));
        }
        $this->em->flush();
        $io->success('Les tâches sont enregistrées ; le planificateur les prend en compte à son prochain rechargement.');

        return Command::SUCCESS;
    }
}
