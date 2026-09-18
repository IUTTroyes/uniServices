<?php

namespace DocumentBundle\Command;

use App\Migration\IntranetV3\MigrationContext;
use DocumentBundle\Migration\IntranetV3\DocumentDatabaseResetter;
use DocumentBundle\Migration\IntranetV3\DocumentIntegrityChecker;
use DocumentBundle\Migration\IntranetV3\DocumentFileMigrator;
use DocumentBundle\Migration\IntranetV3\DocumentMigrationRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'app:migrate-intranet-v3-document', description: 'Migre les catégories et documents de l’intranet V3.')]
final class MigrateIntranetV3DocumentCommand extends Command
{
    public function __construct(
        private readonly DocumentMigrationRunner $runner,
        private readonly DocumentDatabaseResetter $resetter,
        private readonly DocumentIntegrityChecker $integrityChecker,
        private readonly DocumentFileMigrator $fileMigrator,
        private readonly KernelInterface $kernel,
    ) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('migration', InputArgument::OPTIONAL, 'Migration Document à exécuter.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Exécute puis annule les écritures.')
            ->addOption('list', null, InputOption::VALUE_NONE, 'Liste les migrations Document disponibles.')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Contrôle les cardinalités V3/UniServices sans importer.')
            ->addOption('files-source', null, InputOption::VALUE_REQUIRED, 'Dossier V3 contenant public/upload/documents/.')
            ->addOption('files-target', null, InputOption::VALUE_REQUIRED, 'Dossier cible dans lequel copier/contrôler les fichiers.')
            ->addOption('files-only', null, InputOption::VALUE_NONE, 'Ne traite que les fichiers physiques (nécessite --files-source et --files-target).')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Vide les tables du bundle Document avant import (dev/test uniquement).')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore la confirmation de --reset.')
            ->addOption('no-progress', null, InputOption::VALUE_NONE, 'Désactive les barres de progression.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('list')) { $io->title('Migrations intranet V3 — Document'); $io->listing($this->runner->names()); return Command::SUCCESS; }
        if ($input->getOption('check')) {
            $status = $this->displayIntegrityReport($io);
            if (null !== $input->getOption('files-target')) {
                $fileStatus = $this->displayFileCheck($io, (string) $input->getOption('files-target'));
                if (Command::SUCCESS !== $fileStatus) $status = $fileStatus;
            }
            return $status;
        }

        $filesSource = $input->getOption('files-source');
        $filesTarget = $input->getOption('files-target');
        if ($input->getOption('files-only')) {
            if (null === $filesSource || null === $filesTarget) {
                $io->error('--files-only nécessite --files-source et --files-target.');
                return Command::INVALID;
            }
            return $this->migrateFiles($io, (string) $filesSource, (string) $filesTarget, (bool) $input->getOption('dry-run'));
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $reset = (bool) $input->getOption('reset');
        if ($reset && $dryRun) { $io->error('--reset et --dry-run ne peuvent pas être utilisés ensemble.'); return Command::INVALID; }
        if ($reset && !in_array($this->kernel->getEnvironment(), ['dev', 'test'], true)) { $io->error('--reset est réservé aux environnements dev/test.'); return Command::FAILURE; }

        $prerequisites = $this->runner->checkPrerequisites();
        $missing = array_filter($prerequisites, static fn (int $count): bool => 0 === $count);
        $io->title('Migration intranet V3 — Document');
        $io->section('Prérequis Core');
        foreach ($prerequisites as $name => $count) $io->writeln(sprintf(' %s %-24s %d', $count > 0 ? '✓' : '✗', $name, $count));
        if ([] !== $missing) { $io->error('Import Core incomplet : les départements doivent être migrés avant les documents.'); return Command::FAILURE; }

        if ($reset) {
            if (!$input->getOption('force') && !$io->confirm('Vider les tables du bundle Document ?', false)) return Command::SUCCESS;
            $io->success(sprintf('%d table(s) Document vidée(s).', $this->resetter->reset()));
        }

        $currentMigration = null; $progress = null; $step = 0;
        $progressEnabled = !$input->getOption('no-progress') && !$output->isQuiet();
        $context = new MigrationContext(
            dryRun: $dryRun,
            verbose: $output->isVerbose(),
            onMigrationStart: function (string $name) use (&$currentMigration, &$step, $io): void { $currentMigration = $name; ++$step; $io->writeln(sprintf('\n[%d] <info>▶ %s</info>', $step, $name)); },
            onMigrationFinish: function (string $name) use ($io): void { $io->writeln(sprintf('    <info>✓ %s terminé</info>', $name)); },
            onProgressStart: function (string $label, int $total) use (&$progress, &$currentMigration, $output, $progressEnabled): void {
                if (!$progressEnabled) return;
                $progress = new ProgressBar($output, $total);
                $progress->setFormat(sprintf('    %%current%%/%%max%% [%%bar%%] %%percent:3s%%%% — %s · %s', $currentMigration ?? 'document', $label));
                $progress->start();
            },
            onProgressAdvance: function (int $advance) use (&$progress): void { $progress?->advance($advance); },
            onProgressFinish: function () use (&$progress): void { if (null !== $progress) { $progress->finish(); $progress = null; } },
        );

        try { $results = $this->runner->run($input->getArgument('migration'), $context); }
        catch (\Throwable $e) { $io->error($e->getMessage()); return Command::FAILURE; }

        $rows = [];
        foreach ($results as $name => $result) {
            $rows[] = [$name, $result->created, $result->updated, $result->skipped, $result->failed, $result->total()];
            if ($output->isVerbose()) foreach ($result->messages as $message) $io->writeln('  <comment>'.$message.'</comment>');
        }
        $io->newLine();
        $io->table(['Migration', 'Created', 'Updated', 'Skipped', 'Failed', 'Total'], $rows);
        if ($dryRun) $io->note('Dry-run : toutes les écritures ont été annulées.');
        else $this->displayIntegrityReport($io, false);

        if (null !== $filesSource || null !== $filesTarget) {
            if (null === $filesSource || null === $filesTarget) {
                $io->error('Pour migrer les fichiers, --files-source et --files-target doivent être fournis ensemble.');
                return Command::INVALID;
            }
            $fileStatus = $this->migrateFiles($io, (string) $filesSource, (string) $filesTarget, $dryRun);
            if (Command::SUCCESS !== $fileStatus) return $fileStatus;
        } else {
            $io->note('Métadonnées migrées. Pour copier les fichiers V3, fournir --files-source=<ancien public/upload/documents> et --files-target=<stockage cible>.');
        }
        return Command::SUCCESS;
    }

    private function migrateFiles(SymfonyStyle $io, string $source, string $target, bool $dryRun): int
    {
        $io->section($dryRun ? 'Simulation de migration des fichiers' : 'Migration des fichiers physiques');
        try { $result = $this->fileMigrator->migrate($source, $target, $dryRun); }
        catch (\Throwable $e) { $io->error($e->getMessage()); return Command::FAILURE; }

        $io->table(['Attendus', $dryRun ? 'Copiables' : 'Copiés', 'Déjà présents', 'Manquants V3', 'Échecs'], [[
            $result['expected'], $result['copied'], $result['existing'], $result['missing'], $result['failed'],
        ]]);
        if ([] !== $result['missingFiles']) $io->warning("Fichiers absents de V3 (100 premiers) :\n".implode("\n", $result['missingFiles']));
        if ([] !== $result['errors']) $io->error("Erreurs de copie (100 premières) :\n".implode("\n", $result['errors']));
        if ($dryRun) $io->note('Dry-run fichiers : aucune copie effectuée.');

        return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function displayFileCheck(SymfonyStyle $io, string $directory): int
    {
        $io->section('Contrôle des fichiers physiques');
        try { $result = $this->fileMigrator->check($directory); }
        catch (\Throwable $e) { $io->error($e->getMessage()); return Command::FAILURE; }
        $io->table(['Attendus', 'Présents', 'Manquants', 'Taille différente'], [[
            $result['expected'], $result['present'], $result['missing'], $result['wrongSize'],
        ]]);
        if ([] !== $result['missingFiles']) $io->warning("Fichiers manquants (100 premiers) :\n".implode("\n", $result['missingFiles']));
        if ([] !== $result['wrongSizeFiles']) $io->warning("Tailles différentes (100 premières) :\n".implode("\n", $result['wrongSizeFiles']));
        return ($result['missing'] > 0 || $result['wrongSize'] > 0) ? Command::FAILURE : Command::SUCCESS;
    }

    private function displayIntegrityReport(SymfonyStyle $io, bool $withTitle = true): int
    {
        if ($withTitle) $io->title('Contrôle d’intégrité — migration Document');
        else $io->section('Contrôle d’intégrité');
        try { $checks = $this->integrityChecker->check(); }
        catch (\Throwable $e) { $io->error('Contrôle impossible : '.$e->getMessage()); return Command::FAILURE; }

        $rows = []; $hasErrors = false;
        foreach ($checks as $check) { $rows[] = [$check['ok'] ? '✓' : '✗', $check['label'], $check['source'], $check['target']]; $hasErrors = $hasErrors || !$check['ok']; }
        $io->table(['', 'Contrôle', 'V3', 'UniServices'], $rows);
        if ($hasErrors) { $io->warning('Des écarts subsistent entre V3 et UniServices.'); return Command::FAILURE; }
        $io->success('Les cardinalités documentaires sont cohérentes avec V3.');
        return Command::SUCCESS;
    }
}
