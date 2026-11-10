<?php

namespace StageBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Service\StageRapportStorage;

/**
 * Efface le rapport déposé quand son dossier est supprimé (directement, ou avec sa période) : pas de données
 * personnelles orphelines sur le disque. Le fichier n'est effacé qu'après le flush, quand la suppression est acquise.
 */
#[AsDoctrineListener(event: Events::preFlush)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class RapportStageCleanupListener
{
    /** @var list<string> */
    private array $aEffacer = [];

    public function __construct(
        private readonly StageRapportStorage $storage,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** Un flush précédent qui a échoué (transaction annulée) ne doit rien effacer. */
    public function preFlush(PreFlushEventArgs $args): void
    {
        $this->aEffacer = [];
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entite = $args->getObject();
        if ($entite instanceof StageEtudiant && $entite->getReportPath() !== null) {
            $this->aEffacer[] = $entite->getReportPath();
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $chemins = $this->aEffacer;
        $this->aEffacer = [];
        foreach ($chemins as $chemin) {
            try {
                $this->storage->supprimer($chemin);
            } catch (\Throwable $e) {
                $this->logger->warning('Rapport de stage non effacé.', ['chemin' => $chemin, 'erreur' => $e->getMessage()]);
            }
        }
    }
}
