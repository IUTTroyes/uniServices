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
            ->addOption('edt', null, InputOption::VALUE_NONE, 'Execute only Edt')
            ->addOption('user', null, InputOption::VALUE_NONE, 'Execute only users')
            ->addOption('structure', null, InputOption::VALUE_NONE, 'Execute only structure')
            ->addOption('scolarite', null, InputOption::VALUE_NONE, 'Execute only structure')
            ->addOption('enseignement', null, InputOption::VALUE_NONE, 'Execute only enseignements')
            ->addOption('apc', null, InputOption::VALUE_NONE, 'Execute only APC');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('scolarite') && !$input->getOption('edt') && !$input->getOption('enseignement') && !$input->getOption('all') && !$input->getOption('user') && !$input->getOption('structure') && !$input->getOption('apc')) {
            $io->error('At least one option is required. Use --help to see available options.');
            return Command::FAILURE;
        }

        switch (true) {
            case $input->getOption('all'):
                $this->executeAll($io);
                break;

            case $input->getOption('user'):
                $this->executeUsers($io);
                break;

            case $input->getOption('structure'):
                $this->executeStructure($io);
                break;

            case $input->getOption('enseignement'):
                $this->executeEnseignements($io);
                break;

            case $input->getOption('apc'):
                $this->executeApc($io);
                break;
            case $input->getOption('edt'):
                $this->executeEdt($io);
                break;
        }

        $io->success('Transfert terminé.');

        return Command::SUCCESS;
    }

    private function executeAll(SymfonyStyle $io): void
    {
        $this->executeStructure($io);
        $this->executeApc($io);
        $this->executeEnseignements($io);
        $this->executeUsers($io);
        $this->executeEdt($io);
        $this->executeScolarite($io);
        $this->executePrevisionnel($io);
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

    private function executeEnseignements(SymfonyStyle $io): int
    {
        $io->info('Executing enseignements (ressources, SAE, matières...');
        $command = $this->getApplication()?->find('copy:transfert-bdd:enseignements');
        $arguments = [
            'command' => 'copy:transfert-bdd:enseignements',
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

    private function executeApc(SymfonyStyle $io): int
    {
        $io->info('Executing APC...');
        $command = $this->getApplication()?->find('copy:transfert-bdd:apc');
        $arguments = [
            'command' => 'copy:transfert-bdd:apc',
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

    private function executeEdt(SymfonyStyle $io): int
    {
        $io->info('Executing EDT...');
        $command = $this->getApplication()?->find('copy:transfert-bdd:edt');
        $arguments = [
            'command' => 'copy:transfert-bdd:edt',
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

    private function executeScolarite(SymfonyStyle $io): int
    {
        $io->info('Executing APC...');
        $command = $this->getApplication()?->find('copy:transfert-bdd:scolarite');
        $arguments = [
            'command' => 'copy:transfert-bdd:scolarite',
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

    private function executePrevisionnel(SymfonyStyle $io): int
    {
        $io->info('Executing Previsionnel...');
        $command = $this->getApplication()?->find('copy:transfert-bdd:previsionnel');
        $arguments = [
            'command' => 'copy:transfert-bdd:previsionnel',
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
}
