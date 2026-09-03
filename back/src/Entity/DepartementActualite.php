<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Traits\LifeCycleTrait;
use App\Enum\TypePublicEnum;
use App\Repository\DepartementActualiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DepartementActualiteRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['actu:read']]),
        new GetCollection(normalizationContext: ['groups' => ['actu:read']]),
        new Post(),
        new Delete()
    ]
)]
class DepartementActualite
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['actu:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['actu:read'])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['actu:read'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'departementActualites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StructureDepartement $departement = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['actu:read'])]
    private array $public = [];

    #[ORM\Column]
    #[Groups(['actu:read'])]
    private ?bool $actif = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['actu:read'])]
    private ?\DateTime $date_debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['actu:read'])]
    private ?\DateTime $date_fin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['actu:read'])]
    private ?string $link = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function getPublic(): array
    {
        return $this->public;
    }

    /**
     * @param TypePublicEnum[] $public
     */
    public function setPublic(array $public): static
    {
        $this->public = array_map(
            fn($item) => $item instanceof TypePublicEnum ? $item->value : $item,
            $public
        );

        return $this;
    }

    public function getPublicEnums(): array
    {
        return array_map(
            fn(string $value) => TypePublicEnum::from($value),
            $this->public
        );
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(?\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }
}
