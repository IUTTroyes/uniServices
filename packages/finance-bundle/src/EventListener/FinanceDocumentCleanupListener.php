<?php

namespace FinanceBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Service\FinanceDocumentStorage;

/** Supprime le document signé d'une demande d'achat supprimée (après la suppression en base). */
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: FinanceBonCommande::class)]
final class FinanceDocumentCleanupListener
{
    public function __construct(private readonly FinanceDocumentStorage $storage)
    {
    }

    public function postRemove(FinanceBonCommande $commande): void
    {
        $this->storage->supprimer($commande->getPdfFilePath());
    }
}
