<?php

namespace UnifolioBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UnifolioBundle\Service\Migration\V1\MigrationV1;
use UnifolioBundle\Service\Migration\V1\RapportMigration;
use UnifolioBundle\Service\Migration\V1\V1Reader;

#[AsCommand(
    name: 'unifolio:migrate:v1',
    description: 'Reprend les données d\'UniFolio v1 (traces, évaluations, commentaires, portfolios, CV) depuis une base v1 lue en lecture seule.',
)]
final class MigrerV1Command extends Command
{
    public function __construct(
        private readonly MigrationV1 $migration,
        private readonly Connection $connexionApplication,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dsn', null, InputOption::VALUE_REQUIRED, 'DSN de la base v1 (sinon variable UNIFOLIO_V1_DATABASE_URL).')
            ->addOption('files-dir', null, InputOption::VALUE_REQUIRED, 'Dossier des fichiers v1 (sinon variable UNIFOLIO_V1_FILES_DIR).')
            ->addOption('step', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Étape(s) : '.implode(', ', MigrationV1::ETAPES).' ou all (défaut).', ['all'])
            ->addOption('limite', null, InputOption::VALUE_REQUIRED, 'Ne traiter que les N premiers étudiants v1 (essai).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Exécute puis annule toutes les écritures (et supprime les fichiers copiés).')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Vérifie seulement la compatibilité du schéma de la base v1.')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Supprime ce que la migration a créé (traces, portfolios, CV) pour repartir de zéro.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ne pas demander de confirmation pour --purge.')
            ->addOption('rapport', null, InputOption::VALUE_REQUIRED, 'Écrit le rapport d\'écarts dans ce fichier CSV.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rapport = new RapportMigration();

        if ($input->getOption('purge')) {
            if (!$input->getOption('force') && !$io->confirm('Supprimer toutes les traces, portfolios et CV créés par la migration ?', false)) {
                return Command::SUCCESS;
            }
            $this->migration->purger($rapport);
            $this->afficherEcarts($io, $rapport);

            return Command::SUCCESS;
        }

        $dsn = (string) ($input->getOption('dsn') ?: ($_ENV['UNIFOLIO_V1_DATABASE_URL'] ?? $_SERVER['UNIFOLIO_V1_DATABASE_URL'] ?? ''));
        if ($dsn === '') {
            $io->error('Indiquez la base v1 avec --dsn ou la variable UNIFOLIO_V1_DATABASE_URL.');

            return Command::INVALID;
        }
        try {
            $v1 = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql', 'mariadb' => 'pdo_mysql']))->parse($dsn));
            $v1->executeQuery('SELECT 1');
        } catch (\Throwable $e) {
            $io->error('Connexion à la base v1 impossible : '.$e->getMessage());

            return Command::FAILURE;
        }
        // Garde-fou : jamais la base de l'application (la lecture est sans écriture, mais autant ne pas se tromper).
        $a = $this->connexionApplication->getParams();
        $b = $v1->getParams();
        if (($a['dbname'] ?? null) === ($b['dbname'] ?? null) && ($a['host'] ?? null) === ($b['host'] ?? null) && ($a['port'] ?? null) === ($b['port'] ?? null)) {
            $io->error('La base v1 est la base de l\'application : abandon.');

            return Command::FAILURE;
        }

        $lecteur = new V1Reader($v1);
        $manques = $lecteur->verifierSchema();
        if ($manques !== []) {
            $io->error('Le schéma de la base v1 ne correspond pas à celui attendu :');
            $io->listing($manques);

            return Command::FAILURE;
        }
        $io->success('Schéma de la base v1 compatible.');
        if ($input->getOption('check')) {
            return Command::SUCCESS;
        }

        $dossier = (string) ($input->getOption('files-dir') ?: ($_ENV['UNIFOLIO_V1_FILES_DIR'] ?? $_SERVER['UNIFOLIO_V1_FILES_DIR'] ?? ''));
        if ($dossier === '' || !is_dir($dossier)) {
            $io->error('Dossier des fichiers v1 introuvable : indiquez --files-dir ou UNIFOLIO_V1_FILES_DIR.');

            return Command::INVALID;
        }

        $demandees = (array) $input->getOption('step');
        $etapes = \in_array('all', $demandees, true) ? MigrationV1::ETAPES : array_values(array_intersect($demandees, MigrationV1::ETAPES));
        if ($etapes === [] || array_diff($demandees, [...MigrationV1::ETAPES, 'all']) !== []) {
            $io->error('Étape inconnue. Étapes : '.implode(', ', MigrationV1::ETAPES).', all.');

            return Command::INVALID;
        }
        $essai = (bool) $input->getOption('dry-run');
        $io->title(sprintf('Migration UniFolio v1%s — étapes : %s', $essai ? ' (essai, rien n\'est conservé)' : '', implode(', ', $etapes)));

        $limite = $input->getOption('limite') !== null ? max(1, (int) $input->getOption('limite')) : null;
        $this->migration->executer($lecteur, $dossier, $etapes, $rapport, $essai, $limite);

        $lignes = [];
        foreach ($rapport->compteurs() as $type => $etats) {
            foreach ($etats as $etat => $n) {
                $lignes[] = [$type, $etat, $n];
            }
        }
        $io->table(['Objet', 'Résultat', 'Nombre'], $lignes);
        $this->afficherEcarts($io, $rapport);
        if ($chemin = $input->getOption('rapport')) {
            file_put_contents((string) $chemin, $rapport->csv());
            $io->writeln(sprintf('Rapport d\'écarts écrit dans %s', $chemin));
        }

        return $rapport->nbEcarts('erreur') > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function afficherEcarts(SymfonyStyle $io, RapportMigration $rapport): void
    {
        $io->writeln(sprintf('%d erreur(s), %d avertissement(s), %d information(s).', $rapport->nbEcarts('erreur'), $rapport->nbEcarts('avertissement'), $rapport->nbEcarts('info')));
        foreach (array_slice($rapport->ecarts(), 0, 25) as $e) {
            $io->writeln(sprintf(' [%s] %s %s : %s', $e['gravite'], $e['type'], $e['v1Id'] ?? '', $e['message']));
        }
        if (\count($rapport->ecarts()) > 25) {
            $io->writeln(sprintf(' … %d autre(s) : voir --rapport.', \count($rapport->ecarts()) - 25));
        }
    }
}
