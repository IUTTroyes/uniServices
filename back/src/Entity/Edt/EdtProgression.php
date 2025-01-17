<?php

namespace App\Entity\Edt;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Personnel;
use App\Repository\Edt\EdtProgressionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EdtProgressionRepository::class)]
// ajouter une option de type Post qui va dupliquer une ressource sur l'URL /edt_progressions/{id}/duplicate

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Post(
            name: 'duplicate',
            uriTemplate: '/edt_progressions/{id}/duplicate',
            controller: 'App\Controller\Edt\DuplicateEdtProgressionController',
        ),
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
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?array $progression = null;

    #[ORM\Column]
    private ?string $grTd = '';

    #[ORM\Column]
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
}
