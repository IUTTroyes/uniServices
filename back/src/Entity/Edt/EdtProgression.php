<?php

namespace App\Entity\Edt;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\Edt\EdtProgressionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EdtProgressionRepository::class)]
#[ApiResource]
class EdtProgression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
