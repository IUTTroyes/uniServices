<?php

namespace App\Command\OReOF;

use App\Services\OReOF\SynchroOreof;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:oreof:synchro',
    description: 'Récupération des données des maquettes depuis ORéOF',
)]
class OreofSynchroCommand extends Command
{
    public function __construct(
        protected SynchroOreof $synchroOreof
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all-diplome', null, InputOption::VALUE_NONE, 'Affichage ou synchronisation de tous les diplômes')
            ->addOption('diplome', null, InputOption::VALUE_REQUIRED, 'Synchroniser un diplôme spécifique par son ID')
            ->addOption('parcours', null, InputOption::VALUE_REQUIRED, 'Synchronise un parcours d\'un diplôme')
            ->addOption('annee', null, InputOption::VALUE_OPTIONAL, 'Année de synchronisation', date('Y'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('all-diplome') && $input->getOption('diplome')) {
            $io->error('Les options --all-diplome et --diplome ne peuvent pas être utilisées ensemble.');
            return Command::FAILURE;
        }


        // Demander la confirmation avant de continuer
        if (!$io->confirm('Êtes-vous sûr de vouloir continuer ? Les données seront supprimées.', false)) {
            $io->warning('Opération annulée.');
            return Command::FAILURE;
        }

        $annee = $input->getOption('annee') ?: date('Y');

        if ($input->getOption('all-diplome')) {
            // Logique pour la synchronisation de tous les diplômes
            $io->info('Synchronisation de tous les diplômes.');
            $this->synchroOreof->syncAllDiplomes();
            $io->success('Synchronisation terminée pour tous les diplômes.');
        }

        if ($input->getOption('diplome')) {
            $diplomeId = $input->getOption('diplome');
            // Logique pour synchroniser un diplôme spécifique
            $io->info(sprintf('Synchronisation du diplôme avec l\'ID : %s', $diplomeId));
            $this->synchroOreof->syncDiplome($diplomeId);
            $io->success('Synchronisation terminée pour le diplôme.');
        }

        if ($input->getOption('parcours')) {
            $parcoursId = $input->getOption('parcours');
            // Logique pour synchroniser les parcours
            $io->info(sprintf('Synchronisation du parcours avec l\'ID : %s', $parcoursId));
            $this->synchroOreof->syncParcours($parcoursId);
            $io->success('Synchronisation terminée pour le parcours.');
        }

        // Logique utilisant l'année spécifiée ou l'année courante par défaut
        $io->success(sprintf('Synchronisation pour l\'année : %s', $annee));

        return Command::SUCCESS;
    }
}
