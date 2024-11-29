<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

trait UuidTrait
{
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $uuid;

    public function getUuidString(): string
    {
        return (string) $this->getUuid();
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): void
    {
        $this->uuid = $uuid;
    }
}
