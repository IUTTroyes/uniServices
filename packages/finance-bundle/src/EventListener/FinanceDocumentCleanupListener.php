<?php

namespace FinanceBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceReceptionDocument;
use FinanceBundle\Service\FinanceDocumentStorage;

/**
 * Supprime les fichiers après la suppression en base : document signé d'une demande d'achat, bons de livraison d'une
 * réception (retirés un à un, ou avec la demande : suppression en cascade par l'ORM).
 */
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: FinanceBonCommande::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemoveBonLivraison', entity: FinanceReceptionDocument::class)]
final class FinanceDocumentCleanupListener
{
    public function __construct(private readonly FinanceDocumentStorage $storage)
    {
    }

    public function postRemove(FinanceBonCommande $commande): void
    {
        $this->storage->supprimer($commande->getPdfFilePath());
    }

    public function postRemoveBonLivraison(FinanceReceptionDocument $document): void
    {
        $this->storage->supprimer($document->getChemin());
    }
}
