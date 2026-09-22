<?php

namespace App\Command;

use App\Initialization\InitializationRunner;
use App\Migration\IntranetV3\DatabaseResetter;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationRegistry;
use App\Migration\IntranetV3\MigrationRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:migrate-intranet-v3',
    description: 'Migrates data from intranet V3 to uniServices.',
)]
final class MigrateIntranetV3Command extends Command
{
    public function __construct(
        private readonly MigrationRunner $runner,
        private readonly MigrationRegistry $registry,
        private readonly DatabaseResetter $databaseResetter,
        private readonly InitializationRunner $initializationRunner,
        #[Autowire(param: 'kernel.environment')]
        private readonly string $kernelEnvironment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('migration', InputArgument::OPTIONAL, 'Migration name. Omit to run all migrations.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without writing data.')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available migrations.')
            ->addOption('no-progress', null, InputOption::VALUE_NONE, 'Disable progress bars.')
            ->addOption('reset-db', null, InputOption::VALUE_NONE, 'Empty the target application tables and stop (dev/test only).')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation for --reset-db.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('list')) {
            $names = array_keys($this->registry->all());
            sort($names);
            $io->listing($names ?: ['No migration registered.']);

            return Command::SUCCESS;
        }

        if ($input->getOption('reset-db')) {
            if ($input->getOption('dry-run')) {
                $io->error('--reset-db and --dry-run cannot be used together: resetting the database is destructive.');

                return Command::INVALID;
            }

            if (!in_array($this->kernelEnvironment, ['dev', 'test'], true)) {
                $io->error(sprintf('--reset-db is restricted to dev/test environments (current environment: %s).', $this->kernelEnvironment));

                return Command::INVALID;
            }

            if (!$input->getOption('force')) {
                $confirmed = $io->confirm(
                    sprintf('Empty the target database application tables in %s before importing?', $this->kernelEnvironment),
                    false,
                );
                if (!$confirmed) {
                    $io->warning('Database reset cancelled. No migration was run.');

                    return Command::SUCCESS;
                }
            }

            try {
                $tableCount = $this->databaseResetter->reset();
                $io->success(sprintf('Target database reset: %d application table(s) emptied. Doctrine migration history was preserved.', $tableCount));

                return Command::SUCCESS;
            } catch (\Throwable $exception) {
                $io->error('Unable to reset target database: ' . $exception->getMessage());

                return Command::FAILURE;
            }
        }

        $migration = $input->getArgument('migration');

        $initializationResults = [];
        if (null === $migration) {
            try {
                $initializationResults = $this->initializationRunner->run(new MigrationContext(
                    dryRun: (bool) $input->getOption('dry-run'),
                    verbose: $output->isVerbose(),
                ));
            } catch (\Throwable $exception) {
                $io->error('Application initialization failed: ' . $exception->getMessage());

                return Command::FAILURE;
            }
        }
        $progressEnabled = !$input->getOption('no-progress') && !$output->isQuiet();

        try {
            $plan = $this->runner->plan($migration);
        } catch (\InvalidArgumentException|\LogicException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        $overall = null;
        $detail = null;
        $currentMigration = null;
        $migrationIndex = 0;
        $migrationCount = count($plan);

        if ($progressEnabled && [] !== $plan) {
            $io->section(sprintf('Migration intranet V3 — %d étape(s)', $migrationCount));
        }

        $context = new MigrationContext(
            dryRun: (bool) $input->getOption('dry-run'),
            verbose: $output->isVerbose(),
            onMigrationStart: static function (string $name) use ($output, $progressEnabled, &$currentMigration, &$migrationIndex, $migrationCount): void {
                $currentMigration = $name;
                ++$migrationIndex;

                if ($progressEnabled) {
                    $output->writeln('');
                    $output->writeln(sprintf(
                        '<info>[%d/%d] ▶ %s</info>',
                        $migrationIndex,
                        $migrationCount,
                        $name,
                    ));
                }
            },
            onMigrationFinish: static function (string $name) use ($output, $progressEnabled): void {
                if ($progressEnabled) {
                    $output->writeln(sprintf('<info>      ✓ %s terminé</info>', $name));
                }
            },
            onProgressStart: static function (string $label, int $total) use ($output, $progressEnabled, &$detail, &$currentMigration): void {
                if (!$progressEnabled) {
                    return;
                }

                if (null !== $detail) {
                    $detail->finish();
                    $output->writeln('');
                }

                $detail = new ProgressBar($output, $total);
                if ($total > 0) {
                    $detail->setFormat(sprintf(
                        '      %%message%% — %%current%%/%%max%% [%%bar%%] %%percent:3s%%%%',
                    ));
                } else {
                    $detail->setFormat('      %message% — %current% lignes traitées');
                }
                $detail->setMessage(sprintf('%s · %s', $currentMigration ?? 'migration', $label));
                $detail->start();
            },
            onProgressAdvance: static function (int $step) use (&$detail): void {
                if (null !== $detail) {
                    $detail->advance($step);
                }
            },
            onProgressFinish: static function () use ($output, &$detail): void {
                if (null !== $detail) {
                    $detail->finish();
                    $output->writeln('');
                    $detail = null;
                }
            },
        );

        try {
            $results = $this->runner->run($migration, $context);
        } catch (\InvalidArgumentException|\LogicException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        } finally {
            if (null !== $detail) {
                $detail->finish();
                $output->writeln('');
                $detail = null;
            }
        }

        if ($results === [] && $initializationResults === []) {
            $io->warning('No migration is registered yet.');

            return Command::SUCCESS;
        }

        $rows = [];
        $hasFailure = false;

        foreach ($initializationResults as $name => $result) {
            $rows[] = [$name, $result->created, $result->updated, $result->skipped, $result->failed, $result->total()];
            $hasFailure = $hasFailure || $result->failed > 0;
        }
        foreach ($results as $name => $result) {
            $rows[] = [$name, $result->created, $result->updated, $result->skipped, $result->failed, $result->total()];
            $hasFailure = $hasFailure || $result->failed > 0;

            if ($context->verbose) {
                foreach ($result->messages as $message) {
                    $io->writeln(sprintf('<comment>%s:</comment> %s', $name, $message));
                }
            }
        }

        $io->section('Bilan de la migration');
        $io->table(['Migration', 'Created', 'Updated', 'Skipped', 'Failed', 'Total'], $rows);

        if ($context->dryRun) {
            $io->note('Dry-run enabled: migrators must not persist changes.');
        }

        return $hasFailure ? Command::FAILURE : Command::SUCCESS;
    }
}
