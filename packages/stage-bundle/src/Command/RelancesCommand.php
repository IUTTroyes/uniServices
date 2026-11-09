<?php

namespace StageBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageSoutenance;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Service\Notification\StageNotifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Relances périodiques (à lancer une fois par semaine, cf. stage:taches:installer) :
 *  - responsables de période : demandes déposées depuis plus de N jours (un rappel par période) ;
 *  - étudiants : convention envoyée depuis plus de N jours et pas revenue signée ;
 *  - étudiants : rapport absent alors que la soutenance approche, ou que le stage est terminé.
 */
#[AsCommand(name: 'stage:relances', description: 'Relance les demandes de convention en attente, les conventions non revenues et les rapports manquants.')]
final class RelancesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StageNotifier $notifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('jours-demande', null, InputOption::VALUE_REQUIRED, 'Ancienneté (jours) d\'une demande déposée avant relance du responsable.', '7')
            ->addOption('jours-convention', null, InputOption::VALUE_REQUIRED, 'Ancienneté (jours) d\'une convention envoyée avant relance de l\'étudiant.', '14')
            ->addOption('jours-rapport', null, InputOption::VALUE_REQUIRED, 'Relance du rapport N jours avant la soutenance (ou dans les N jours qui suivent la fin du stage).', '7')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Liste les relances sans rien envoyer.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $maintenant = new \DateTimeImmutable();
        $lignes = [];

        // 1. Demandes en attente, regroupées par période puis par responsable
        $joursDemande = max(1, (int) $input->getOption('jours-demande'));
        $parPeriode = [];
        foreach ($this->dossiers(EtatStageEnum::DEPOSE, 'dateDepotFormulaire', $maintenant->modify(sprintf('-%d days', $joursDemande))) as $stage) {
            $parPeriode[$stage->getStagePeriode()?->getId() ?? 0][] = $stage;
        }
        foreach ($parPeriode as $enAttente) {
            foreach ($this->notifier->responsables($enAttente[0]) as $responsable) {
                $lignes[] = ['Demandes en attente', $responsable->getDisplay(), \count($enAttente).' demande(s) — '.($enAttente[0]->getStagePeriode()?->getLibelle() ?? '?')];
                if (!$dryRun) {
                    $this->notifier->relanceDemandes($responsable, $enAttente, $joursDemande);
                }
            }
        }

        // 2. Conventions envoyées, pas revenues
        $joursConvention = max(1, (int) $input->getOption('jours-convention'));
        foreach ($this->dossiers(EtatStageEnum::CONVENTION_ENVOYEE, 'dateConventionEnvoyee', $maintenant->modify(sprintf('-%d days', $joursConvention))) as $stage) {
            $lignes[] = ['Convention non revenue', $stage->getEtudiant()?->getDisplay() ?? '?', 'envoyée le '.$stage->getDateConventionEnvoyee()?->format('d/m/Y')];
            if (!$dryRun) {
                $this->notifier->relanceConvention($stage);
            }
        }

        // 3. Rapports manquants : soutenance dans les N jours, ou stage terminé depuis moins de N jours (sans soutenance)
        $joursRapport = max(1, (int) $input->getOption('jours-rapport'));
        $limite = $maintenant->modify(sprintf('+%d days', $joursRapport));
        $relances = [];
        /** @var list<StageSoutenance> $soutenances */
        $soutenances = $this->em->createQueryBuilder()->select('so', 's')->from(StageSoutenance::class, 'so')->join('so.stageEtudiant', 's')
            ->where('so.dateSoutenance BETWEEN :maintenant AND :limite')->andWhere('s.reportUploaded = false')
            ->setParameter('maintenant', $maintenant)->setParameter('limite', $limite)
            ->getQuery()->getResult();
        foreach ($soutenances as $soutenance) {
            $stage = $soutenance->getStageEtudiant();
            if ($stage !== null) {
                $relances[$stage->getId()] = [$stage, $soutenance->getDateSoutenance()];
            }
        }
        /** @var list<StageEtudiant> $termines */
        $termines = $this->em->createQueryBuilder()->select('s')->from(StageEtudiant::class, 's')
            ->where('s.reportUploaded = false')->andWhere('s.etatStage = :recue')
            ->andWhere('s.dateFinStage BETWEEN :debut AND :maintenant')
            ->setParameter('recue', EtatStageEnum::CONVENTION_RECUE)
            ->setParameter('debut', $maintenant->modify(sprintf('-%d days', $joursRapport)))->setParameter('maintenant', $maintenant)
            ->getQuery()->getResult();
        foreach ($termines as $stage) {
            $relances[$stage->getId()] ??= [$stage, null];
        }
        foreach ($relances as [$stage, $date]) {
            $lignes[] = ['Rapport manquant', $stage->getEtudiant()?->getDisplay() ?? '?', $date !== null ? 'soutenance le '.$date->format('d/m/Y') : 'stage terminé le '.$stage->getDateFinStage()?->format('d/m/Y')];
            if (!$dryRun) {
                $this->notifier->relanceRapport($stage, $date);
            }
        }

        if ($lignes === []) {
            $io->success('Aucune relance à envoyer.');

            return Command::SUCCESS;
        }
        $io->table(['Relance', 'Destinataire', 'Détail'], $lignes);
        $io->success(sprintf('%d relance(s) %s.', \count($lignes), $dryRun ? 'à envoyer (simulation)' : 'envoyée(s)'));

        return Command::SUCCESS;
    }

    /** @return list<StageEtudiant> dossiers dans l'état donné dont la date indiquée est antérieure au seuil */
    private function dossiers(EtatStageEnum $etat, string $champDate, \DateTimeImmutable $seuil): array
    {
        return $this->em->createQueryBuilder()->select('s')->from(StageEtudiant::class, 's')
            ->where('s.etatStage = :etat')->andWhere(sprintf('s.%s < :seuil', $champDate))
            ->setParameter('etat', $etat)->setParameter('seuil', $seuil)
            ->orderBy('s.id')->getQuery()->getResult();
    }
}
