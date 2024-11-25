<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\EtudiantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: EtudiantRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class StructureEtudiant implements UserInterface
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

    public function setPassword(?string $password): void
    {
        $this->password = $password;
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
}
