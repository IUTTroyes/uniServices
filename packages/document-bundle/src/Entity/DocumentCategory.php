<?php

namespace DocumentBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Structure\StructureDepartement;
use DocumentBundle\Repository\DocumentCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DocumentCategoryRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['document_category:read']]),
        new GetCollection(normalizationContext: ['groups' => ['document_category:read']]),
        new Post(
            denormalizationContext: ['groups' => ['document_category:write']],
            security: "is_granted('ROLE_PERSONNEL')"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['document_category:write']],
            security: "is_granted('ROLE_PERSONNEL')"
        ),
        new Delete(
            security: "is_granted('ROLE_PERSONNEL')"
        ),
    ],
    normalizationContext: ['groups' => ['document_category:read']],
    denormalizationContext: ['groups' => ['document_category:write']]
)]
class DocumentCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['document_category:read', 'document:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['document_category:read', 'document_category:write', 'document:read'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['document_category:read', 'document_category:write', 'document:read'])]
    private ?string $icon = '📁';

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['document_category:read', 'document_category:write', 'document:read'])]
    private ?string $color = 'bg-blue-500';

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['document_category:read', 'document_category:write'])]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[Groups(['document_category:read'])]
    private Collection $children;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'category')]
    private Collection $documents;

    #[ORM\ManyToOne(targetEntity: StructureDepartement::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: true)]
    #[Groups(['document_category:read', 'document_category:write'])]
    private ?StructureDepartement $departement = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document_category:read', 'document_category:write'])]
    private ?string $packageKey = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['document_category:read', 'document_category:write'])]
    private bool $isSystem = false;

    #[ORM\Column]
    #[Groups(['document_category:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    #[Groups(['document_category:read'])]
    public function getDocumentCount(): int
    {
        $count = $this->documents->count();
        foreach ($this->children as $child) {
            $count += $child->getDocumentCount();
        }
        return $count;
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

    public function getPackageKey(): ?string
    {
        return $this->packageKey;
    }

    public function setPackageKey(?string $packageKey): static
    {
        $this->packageKey = $packageKey;
        return $this;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): static
    {
        $this->isSystem = $isSystem;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
