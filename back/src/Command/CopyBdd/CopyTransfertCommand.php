<?php

namespace App\Command\CopyBdd;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'copy:transfert:main',
    description: 'Transfert la base de donées de l\'intranet V3 au format V4',
)]
class CopyTransfertCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', null, InputOption::VALUE_NONE, 'Execute all commands in order')
            ->addOption('users', null, InputOption::VALUE_NONE, 'Execute only users')
            ->addOption('structure', null, InputOption::VALUE_NONE, 'Execute only structure')
            ->addOption('apc', null, InputOption::VALUE_NONE, 'Execute only APC');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('all') && !$input->getOption('users') && !$input->getOption('structure') && !$input->getOption('apc')) {
            $io->error('At least one option is required. Use --help to see available options.');
            return Command::FAILURE;
        }

        if ($input->getOption('all')) {
            $this->executeAll($io);
        } elseif ($input->getOption('users')) {
            $this->executeUsers($io);
        } elseif ($input->getOption('structure')) {
            $this->executeStructure($io);
        } elseif ($input->getOption('apc')) {
            $this->executeApc($io);
        }

        $io->success('Transfert terminé.');

        return Command::SUCCESS;
    }

    private function executeAll(SymfonyStyle $io): void
    {
        $this->executeUsers($io);
        $this->executeStructure($io);
        $this->executeApc($io);
    }

    private function executeUsers(SymfonyStyle $io): int
    {
        $io->info('Executing users...');
        $command = $this->getApplication()?->find('copy:transfert-bdd:user');
        $arguments = [
            'command' => 'copy:transfert-bdd:user',
        ];

        $arrayInput = new ArrayInput($arguments);
        $bufferedOutput = new BufferedOutput();

        // Run the command
        $returnCode = $command->run($arrayInput, $bufferedOutput);

        // Get the output of the command
        $content = $bufferedOutput->fetch();
        $io->success('Output of the other command: ' . $content);


        return $returnCode;
    }

    private function executeStructure(SymfonyStyle $io): int
    {
        $io->info('Executing structure...');
        $command = $this->getApplication()?->find('copy:transfert-bdd:structure');

        if (!$io->confirm('Do you really want to execute this command?', false)) {
            $io->warning('Command execution aborted.');
            return Command::FAILURE;
        }

        $arguments = [
            'command' => 'copy:transfert-bdd:structure',
            '--force' => '--force',
        ];

        $arrayInput = new ArrayInput($arguments);
        $bufferedOutput = new BufferedOutput();

        // Run the command
        $returnCode = $command->run($arrayInput, $bufferedOutput);

        // Get the output of the command
        $content = $bufferedOutput->fetch();
        $io->success('Output of the other command: ' . $content);


        return $returnCode;
    }

    private function executeApc(SymfonyStyle $io): void
    {
        // Logic to execute APC
        $io->info('Executing APC...');
    }
}
