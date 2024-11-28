<?php

namespace App\Entity\Users;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureScolarite;
use App\Repository\EtudiantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EtudiantRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['etudiant:read']]),
        new GetCollection(normalizationContext: ['groups' => ['etudiant:read']]),
    ]
)]
class Etudiant implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Groups(['etudiant:read'])]
    private ?string $mail_univ = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $photo_name = null;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, mappedBy: 'etudiants')]
    private Collection $structureGroupes;

    /**
     * @var Collection<int, StructureScolarite>
     */
    #[ORM\OneToMany(targetEntity: StructureScolarite::class, mappedBy: 'etudiant', orphanRemoval: true)]
    #[Groups(['etudiant:read'])]
    private Collection $structureScolarites;

    public function __construct()
    {
        $this->structureGroupes = new ArrayCollection();
        $this->structureScolarites = new ArrayCollection();
    }

    public function getMails(): array
    {
        return [$this->mail_univ];
    }

    public function getTypeUser(): ?string
    {
        return 'etudiant';
    }

    public function __toString(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getMailUniv(): ?string
    {
        return $this->mail_univ;
    }

    public function setMailUniv(string $mail_univ): static
    {
        $this->mail_univ = $mail_univ;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPhotoName(): ?string
    {
        return $this->photo_name;
    }

    public function setPhotoName(?string $photo_name): static
    {
        $this->photo_name = $photo_name;

        return $this;
    }

    /**
     * @return Collection<int, StructureGroupe>
     */
    public function getStructureGroupes(): Collection
    {
        return $this->structureGroupes;
    }

    public function addStructureGroupe(StructureGroupe $structureGroupe): static
    {
        if (!$this->structureGroupes->contains($structureGroupe)) {
            $this->structureGroupes->add($structureGroupe);
            $structureGroupe->addEtudiant($this);
        }

        return $this;
    }

    public function removeStructureGroupe(StructureGroupe $structureGroupe): static
    {
        if ($this->structureGroupes->removeElement($structureGroupe)) {
            $structureGroupe->removeEtudiant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, StructureScolarite>
     */
    public function getStructureScolarites(): Collection
    {
        return $this->structureScolarites;
    }

    public function addStructureScolarite(StructureScolarite $structureScolarite): static
    {
        if (!$this->structureScolarites->contains($structureScolarite)) {
            $this->structureScolarites->add($structureScolarite);
            $structureScolarite->setEtudiant($this);
        }

        return $this;
    }

    public function removeStructureScolarite(StructureScolarite $structureScolarite): static
    {
        if ($this->structureScolarites->removeElement($structureScolarite)) {
            // set the owning side to null (unless already changed)
            if ($structureScolarite->getEtudiant() === $this) {
                $structureScolarite->setEtudiant(null);
            }
        }

        return $this;
    }
}