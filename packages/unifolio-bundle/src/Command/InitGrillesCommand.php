<?php

namespace UnifolioBundle\Command;

use App\Entity\Structure\StructureTypeDiplome;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Repository\GrilleRepository;
use UnifolioBundle\Service\Grille\GrillesParDefaut;

#[AsCommand(
    name: 'unifolio:grilles:init',
    description: 'Crée les grilles nationales BUT par défaut (une par granularité). Sans effet sur les grilles existantes.',
)]
final class InitGrillesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GrilleRepository $grilles,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche ce qui serait créé sans rien enregistrer.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $types = $this->em->getRepository(StructureTypeDiplome::class)->findBy(['apc' => true]);
        if ($types === []) {
            $io->warning('Aucun type de diplôme APC (BUT) n\'est défini.');

            return Command::SUCCESS;
        }

        $crees = 0;
        foreach ($types as $type) {
            foreach (GranulariteEnum::cases() as $granularite) {
                $libelle = GrillesParDefaut::DEFINITIONS[$granularite->value]['libelle'];
                $existe = $this->grilles->findOneBy(['typeDiplome' => $type, 'departement' => null, 'diplome' => null, 'libelle' => $libelle]);
                if ($existe !== null) {
                    $io->writeln(sprintf(' <comment>déjà présente</comment> %s (%s)', $libelle, $type->getSigle()));
                    continue;
                }
                $io->writeln(sprintf(' <info>création</info> %s (%s)', $libelle, $type->getSigle()));
                if (!$input->getOption('dry-run')) {
                    $this->em->persist(GrillesParDefaut::construire($type, $granularite));
                }
                ++$crees;
            }
        }

        if (!$input->getOption('dry-run')) {
            $this->em->flush();
        }
        $io->success(sprintf('%d grille(s) %s.', $crees, $input->getOption('dry-run') ? 'à créer' : 'créée(s) et publiée(s)'));

        return Command::SUCCESS;
    }
}
