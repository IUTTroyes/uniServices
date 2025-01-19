<?php

namespace App\Entity\Edt;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\Edt\EdtProgressionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EdtProgressionRepository::class)]
// ajouter une option de type Post qui va dupliquer une ressource sur l'URL /edt_progressions/{id}/duplicate

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Put(),
        new Delete(),
    ],
)]
class EdtProgression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['previsionnel:read'])]
    private ?array $progression = null;

    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?string $grTd = '';

    #[ORM\Column]
    #[Groups(['previsionnel:read'])]
    private ?string $grTp = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrTd(): ?string
    {
        return $this->grTd ?? '';
    }

    public function setGrTd(string $grTd): static
    {
        $this->grTd = $grTd;

        return $this;
    }

    public function getGrTp(): ?string
    {
        return $this->grTp ?? '';
    }

    public function setGrTp(string $grTp): static
    {
        $this->grTp = $grTp;

        return $this;
    }

    public function getProgression(): ?array
    {
        return $this->progression ?? [];
    }

    public function setProgression(array $progression): static
    {
        $this->progression = $progression;

        return $this;
    }
}
