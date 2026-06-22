<?php

namespace StageBundle\Entity\Stages;

use App\Entity\Traits\LifeCycleTrait;
use StageBundle\Repository\Stages\ContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contact
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_periode_gestion', 'stage_entreprise', 'stage_etudiant:read'])]
    private ?int $id = null;

    #[Groups(['alternance_administration', 'stage_entreprise_administration', 'stage_periode_gestion', 'stage_entreprise', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $nom = null;

    #[Groups(['alternance_administration', 'stage_entreprise_administration', 'stage_periode_gestion', 'stage_entreprise', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $prenom = null;

    #[Groups(['alternance_administration', 'stage_entreprise_administration', 'stage_periode_gestion', 'stage_entreprise', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $fonction = null;

    #[Groups(['alternance_administration', 'stage_entreprise_administration', 'stage_periode_gestion', 'stage_entreprise', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::STRING, length: 25, nullable: true)]
    private ?string $telephone = null;

    #[Groups(['alternance_administration', 'stage_entreprise_administration', 'stage_periode_gestion', 'stage_entreprise', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $email = null;

    #[Groups(['alternance_administration', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::STRING, length: 25, nullable: true)]
    private ?string $portable = null;

    #[Groups(['alternance_administration', 'stage_etudiant:read', 'stage_etudiant:write'])]
    #[ORM\Column(type: Types::STRING, length: 3, nullable: true)]
    private ?string $civilite = null;

    #[ORM\Column(type: Types::STRING, length: 25, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $fax = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(?string $fonction): self
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPortable(): ?string
    {
        return $this->portable;
    }

    public function setPortable(?string $portable): self
    {
        $this->portable = $portable;

        return $this;
    }

    public function getCivilite(): ?string
    {
        return $this->civilite;
    }

    public function setCivilite(?string $civilite): self
    {
        $this->civilite = $civilite;

        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): self
    {
        $this->fax = $fax;

        return $this;
    }

    public function getDisplay(): string
    {
        return ucfirst((string)$this->getPrenom()) . ' ' . mb_strtoupper((string)$this->getNom());
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCiviliteLong(): ?string
    {
        return 'M' === $this->civilite ? 'Monsieur' : 'Madame';
    }
}
