<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\EtablissementRepository;
use App\State\Processor\EtablissementProcessor;
use App\State\Provider\EtablissementProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EtablissementRepository::class)]
#[UniqueEntity(fields: ['isMain'], message: 'Il ne peut y avoir qu\'un seul établissement principal.')]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['etablissement:read']],
            provider: EtablissementProvider::class,
            processor: EtablissementProcessor::class
        ),
        new Patch(
            inputFormats: [
                'json' => ['application/merge-patch+json']
            ],
            denormalizationContext: ['groups' => ['etablissement:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_ETABLISSEMENT', object)",
            processor: EtablissementProcessor::class
        ),
        new Post(
            uriTemplate: '/etablissements/{id}/logo',
            inputFormats: [
                'multipart' => ['multipart/form-data']
            ],
            security: "is_granted('CAN_EDIT_ETABLISSEMENT', object)",
            deserialize: false,
            processor: EtablissementProcessor::class
        ),
        new Post(
            denormalizationContext: ['groups' => ['etablissement:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_ETABLISSEMENT', object)",
            processor: EtablissementProcessor::class
        )

    ],
)]
class Etablissement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etablissement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $logo_name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?array $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $site_web = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isMain = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

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

    public function getIsMain(): ?bool
    {
        return $this->isMain;
    }

    public function setIsMain(?bool $isMain): void
    {
        $this->isMain = $isMain;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }
}
