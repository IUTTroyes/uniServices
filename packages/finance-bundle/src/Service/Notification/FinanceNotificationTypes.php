<?php

namespace FinanceBundle\Service\Notification;

use App\Notification\NotificationTypeProviderInterface;
use FinanceBundle\Enum\TypeNotificationEnum;

/** Déclare les types de notification du package Finances auprès du registre global (enregistré automatiquement). */
final class FinanceNotificationTypes implements NotificationTypeProviderInterface
{
    public function getTypes(): iterable
    {
        foreach (TypeNotificationEnum::cases() as $type) {
            yield $type->definition();
        }
    }
}
