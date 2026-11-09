<?php

namespace QuestionnaireBundle\Services\Notification;

use App\Notification\NotificationTypeProviderInterface;
use QuestionnaireBundle\Enum\TypeNotificationEnum;

/** Déclare les types de notification du package (enregistré automatiquement par autoconfiguration). */
final class QuestionnaireNotificationTypes implements NotificationTypeProviderInterface
{
    public function getTypes(): iterable
    {
        foreach (TypeNotificationEnum::cases() as $type) {
            yield $type->definition();
        }
    }
}
