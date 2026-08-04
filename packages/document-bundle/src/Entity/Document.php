<?php

namespace DocumentBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Structure\StructureDepartement;
use DocumentBundle\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['document:read']]),
        new GetCollection(normalizationContext: ['groups' => ['document:read']]),
        new Post(
            denormalizationContext: ['groups' => ['document:write']],
            security: "is_granted('ROLE_PERSONNEL')"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['document:write']],
            security: "is_granted('ROLE_PERSONNEL')"
        ),
        new Delete(
            security: "is_granted('ROLE_PERSONNEL')"
        ),
    ],
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']]
)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['document:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $filename = null;

    #[ORM\Column(length: 100)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $mimeType = 'application/pdf';

    #[ORM\Column]
    #[Groups(['document:read', 'document:write'])]
    private int $fileSize = 0;

    #[ORM\Column(length: 50)]
    #[Groups(['document:read', 'document:write'])]
    private string $type = 'pdf'; // pdf, excel, word, powerpoint, image, video, audio, text, archive

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $author = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $version = 'v1.0';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?array $tags = [];

    #[ORM\Column]
    #[Groups(['document:read', 'document:write'])]
    private bool $isFavorite = false;

    #[ORM\Column(length: 50)]
    #[Groups(['document:read', 'document:write'])]
    private string $visibility = 'PUBLIC'; // PUBLIC, ETUDIANT, PERSONNEL, DEPARTEMENT

    #[ORM\ManyToOne(targetEntity: DocumentCategory::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['document:read', 'document:write'])]
    private ?DocumentCategory $category = null;

    #[ORM\ManyToOne(targetEntity: StructureDepartement::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['document:read', 'document:write'])]
    private ?StructureDepartement $departement = null;

    #[ORM\Column]
    #[Groups(['document:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['document:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
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

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags ?? [];
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): static
    {
        $this->isFavorite = $isFavorite;
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function getCategory(): ?DocumentCategory
    {
        return $this->category;
    }

    public function setCategory(?DocumentCategory $category): static
    {
        $this->category = $category;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
