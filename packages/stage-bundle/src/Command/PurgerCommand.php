<?php

namespace StageBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageEvaluationEntreprise;
use StageBundle\Entity\Stages\StageSoutenance;
use StageBundle\Service\StageRapportStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Durée de conservation des dossiers de stage : au-delà de N années (5 par défaut) après la fin du stage (à défaut,
 * la fin de sa période), le dossier est anonymisé (StageEtudiant::anonymiser), son rapport effacé du disque, son tuteur
 * entreprise et l'évaluation de l'entreprise supprimés, ses créneaux de soutenance détachés. Les statistiques (période, entreprise, durée,
 * gratification, statut, note) restent disponibles.
 */
#[AsCommand(name: 'stage:purger', description: 'Anonymise les dossiers de stage terminés depuis plus de N années et efface leurs rapports.')]
final class PurgerCommand extends Command
{
    private const LOT = 100;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StageRapportStorage $storage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('annees', null, InputOption::VALUE_REQUIRED, 'Durée de conservation, en années après la fin du stage.', '5')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Compte les dossiers concernés sans rien modifier.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $annees = max(1, (int) $input->getOption('annees'));
        $seuil = new \DateTimeImmutable(sprintf('-%d years', $annees));

        $qb = $this->em->createQueryBuilder()->select('s')->from(StageEtudiant::class, 's')
            ->leftJoin('s.stagePeriode', 'p')
            ->where('s.anonymiseLe IS NULL')
            ->andWhere('COALESCE(s.dateFinStage, p.dateFin) < :seuil')
            ->setParameter('seuil', $seuil)
            ->orderBy('s.id');

        if ($input->getOption('dry-run')) {
            $nombre = (int) (clone $qb)->select('COUNT(s.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
            $io->success(sprintf('%d dossier(s) terminé(s) avant le %s seraient anonymisés (simulation).', $nombre, $seuil->format('d/m/Y')));

            return Command::SUCCESS;
        }

        $total = 0;
        do {
            /** @var list<StageEtudiant> $lot */
            $lot = (clone $qb)->setMaxResults(self::LOT)->getQuery()->getResult();
            $fichiers = [];
            foreach ($lot as $stage) {
                $fichiers[] = $stage->getReportPath();
                if ($stage->getTuteur() !== null) {
                    $this->em->remove($stage->getTuteur());
                }
                // Évaluation de l'entreprise : nom de l'évaluateur et appréciations sur l'étudiant.
                $this->em->createQueryBuilder()->delete(StageEvaluationEntreprise::class, 'ev')
                    ->where('ev.stageEtudiant = :stage')->setParameter('stage', $stage)->getQuery()->execute();
                $this->em->createQueryBuilder()->update(StageSoutenance::class, 'so')->set('so.stageEtudiant', ':null')
                    ->where('so.stageEtudiant = :stage')->setParameter('null', null)->setParameter('stage', $stage)
                    ->getQuery()->execute();
                $stage->anonymiser();
            }
            $this->em->flush();
            // Fichiers effacés une fois l'anonymisation enregistrée.
            foreach ($fichiers as $chemin) {
                $this->storage->supprimer($chemin);
            }
            $total += \count($lot);
            $this->em->clear();
        } while (\count($lot) === self::LOT);

        $io->success(sprintf('%d dossier(s) terminé(s) avant le %s anonymisé(s).', $total, $seuil->format('d/m/Y')));

        return Command::SUCCESS;
    }
}
