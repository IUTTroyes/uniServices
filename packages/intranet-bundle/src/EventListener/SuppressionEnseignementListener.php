<?php

declare(strict_types=1);

namespace IntranetBundle\EventListener;

use App\Entity\Scolarite\ScolEnseignement;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use IntranetBundle\Entity\Previsionnel\Previsionnel;

/** Le prévisionnel d'un enseignement supprimé disparaît avec lui. */
#[AsDoctrineListener(event: Events::preRemove)]
final class SuppressionEnseignementListener
{
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $objet = $args->getObject();
        if (!$objet instanceof ScolEnseignement || $objet->getId() === null) {
            return;
        }

        $em = $args->getObjectManager();
        foreach ($em->getRepository(Previsionnel::class)->findBy(['enseignement' => $objet]) as $previsionnel) {
            $em->remove($previsionnel);
        }
    }
}
