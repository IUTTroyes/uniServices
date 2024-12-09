<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait OldIdTrait
{
    #[ORM\Column(nullable: true)]
    private ?int $oldId = null; //permet de garder l'id de V3 le temps de la bascule

    public function getOldId(): ?int
    {
        return $this->oldId;
    }

    public function setOldId(?int $oldId): static
    {
        $this->oldId = $oldId;

        return $this;
    }
}
