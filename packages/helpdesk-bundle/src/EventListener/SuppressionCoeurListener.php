<?php

declare(strict_types=1);

namespace HelpdeskBundle\EventListener;

use App\Entity\Structure\StructureService;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use HelpdeskBundle\Entity\HelpdeskCategorie;
use HelpdeskBundle\Entity\HelpdeskTicket;

/** Les tickets d'un personnel et les catégories d'un service disparaissent avec eux. */
#[AsDoctrineListener(event: Events::preRemove)]
final class SuppressionCoeurListener
{
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $objet = $args->getObject();
        [$classe, $champ] = match (true) {
            $objet instanceof Personnel => [HelpdeskTicket::class, 'auteur'],
            $objet instanceof StructureService => [HelpdeskCategorie::class, 'service'],
            default => [null, null],
        };
        if ($classe === null || $objet->getId() === null) {
            return;
        }

        $em = $args->getObjectManager();
        foreach ($em->getRepository($classe)->findBy([$champ => $objet]) as $enfant) {
            $em->remove($enfant);
        }
    }
}
