<?php

namespace FinanceBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FinanceBundle\Enum\TypeReceptionEnum;
use FinanceBundle\Repository\FinanceReceptionRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FinanceReceptionRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['reception:read']]),
        new Get(normalizationContext: ['groups' => ['reception:read']]),
        new Post(denormalizationContext: ['groups' => ['reception:write', 'finance:write']]),
        new Patch(denormalizationContext: ['groups' => ['reception:write', 'finance:write']]),
        new Delete()
    ]
)]
class FinanceReception
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reception:read', 'finance:read', 'finance:list'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'receptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['reception:read', 'reception:write'])]
    private ?FinanceBonCommande $bonCommande = null;

    // Date effective de réception / livraison
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateEffectiveReception = null;

    #[ORM\Column(length: 20, enumType: TypeReceptionEnum::class)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private TypeReceptionEnum $typeReception = TypeReceptionEnum::TOTALE;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?string $montantTtcConstate = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private bool $prestationsConformes = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?string $detailsReceptionPartielle = null;

    // Numéros MIGO (SIFAC)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?array $numerosMigo = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateMigo103 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateMigo105 = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private bool $blRattacheSifac = false;

    // Demandeur de l'achat ou personne désignée pour réceptionner le bien/service
    #[ORM\ManyToOne]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list', 'finance:write'])]
    private ?Personnel $receptionnaire = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:write'])]
    private ?string $qualiteReceptionnaire = null; // Ex: Technicien d'atelier, Enseignant, etc.

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'finance:read', 'finance:list'])]
    private ?\DateTimeInterface $dateSignatureServiceFait = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBonCommande(): ?FinanceBonCommande
    {
        return $this->bonCommande;
    }

    public function setBonCommande(?FinanceBonCommande $bonCommande): static
    {
        $this->bonCommande = $bonCommande;
        return $this;
    }

    public function getDateEffectiveReception(): ?\DateTimeInterface
    {
        return $this->dateEffectiveReception;
    }

    public function setDateEffectiveReception(?\DateTimeInterface $dateEffectiveReception): static
    {
        $this->dateEffectiveReception = $dateEffectiveReception;
        return $this;
    }

    public function getTypeReception(): TypeReceptionEnum
    {
        return $this->typeReception;
    }

    public function setTypeReception(TypeReceptionEnum $typeReception): static
    {
        $this->typeReception = $typeReception;
        return $this;
    }

    public function getMontantTtcConstate(): ?string
    {
        return $this->montantTtcConstate;
    }

    public function setMontantTtcConstate(?string $montantTtcConstate): static
    {
        $this->montantTtcConstate = $montantTtcConstate;
        return $this;
    }

    public function isPrestationsConformes(): bool
    {
        return $this->prestationsConformes;
    }

    public function setPrestationsConformes(bool $prestationsConformes): static
    {
        $this->prestationsConformes = $prestationsConformes;
        return $this;
    }

    public function getDetailsReceptionPartielle(): ?string
    {
        return $this->detailsReceptionPartielle;
    }

    public function setDetailsReceptionPartielle(?string $detailsReceptionPartielle): static
    {
        $this->detailsReceptionPartielle = $detailsReceptionPartielle;
        return $this;
    }

    public function getNumerosMigo(): ?array
    {
        return $this->numerosMigo;
    }

    public function setNumerosMigo(?array $numerosMigo): static
    {
        $this->numerosMigo = $numerosMigo;
        return $this;
    }

    public function getDateMigo103(): ?\DateTimeInterface
    {
        return $this->dateMigo103;
    }

    public function setDateMigo103(?\DateTimeInterface $dateMigo103): static
    {
        $this->dateMigo103 = $dateMigo103;
        return $this;
    }

    public function getDateMigo105(): ?\DateTimeInterface
    {
        return $this->dateMigo105;
    }

    public function setDateMigo105(?\DateTimeInterface $dateMigo105): static
    {
        $this->dateMigo105 = $dateMigo105;
        return $this;
    }

    public function isBlRattacheSifac(): bool
    {
        return $this->blRattacheSifac;
    }

    public function setBlRattacheSifac(bool $blRattacheSifac): static
    {
        $this->blRattacheSifac = $blRattacheSifac;
        return $this;
    }

    public function getReceptionnaire(): ?Personnel
    {
        return $this->receptionnaire;
    }

    public function setReceptionnaire(?Personnel $receptionnaire): static
    {
        $this->receptionnaire = $receptionnaire;
        return $this;
    }

    public function getQualiteReceptionnaire(): ?string
    {
        return $this->qualiteReceptionnaire;
    }

    public function setQualiteReceptionnaire(?string $qualiteReceptionnaire): static
    {
        $this->qualiteReceptionnaire = $qualiteReceptionnaire;
        return $this;
    }

    public function getDateSignatureServiceFait(): ?\DateTimeInterface
    {
        return $this->dateSignatureServiceFait;
    }

    public function setDateSignatureServiceFait(?\DateTimeInterface $dateSignatureServiceFait): static
    {
        $this->dateSignatureServiceFait = $dateSignatureServiceFait;
        return $this;
    }
}
