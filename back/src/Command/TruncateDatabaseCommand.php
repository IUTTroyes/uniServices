<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'database:truncate',
    description: 'Vider complètement la base de données',
)]
class TruncateDatabaseCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');

        foreach ($schemaManager->listTableNames() as $tableName) {
            $connection->executeQuery('TRUNCATE TABLE ' . $tableName);
        }

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

        $io->success('La base de données a été vidée avec succès.');

        return Command::SUCCESS;
    }
}
