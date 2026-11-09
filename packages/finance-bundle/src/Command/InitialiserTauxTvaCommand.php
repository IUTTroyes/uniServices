<?php

namespace FinanceBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Repository\FinanceTauxTvaRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Crée (sans doublon) les taux de TVA en vigueur. À lancer une fois par environnement après déploiement. */
#[AsCommand(name: 'finance:tva:initialiser', description: 'Crée les taux de TVA par défaut (20 / 10 / 5,5 / 2,1 / 0 %) absents.')]
final class InitialiserTauxTvaCommand extends Command
{
    public function __construct(
        private readonly FinanceTauxTvaRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $crees = $this->repository->initialiser();
        $this->em->flush();
        (new SymfonyStyle($input, $output))->success($crees === 0 ? 'Les taux de TVA existent déjà.' : sprintf('%d taux de TVA créé(s).', $crees));

        return Command::SUCCESS;
    }
}
