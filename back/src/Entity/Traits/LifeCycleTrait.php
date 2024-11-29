<?php

namespace App\Entity\Traits;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait LifeCycleTrait
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?CarbonInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?CarbonInterface $updated = null;

    public function getCreated(): ?CarbonInterface
    {
        return $this->created ?? Carbon::now();
    }

    public function setCreated(?CarbonInterface $created): void
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

    public function setUpdatedValue(): void
    {
        $this->updated = Carbon::now();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedEntity(): void
    {
        $this->updated = Carbon::now();
    }

    #[ORM\PrePersist]
    public function setCreatedValue(): void
    {
        $this->created = Carbon::now();
    }
}
