<?php

namespace App\EventListener\Doctrine;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\LifeCycleTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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

        $now = \DateTimeImmutable::createFromInterface($this->clock->now());

        if ($entity instanceof TimestampableInterface) {
            if (null === $entity->getCreatedAt()) {
                $entity->setCreatedAt($now);
            }

            if (null === $entity->getUpdatedAt()) {
                $entity->setUpdatedAt($now);
            }

            return;
        }

        if ($this->usesLegacyLifeCycleTrait($entity)) {
            if (null === $entity->getCreated()) {
                $entity->setCreated(CarbonImmutable::instance($now));
            }

            if (null === $entity->getUpdated()) {
                $entity->setUpdated(Carbon::instance($now));
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $now = \DateTimeImmutable::createFromInterface($this->clock->now());

            if ($entity instanceof TimestampableInterface) {
                $entity->setUpdatedAt($now);
            } elseif ($this->usesLegacyLifeCycleTrait($entity)) {
                $entity->setUpdated(CarbonImmutable::instance($now));
            } else {
                continue;
            }

            $metadata = $entityManager->getClassMetadata($entity::class);
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
        }
    }

    private function usesLegacyLifeCycleTrait(object $entity): bool
    {
        return in_array(LifeCycleTrait::class, class_uses($entity), true);
    }
}
