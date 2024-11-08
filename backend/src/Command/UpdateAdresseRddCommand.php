<?php

namespace App\Command;

use App\Repository\EtudiantRepository;
use App\Repository\RddDiplomeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-adresse-rdd',
    description: 'Mise à jour des adresses des étudiants pour la RDD',
)]
class UpdateAdresseRddCommand extends Command
{
    public function __construct(
        private readonly RddDiplomeRepository $diplomeRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EtudiantRepository $etudiantRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rdds = $this->diplomeRepository->findAll();

        foreach ($rdds as $rdd) {
            $etudiant = $this->etudiantRepository->findOneBy(['numEtudiant' => $rdd->getNumEtudiant()]);
            if (null !== $etudiant && null !== $etudiant->getAdresse()) {
                $etudiant->getAdresse()->setAdresse1($rdd->getAdresse());
                $etudiant->getAdresse()->setAdresse2($rdd->getAdresseComplement());
                $etudiant->getAdresse()->setCodePostal($rdd->getCodePostal());
                $etudiant->getAdresse()->setVille($rdd->getVille());
                $etudiant->getAdresse()->setPays($rdd->getPays());
                $io->success(sprintf('Mise à jour de l\'adresse de l\'étudiant %s', $etudiant->getNumEtudiant()));
            }
        }

        $this->entityManager->flush();

        $io->success('Adresses mises à jour.');

        return Command::SUCCESS;
    }
}
