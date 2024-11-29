<?php

namespace App\Command\CopyBdd;

use App\Entity\Structure\StructureDepartement;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'copy:transfert-bdd',
    description: 'Add a short description for your command',
)]
class CopyTransfertBddCommand extends Command
{
    protected $em;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        ManagerRegistry                  $managerRegistry
    )
    {
        parent::__construct();
        $this->em = $managerRegistry->getConnection('copy');
    }

    protected function configure(): void
    {
    }

    private function effacerTables()
    {
        // vider les tables de destination et les réinitialiser
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=0');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_departement');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->effacerTables();

        // Départements
        $sql = "SELECT * FROM departement";
        $departements = $this->em->executeQuery($sql)->fetchAllAssociative();
        // dd($departements);

        // parcourir les départements et les insérer dans la base de données de destination avec les mêmes données et l'entité correspondante StructureDepartement

        foreach ($departements as $dept) {
            $departement = new StructureDepartement();
            $departement->setLibelle($dept['libelle']);
            $departement->setLogoName($dept['logo_name']);
            $departement->setTelContact($dept['tel_contact']);
            $departement->setCouleur($dept['couleur']);
            $departement->setSiteWeb($dept['site_web']);
            $departement->setDescription($dept['description']);
            $departement->setActif($dept['actif']);
            $departement->setOpt([
                'materiel' => (bool)$dept['opt_materiel'],
                'edt' => (bool)$dept['opt_edt'],
                'stage' => (bool)$dept['opt_stage'],
            ]);


            $this->entityManager->persist($departement);
            $io->info('Département : ' . $dept['libelle'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();


        $io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }
}
