<?php

namespace App\Scheduler;

use App\Entity\Scheduler\SchedulerTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Base commune des commandes « <package>:taches:installer » : crée, sans doublon (le nom fait foi), les tâches planifiées
 * d'un package dans la table SchedulerTask. À lancer une fois par environnement après déploiement ; l'option --inactives
 * les crée désactivées (à activer depuis l'administration des tâches).
 *
 * Une sous-classe n'a qu'à porter #[AsCommand] et décrire ses tâches dans taches().
 */
abstract class AbstractInstallerTachesCommand extends Command
{
    public function __construct(protected readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    /**
     * Tâches du package : nom (unique) → [commande, arguments, expression cron, description].
     *
     * @return array<string, array{0: string, 1: list<string>, 2: string, 3: string}>
     */
    abstract protected function taches(): array;

    protected function configure(): void
    {
        $this->addOption('inactives', null, InputOption::VALUE_NONE, 'Crée les tâches désactivées (à activer ensuite depuis l\'administration des tâches).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repo = $this->em->getRepository(SchedulerTask::class);
        foreach ($this->taches() as $nom => [$commande, $arguments, $cron, $description]) {
            if ($repo->findOneBy(['name' => $nom]) !== null) {
                $io->writeln(sprintf(' • %s : déjà présente', $nom));
                continue;
            }
            $this->em->persist((new SchedulerTask())->setName($nom)->setCommand($commande)->setArguments($arguments)
                ->setCronExpression($cron)->setDescription($description)->setActive(!$input->getOption('inactives')));
            $io->writeln(sprintf(' • %s : créée (%s)', $nom, $cron));
        }
        $this->em->flush();
        $io->success('Les tâches sont enregistrées ; le planificateur les prend en compte à son prochain rechargement.');

        return Command::SUCCESS;
    }
}
