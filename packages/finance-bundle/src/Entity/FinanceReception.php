<?php

namespace FinanceBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Entity\Users\Personnel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FinanceBundle\Enum\TypeReceptionEnum;
use FinanceBundle\Repository\FinanceReceptionRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FinanceReceptionRepository::class)]
#[ApiResource(
    operations: [
        // Liste restreinte par FinanceVisibiliteExtension (visibilité de la demande).
        new GetCollection(normalizationContext: ['groups' => ['reception:read']],
            paginationClientItemsPerPage: true,
            paginationMaximumItemsPerPage: 100),
        new Get(security: "is_granted('CAN_VIEW_FINANCE', object)", normalizationContext: ['groups' => ['reception:read']]),
        // Le service fait se constate par la transition « constater_service_fait » (avec le ou les bons de livraison) ;
        // le service financier vise ensuite chaque réception (POST /api/finance_receptions/{id}/viser) et complète les
        // informations SIFAC (numéros MIGO…). La demande n'est choisie qu'à la création.
        new Post(securityPostDenormalize: "is_granted('CAN_MANAGE_FINANCE', object)", denormalizationContext: ['groups' => ['reception:write', 'reception:create']]),
        new Patch(security: "is_granted('CAN_MANAGE_FINANCE', object)", securityPostDenormalize: "is_granted('CAN_MANAGE_FINANCE', object)", denormalizationContext: ['groups' => ['reception:write']]),
        new Delete(security: "is_granted('CAN_MANAGE_FINANCE', object)"),
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
    #[Groups(['reception:read', 'reception:create'])]
    private ?FinanceBonCommande $bonCommande = null;

    // Date effective de réception / livraison
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private ?\DateTimeInterface $dateEffectiveReception = null;

    #[ORM\Column(length: 20, enumType: TypeReceptionEnum::class)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private TypeReceptionEnum $typeReception = TypeReceptionEnum::TOTALE;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private ?string $montantTtcConstate = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private bool $prestationsConformes = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private ?string $detailsReceptionPartielle = null;

    // Numéros MIGO (SIFAC)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private ?array $numerosMigo = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private ?\DateTimeInterface $dateMigo103 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private ?\DateTimeInterface $dateMigo105 = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private bool $blRattacheSifac = false;

    // Demandeur de l'achat ou personne désignée pour réceptionner le bien/service
    #[ORM\ManyToOne]
    #[Groups(['reception:read', 'reception:write', 'finance:read', 'finance:list'])]
    private ?Personnel $receptionnaire = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['reception:read', 'reception:write', 'finance:read'])]
    private ?string $qualiteReceptionnaire = null; // Ex: Technicien d'atelier, Enseignant, etc.

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'finance:read', 'finance:list'])]
    private ?\DateTimeInterface $dateSignatureServiceFait = null;

    /** Bons de livraison (PDF) déposés à la constatation, ou ajoutés tant que la réception n'est pas visée. */
    #[ORM\OneToMany(targetEntity: FinanceReceptionDocument::class, mappedBy: 'reception', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    #[Groups(['reception:read', 'finance:read'])]
    private Collection $documents;

    /** Visa du service financier sur cette réception (second temps du service fait). */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['reception:read', 'finance:read', 'finance:list'])]
    private ?\DateTimeInterface $dateVisa = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['reception:read', 'finance:read'])]
    private ?Personnel $viseur = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    /** @return Collection<int, FinanceReceptionDocument> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(FinanceReceptionDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setReception($this);
        }

        return $this;
    }

    public function removeDocument(FinanceReceptionDocument $document): static
    {
        $this->documents->removeElement($document);

        return $this;
    }

    public function getDateVisa(): ?\DateTimeInterface
    {
        return $this->dateVisa;
    }

    public function getViseur(): ?Personnel
    {
        return $this->viseur;
    }

    public function viser(Personnel $viseur, \DateTimeInterface $date): static
    {
        $this->viseur = $viseur;
        $this->dateVisa = $date;

        return $this;
    }

    #[Groups(['reception:read', 'finance:read', 'finance:list'])]
    public function isVisee(): bool
    {
        return $this->dateVisa !== null;
    }

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
