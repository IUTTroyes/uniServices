<?php

namespace App\Command;

use App\Enum\FrequenceResumeEnum;
use App\Notification\NotificationDigest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notifications:resume',
    description: 'Envoie le résumé groupé des notifications (un seul e-mail par personne, toute l\'application) aux abonnés de la fréquence demandée.',
)]
final class NotificationDigestCommand extends Command
{
    public function __construct(private readonly NotificationDigest $digest)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('frequence', null, InputOption::VALUE_REQUIRED, 'quotidien ou hebdomadaire.', 'quotidien')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Compte ce qui serait envoyé, sans envoyer ni modifier.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $frequence = FrequenceResumeEnum::tryFrom((string) $input->getOption('frequence'));
        if ($frequence === null) {
            $io->error('Fréquence inconnue : quotidien ou hebdomadaire.');

            return Command::INVALID;
        }
        $essai = (bool) $input->getOption('dry-run');
        $s = $this->digest->envoyer($frequence, $essai);
        $io->success(sprintf(
            '%s : %d personne(s) concernée(s), %d résumé(s) %s (%d événement(s)), %d événement(s) écarté(s) (type désactivé depuis).',
            $frequence->getLibelle(), $s['personnes'], $s['envoyes'], $essai ? 'à envoyer' : 'envoyé(s)', $s['evenements'], $s['ecartes']
        ));

        return Command::SUCCESS;
    }
}
