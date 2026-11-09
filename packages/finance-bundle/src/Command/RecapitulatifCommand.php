<?php

namespace FinanceBundle\Command;

use FinanceBundle\Security\FinanceAccess;
use FinanceBundle\Service\Notification\FinanceDestinataires;
use FinanceBundle\Service\Notification\FinanceNotifier;
use FinanceBundle\Service\Notification\FinanceRecapitulatif;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Récapitulatif des demandes d'achat en attente, pour le service financier et les valideurs (planifié deux fois par
 * jour ouvré, cf. finance:taches:installer). Rien n'est envoyé à qui n'a aucune demande en attente ; chacun peut le
 * couper dans ses préférences de notification.
 */
#[AsCommand(name: 'finance:recapitulatif', description: 'Envoie le récapitulatif des demandes d\'achat en attente au service financier et aux valideurs.')]
final class RecapitulatifCommand extends Command
{
    public function __construct(
        private readonly FinanceDestinataires $destinataires,
        private readonly FinanceRecapitulatif $recapitulatif,
        private readonly FinanceNotifier $notifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche ce qui serait envoyé, sans rien envoyer.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $simulation = (bool) $input->getOption('dry-run');
        $envois = 0;
        foreach ($this->destinataires->parRole([FinanceAccess::ROLE_GESTIONNAIRE, FinanceAccess::ROLE_VALIDEUR]) as ['personnel' => $personnel, 'roles' => $roles]) {
            $contenu = $this->recapitulatif->pour($roles);
            if ($contenu === null) {
                continue;
            }
            ++$envois;
            $io->writeln(sprintf(' • %s : %d demande(s) — %s', $personnel->getDisplay(), $contenu['total'], $contenu['resume']));
            if (!$simulation) {
                $this->notifier->recapitulatif($personnel, $contenu);
            }
        }
        $io->success(sprintf('%d récapitulatif(s) %s.', $envois, $simulation ? 'à envoyer (simulation)' : 'transmis'));

        return Command::SUCCESS;
    }
}
