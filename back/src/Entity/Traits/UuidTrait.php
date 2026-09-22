<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

trait UuidTrait
{
    #[ORM\Column(type: UuidType::NAME)]
    #[Groups(['evaluation:detail', 'questionnaire_section:read'])]
    private ?Uuid $uuid = null;

    public function getUuidString(): string
    {
        return (string) $this->getUuid();
    }

    public function getUuid(): ?Uuid
    {
        if (null === $this->uuid) {
            $this->uuid = Uuid::v4();
        }

        return $this->uuid;
    }

    public function setUuid(Uuid|string|null $uuid = null): static
    {
        if (null === $uuid) {
            $this->uuid = Uuid::v4();
        } elseif (is_string($uuid)) {
            $trimmed = trim($uuid);
            if ('' === $trimmed) {
                $this->uuid = Uuid::v4();
            } elseif (16 === strlen($trimmed)) {
                try {
                    $this->uuid = Uuid::fromBinary($trimmed);
                } catch (\Throwable) {
                    $this->uuid = Uuid::v4();
                }
            } elseif (Uuid::isValid($trimmed)) {
                $this->uuid = Uuid::fromString($trimmed);
            } else {
                $hex = str_replace('-', '', $trimmed);
                if (32 === strlen($hex) && ctype_xdigit($hex)) {
                    $this->uuid = Uuid::fromString(sprintf(
                        '%s-%s-%s-%s-%s',
                        substr($hex, 0, 8),
                        substr($hex, 8, 4),
                        substr($hex, 12, 4),
                        substr($hex, 16, 4),
                        substr($hex, 20, 12),
                    ));
                } else {
                    $this->uuid = Uuid::v4();
                }
            }
        } else {
            $this->uuid = $uuid;
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeUuid(): void
    {
        if (null === $this->uuid) {
            $this->uuid = Uuid::v4();
        }
    }
}
