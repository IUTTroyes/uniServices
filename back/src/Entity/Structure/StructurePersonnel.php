<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\StructurePersonnelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: StructurePersonnelRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class StructurePersonnel implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 75)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $mail_univ = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(length: 75)]
    private ?string $prenom = null;

    #[ORM\Column(length: 75)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $photo_name = null;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'responsable_diplome')]
    private Collection $responsableDiplome;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'assistant_diplome')]
    private Collection $assistant_diplome;

    #[ORM\ManyToOne(inversedBy: 'personnels')]
    private ?StructureAnneeUniversitaire $structureAnneeUniversitaire = null;

    public function __construct()
    {
        $this->responsableDiplome = new ArrayCollection();
        $this->assistant_diplome = new ArrayCollection();
    }

    public function getMails(): array
    {
        return [$this->mail_univ];
    }

    public function getTypeUser(): ?string
    {
        return 'personnel';
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
     * @return Collection<int, StructureDiplome>
     */
    public function getResponsableDiplome(): Collection
    {
        return $this->responsableDiplome;
    }

    public function addResponsableDiplome(StructureDiplome $responsableDiplome): static
    {
        if (!$this->responsableDiplome->contains($responsableDiplome)) {
            $this->responsableDiplome->add($responsableDiplome);
            $responsableDiplome->setResponsableDiplome($this);
        }

        return $this;
    }

    public function removeResponsableDiplome(StructureDiplome $responsableDiplome): static
    {
        if ($this->responsableDiplome->removeElement($responsableDiplome)) {
            // set the owning side to null (unless already changed)
            if ($responsableDiplome->getResponsableDiplome() === $this) {
                $responsableDiplome->setResponsableDiplome(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructureDiplome>
     */
    public function getAssistantDiplome(): Collection
    {
        return $this->assistant_diplome;
    }

    public function addAssistantDiplome(StructureDiplome $assistantDiplome): static
    {
        if (!$this->assistant_diplome->contains($assistantDiplome)) {
            $this->assistant_diplome->add($assistantDiplome);
            $assistantDiplome->setAssistantDiplome($this);
        }

        return $this;
    }

    public function removeAssistantDiplome(StructureDiplome $assistantDiplome): static
    {
        if ($this->assistant_diplome->removeElement($assistantDiplome)) {
            // set the owning side to null (unless already changed)
            if ($assistantDiplome->getAssistantDiplome() === $this) {
                $assistantDiplome->setAssistantDiplome(null);
            }
        }

        return $this;
    }

    public function getStructureAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->structureAnneeUniversitaire;
    }

    public function setStructureAnneeUniversitaire(?StructureAnneeUniversitaire $structureAnneeUniversitaire): static
    {
        $this->structureAnneeUniversitaire = $structureAnneeUniversitaire;

        return $this;
    }
}
