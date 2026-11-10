<?php

namespace App\Notification;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/** Ensemble des types déclarés par tous les packages. */
class NotificationTypeRegistry
{
    /** @var array<string, NotificationType>|null */
    private ?array $types = null;

    /** @param iterable<NotificationTypeProviderInterface> $providers */
    public function __construct(
        #[AutowireIterator('app.notification_type_provider')]
        private readonly iterable $providers,
    ) {
    }

    public function get(string $cle): ?NotificationType
    {
        return $this->all()[$cle] ?? null;
    }

    /** @return array<string, NotificationType> */
    public function all(): array
    {
        if ($this->types === null) {
            $this->types = [];
            foreach ($this->providers as $provider) {
                foreach ($provider->getTypes() as $type) {
                    if (isset($this->types[$type->cle])) {
                        throw new \LogicException(sprintf('Type de notification « %s » déclaré deux fois.', $type->cle));
                    }
                    $this->types[$type->cle] = $type;
                }
            }
        }

        return $this->types;
    }

    /** @return list<NotificationType> types destinés à ce public, groupés par package puis dans l'ordre de déclaration */
    public function pourAudience(string $audience): array
    {
        return array_values(array_filter($this->all(), static fn (NotificationType $t) => $t->audience === $audience));
    }
}
