<?php

namespace StageBundle\Service\Notification;

use App\Notification\NotificationTypeProviderInterface;
use StageBundle\Enum\TypeNotificationEnum;

/** Déclare les types de notification du package Stages auprès du registre global (enregistré automatiquement). */
final class StageNotificationTypes implements NotificationTypeProviderInterface
{
    public function getTypes(): iterable
    {
        foreach (TypeNotificationEnum::cases() as $type) {
            yield $type->definition();
        }
    }
}
