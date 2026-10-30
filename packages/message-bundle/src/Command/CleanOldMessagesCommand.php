<?php

namespace MessageBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use MessageBundle\Entity\Message;
use MessageBundle\Repository\MessageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:message:clean-old',
    description: 'Supprime les messages plus anciens qu\'une durée donnée (par défaut 6 mois) ainsi que leurs destinataires et pièces jointes.',
    aliases: ['message:clean-old', 'app:message:purge-old']
)]
class CleanOldMessagesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MessageRepository $messageRepository,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'months',
                'm',
                InputOption::VALUE_REQUIRED,
                'Nombre de mois d\'ancienneté avant suppression (par défaut : 6)',
                6
            )
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Nombre de jours d\'ancienneté (si spécifié, prévaut sur l\'option --months)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Simuler la suppression sans modifier la base de données ni supprimer les fichiers sur le disque'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_REQUIRED,
                'Taille des lots de traitement pour limiter la consommation mémoire',
                100
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');
        $batchSize = max(10, min(1000, (int) $input->getOption('batch-size')));

        $days = $input->getOption('days');
        if (null !== $days && is_numeric($days) && (int) $days >= 0) {
            $daysVal = (int) $days;
            $cutoffDate = $daysVal === 0
                ? (new \DateTimeImmutable())->modify('+1 minute')
                : (new \DateTimeImmutable())->modify(sprintf('-%d days', $daysVal));
            $periodLabel = sprintf('%d jour(s)', $daysVal);
        } else {
            $monthsVal = max(1, (int) $input->getOption('months'));
            $cutoffDate = (new \DateTimeImmutable())->modify(sprintf('-%d months', $monthsVal));
            $periodLabel = sprintf('%d mois', $monthsVal);
        }

        $io->title('Nettoyage des anciens messages' . ($isDryRun ? ' [SIMULATION]' : ''));
        $io->text(sprintf('Date limite de conservation : %s (antérieurs à %s)', $cutoffDate->format('d/m/Y H:i:s'), $periodLabel));

        $totalMatching = $this->messageRepository->countMessagesOlderThan($cutoffDate);

        if ($totalMatching === 0) {
            $io->success('Aucun message antérieur à la date limite. Rien à nettoyer.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Nombre total de messages à supprimer : %d', $totalMatching));

        $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/messages';

        $deletedMessagesCount = 0;
        $deletedDestinatairesCount = 0;
        $deletedFilesCount = 0;
        $freedBytes = 0;

        if ($isDryRun) {
            // En mode simulation, on récupère un échantillon pour estimer les volumes
            $sample = $this->messageRepository->findMessagesOlderThan($cutoffDate, 500);
            $samplePjCount = 0;
            $sampleDestCount = 0;
            $sampleBytes = 0;

            foreach ($sample as $msg) {
                $sampleDestCount += $msg->getDestinataires()->count();
                foreach ($msg->getPiecesJointes() as $pj) {
                    $samplePjCount++;
                    $sampleBytes += $pj->getTaille();
                }
            }

            $io->table(
                ['Métrique', 'Estimation'],
                [
                    ['Messages à supprimer', (string) $totalMatching],
                    ['Destinataires associés (échantillon)', (string) $sampleDestCount],
                    ['Pièces jointes associées (échantillon)', (string) $samplePjCount],
                    ['Espace disque libéré estimé (échantillon)', $this->formatBytes($sampleBytes)],
                ]
            );

            $io->warning('Mode [DRY-RUN] actif : aucune suppression réelle n\'a été effectuée.');
            return Command::SUCCESS;
        }

        // Mode réel : traitement par lots successifs
        $io->progressStart($totalMatching);

        while (true) {
            $batch = $this->messageRepository->findMessagesOlderThan($cutoffDate, $batchSize);
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $message) {
                $deletedDestinatairesCount += $message->getDestinataires()->count();

                // Suppression physique des pièces jointes associées sur le disque
                foreach ($message->getPiecesJointes() as $pj) {
                    $fileName = $pj->getFichier();
                    if (!empty($fileName)) {
                        $filePath = $uploadDir . '/' . $fileName;
                        if (file_exists($filePath) && is_file($filePath)) {
                            $size = filesize($filePath) ?: $pj->getTaille();
                            if (@unlink($filePath)) {
                                $deletedFilesCount++;
                                $freedBytes += $size;
                            }
                        }
                    }
                }

                $this->em->remove($message);
                $deletedMessagesCount++;
                $io->progressAdvance();
            }

            $this->em->flush();
            $this->em->clear();
        }

        $io->progressFinish();

        $io->success(sprintf(
            'Nettoyage terminé avec succès : %d message(s) supprimé(s), %d relation(s) destinataire(s) purgée(s), %d pièce(s) jointe(s) supprimée(s) du disque (%s libérés).',
            $deletedMessagesCount,
            $deletedDestinatairesCount,
            $deletedFilesCount,
            $this->formatBytes($freedBytes)
        ));

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'Ko', 'Mo', 'Go', 'To'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return sprintf('%.2f %s', $bytes / (1024 ** $i), $units[$i]);
    }
}
