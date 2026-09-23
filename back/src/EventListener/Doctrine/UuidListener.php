<?php

namespace App\EventListener\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
final readonly class UuidListener
{
    public function prePersist(PrePersistEventArgs $event): void
    {
        $entity = $event->getObject();

        if (method_exists($entity, 'initializeUuid')) {
            $entity->initializeUuid();
        } elseif (method_exists($entity, 'getUuid') && method_exists($entity, 'setUuid') && null === $entity->getUuid()) {
            $entity->setUuid();
        }
    }
}
