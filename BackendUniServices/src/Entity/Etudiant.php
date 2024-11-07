<?php

namespace App\Entity;

use App\Repository\EtudiantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtudiantRepository::class)]
class Etudiant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $num_etudiant = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $num_ine = null;

    #[ORM\Column(length: 75, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 75)]
    private ?string $prenom = null;

    #[ORM\Column(length: 75)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $roles = null;

    #[ORM\Column(length: 255)]
    private ?string $mail_univ = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumEtudiant(): ?string
    {
        return $this->num_etudiant;
    }

    public function setNumEtudiant(string $num_etudiant): static
    {
        $this->num_etudiant = $num_etudiant;

        return $this;
    }

    public function getNumIne(): ?string
    {
        return $this->num_ine;
    }

    public function setNumIne(?string $num_ine): static
    {
        $this->num_ine = $num_ine;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

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

    public function getRoles(): ?string
    {
        return $this->roles;
    }

    public function setRoles(string $roles): static
    {
        $this->roles = $roles;

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
}
