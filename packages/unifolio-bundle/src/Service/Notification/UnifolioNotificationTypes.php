<?php

namespace UnifolioBundle\Service\Notification;

use App\Notification\NotificationTypeProviderInterface;
use UnifolioBundle\Enum\TypeNotificationEnum;

/** Déclare les types de notification d'UniFolio auprès du registre global (enregistré automatiquement). */
final class UnifolioNotificationTypes implements NotificationTypeProviderInterface
{
    public function getTypes(): iterable
    {
        foreach (TypeNotificationEnum::cases() as $type) {
            yield $type->definition();
        }
    }
}
