<?php

namespace App\EventListener\Doctrine;

use App\Entity\Contracts\TimestampableInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Clock\ClockInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::onFlush)]
final readonly class TimestampableListener
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        $entity = $event->getObject();

        if (!$entity instanceof TimestampableInterface) {
            return;
        }

        $now = \DateTimeImmutable::createFromInterface($this->clock->now());

        if (null === $entity->getCreatedAt()) {
            $entity->setCreatedAt($now);
        }

        if (null === $entity->getUpdatedAt()) {
            $entity->setUpdatedAt($now);
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof TimestampableInterface) {
                continue;
            }

            $entity->setUpdatedAt(
                \DateTimeImmutable::createFromInterface($this->clock->now())
            );

            $metadata = $entityManager->getClassMetadata($entity::class);
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
        }
    }
}
