<?php

namespace App\Entity\Traits;

use ApiPlatform\Metadata\ApiProperty;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @deprecated Use TimestampableTrait on new entities. Kept for backward compatibility.
 */
trait LifeCycleTrait
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['questionnaire:read','ticket:read', 'absence:administration', 'actu:read'])]
    #[ApiProperty(writable: false)]
    private ?CarbonImmutable $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['questionnaire:read', 'absence:administration', 'actu:read'])]
    #[ApiProperty(writable: false)]
    private ?CarbonInterface $updated = null;

    public function getCreated(): ?CarbonImmutable
    {
        return $this->created;
    }

    public function setCreated(?CarbonImmutable $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): ?CarbonInterface
    {
        return $this->updated;
    }

    public function setUpdated(?CarbonInterface $updated): void
    {
        $this->updated = $updated;
    }

}
