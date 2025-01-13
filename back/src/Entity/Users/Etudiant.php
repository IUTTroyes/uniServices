<?php

namespace App\Entity\Users;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Etudiant\EtudiantAbsence;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Repository\EtudiantRepository;
use App\ValueObject\Adresse;
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
#[ORM\HasLifecycleCallbacks]
class Etudiant implements UserInterface, PasswordAuthenticatedUserInterface
{
    use LifeCycleTrait;
    use EduSignTrait;
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $username;

    #[ORM\Column(length: 255)]
    #[Groups(['etudiant:read'])]
    private string $mailUniv;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $prenom;

    #[ORM\Column(length: 75)]
    #[Groups(['etudiant:read', 'scolarite:read'])]
    private string $nom;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etudiant:read'])]
    private ?string $photoName = null;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\OneToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'etudiant', orphanRemoval: true)]
    #[Groups(['etudiant:read'])]
    private Collection $etudiantScolarites;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $adresseEtudiante = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $adresseParentale = null;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'etudiant')]
    private Collection $etudiantAbsences;

    public function __construct()
    {
        $this->etudiantScolarites = new ArrayCollection();
        $this->etudiantAbsences = new ArrayCollection();
    }

    public function getMails(): array
    {
        return [$this->mailUniv];
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
        return $this->mailUniv;
    }

    public function setMailUniv(string $mailUniv): static
    {
        $this->mailUniv = $mailUniv;

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
        return $this->photoName;
    }

    public function setPhotoName(?string $photoName): static
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * @return Collection<int, EtudiantScolarite>
     */
    public function getEtudiantScolarites(): Collection
    {
        return $this->etudiantScolarites;
    }

    public function addEtudiantScolarite(EtudiantScolarite $etudiantScolarite): static
    {
        if (!$this->etudiantScolarites->contains($etudiantScolarite)) {
            $this->etudiantScolarites->add($etudiantScolarite);
            $etudiantScolarite->setEtudiant($this);
        }

        return $this;
    }

    public function removeEtudiantScolarite(EtudiantScolarite $etudiantScolarite): static
    {
        if ($this->etudiantScolarites->removeElement($etudiantScolarite)) {
            // set the owning side to null (unless already changed)
            if ($etudiantScolarite->getEtudiant() === $this) {
                $etudiantScolarite->setEtudiant(null);
            }
        }

        return $this;
    }

    public function setAdresseEtudiante(Adresse $adresse): void
    {
        $this->adresseEtudiante = $adresse->toArray();
    }

    public function getAdresseEtudiante(): ?Adresse
    {
        if ($this->adresseEtudiante === null) {
            return null;
        }

        return Adresse::fromArray($this->adresseEtudiante);
    }

    public function setAdresseParentale(Adresse $adresse): void
    {
        $this->adresseParentale= $adresse->toArray();
    }

    public function getAdresseParentale(): ?Adresse
    {
        if ($this->adresseParentale === null) {
            return null;
        }

        return Adresse::fromArray($this->adresseParentale);
    }

    /**
     * @return Collection<int, EtudiantAbsence>
     */
    public function getEtudiantAbsences(): Collection
    {
        return $this->etudiantAbsences;
    }

    public function addEtudiantAbsence(EtudiantAbsence $etudiantAbsence): static
    {
        if (!$this->etudiantAbsences->contains($etudiantAbsence)) {
            $this->etudiantAbsences->add($etudiantAbsence);
            $etudiantAbsence->setEtudiant($this);
        }

        return $this;
    }

    public function removeEtudiantAbsence(EtudiantAbsence $etudiantAbsence): static
    {
        if ($this->etudiantAbsences->removeElement($etudiantAbsence)) {
            // set the owning side to null (unless already changed)
            if ($etudiantAbsence->getEtudiant() === $this) {
                $etudiantAbsence->setEtudiant(null);
            }
        }

        return $this;
    }
}
