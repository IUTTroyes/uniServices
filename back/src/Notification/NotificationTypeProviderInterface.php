<?php

namespace App\Notification;

/**
 * À implémenter dans un package pour déclarer ses types de notification.
 * Étiquetée automatiquement « app.notification_type_provider » (voir docs/NOTIFICATIONS_SYSTEM.md).
 */
interface NotificationTypeProviderInterface
{
    /** @return iterable<NotificationType> */
    public function getTypes(): iterable;
}
