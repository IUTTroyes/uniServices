<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EtablissementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementRepository::class)]
#[ApiResource]
class Etablissement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo_name = null;

    #[ORM\Column(nullable: true)]
    private ?array $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $site_web = null;

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

    public function getLogoName(): ?string
    {
        return $this->logo_name;
    }

    public function setLogoName(?string $logo_name): static
    {
        $this->logo_name = $logo_name;

        return $this;
    }

    public function getAdresse(): ?array
    {
        return $this->adresse;
    }

    public function setAdresse(?array $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->site_web;
    }

    public function setSiteWeb(?string $site_web): static
    {
        $this->site_web = $site_web;

        return $this;
    }
}
