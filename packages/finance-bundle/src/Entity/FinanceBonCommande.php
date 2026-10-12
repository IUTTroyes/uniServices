<?php

namespace FinanceBundle\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureService;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Users\Personnel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FinanceBundle\Enum\AvisDirecteurEnum;
use FinanceBundle\Enum\StatutCommandeEnum;
use FinanceBundle\Enum\TypePrestationEnum;
use FinanceBundle\Repository\FinanceBonCommandeRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FinanceBonCommandeRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['finance:read', 'finance:list']],
            paginationEnabled: false
        ),
        new Get(
            normalizationContext: ['groups' => ['finance:read', 'finance:detail']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['finance:write']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['finance:write', 'finance:update_step']]
        ),
        new Delete()
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'centreFinancier' => 'exact',
    'numeroBonCommande' => 'ipartial',
    'objetDemande' => 'ipartial',
    'statut' => 'exact',
    'departement.id' => 'exact',
    'service.id' => 'exact',
    'gestionnaire.id' => 'exact',
    'demandeur.id' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: ['dateBonCommande', 'dateDemande', 'dateArriveeFacture', 'datePaiement'])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'dateBonCommande', 'dateDemande', 'montantHt'], arguments: ['orderParameterName' => 'order'])]
class FinanceBonCommande implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['finance:read', 'finance:list'])]
    private ?int $id = null;

    // ==========================================
    // 1 - DEMANDE
    // ==========================================

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?Personnel $demandeur = null;

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?Personnel $responsable = null;

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?StructureDepartement $departement = null;

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?StructureService $service = null;

    #[ORM\Column(length: 20, enumType: TypePrestationEnum::class)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private TypePrestationEnum $prestation = TypePrestationEnum::FOURNITURES;

    #[ORM\Column(length: 255)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?string $objetDemande = null; // Libellé / Objet

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?string $montantHt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?string $montantTtc = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['finance:read', 'finance:detail', 'finance:write'])]
    private ?string $noteExplicative = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateDemande = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:detail'])]
    private ?\DateTimeInterface $dateSignatureDemandeur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:detail'])]
    private ?\DateTimeInterface $dateSignatureResponsable = null;

    // Avis Directeur
    #[ORM\Column(length: 30, enumType: AvisDirecteurEnum::class, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?AvisDirecteurEnum $avisDirecteur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['finance:read', 'finance:detail', 'finance:write'])]
    private ?string $avisDirecteurCommentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateAvisDirecteur = null;

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:detail'])]
    private ?Personnel $signataireDirecteur = null;

    // ==========================================
    // 2 - VERIFICATION (Service Financier)
    // ==========================================

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?string $centreFinancier = null; // ex: ACFA9B5FGL

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?Personnel $gestionnaire = null;

    #[ORM\ManyToOne(inversedBy: 'bonsCommande', cascade: ['persist'])]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?FinanceFournisseur $fournisseur = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private bool $commandeSurMarche = false;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?string $numeroBonCommande = null; // ex: 4500264424 (SIFAC)

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateBonCommande = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private bool $dateLivraisonRenseignee = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private bool $pjSurSifac = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private bool $bcTransmis = false;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?string $colisAttendu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateVerificationFinanciere = null;

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:detail'])]
    private ?Personnel $verificateurFinancier = null;

    // ==========================================
    // 3 - VISA ENGAGEMENT / COMMANDE
    // ==========================================

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateVisaEngagement = null;

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:detail'])]
    private ?Personnel $signataireVisaEngagement = null;

    // ==========================================
    // 4 & 5 - SERVICE FAIT & CERTIFICATION
    // ==========================================

    /**
     * @var Collection<int, FinanceReception>
     */
    #[ORM\OneToMany(targetEntity: FinanceReception::class, mappedBy: 'bonCommande', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private Collection $receptions;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateCertificationServiceFait = null;

    #[ORM\ManyToOne]
    #[Groups(['finance:read', 'finance:detail'])]
    private ?Personnel $certificateurServiceFait = null;

    // ==========================================
    // FACTURATION & PAIEMENT
    // ==========================================

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $dateArriveeFacture = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?string $rejetFactureMotif = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private bool $factureFinale = false;

    #[ORM\Column(length: 30, enumType: StatutCommandeEnum::class)]
    #[Groups(['finance:read', 'finance:list', 'finance:write'])]
    private StatutCommandeEnum $statut = StatutCommandeEnum::BROUILLON;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['finance:read', 'finance:detail'])]
    private ?string $pdfFilePath = null;

    public function __construct()
    {
        $this->receptions = new ArrayCollection();
        $this->dateDemande = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemandeur(): ?Personnel
    {
        return $this->demandeur;
    }

    public function setDemandeur(?Personnel $demandeur): static
    {
        $this->demandeur = $demandeur;
        return $this;
    }

    public function getResponsable(): ?Personnel
    {
        return $this->responsable;
    }

    public function setResponsable(?Personnel $responsable): static
    {
        $this->responsable = $responsable;
        return $this;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;
        return $this;
    }

    public function getService(): ?StructureService
    {
        return $this->service;
    }

    public function setService(?StructureService $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getPrestation(): TypePrestationEnum
    {
        return $this->prestation;
    }

    public function setPrestation(TypePrestationEnum $prestation): static
    {
        $this->prestation = $prestation;
        return $this;
    }

    public function getObjetDemande(): ?string
    {
        return $this->objetDemande;
    }

    public function setObjetDemande(string $objetDemande): static
    {
        $this->objetDemande = $objetDemande;
        return $this;
    }

    public function getMontantHt(): ?string
    {
        return $this->montantHt;
    }

    public function setMontantHt(string $montantHt): static
    {
        $this->montantHt = $montantHt;
        return $this;
    }

    public function getMontantTtc(): ?string
    {
        return $this->montantTtc;
    }

    public function setMontantTtc(?string $montantTtc): static
    {
        $this->montantTtc = $montantTtc;
        return $this;
    }

    public function getNoteExplicative(): ?string
    {
        return $this->noteExplicative;
    }

    public function setNoteExplicative(?string $noteExplicative): static
    {
        $this->noteExplicative = $noteExplicative;
        return $this;
    }

    public function getDateDemande(): ?\DateTimeInterface
    {
        return $this->dateDemande;
    }

    public function setDateDemande(?\DateTimeInterface $dateDemande): static
    {
        $this->dateDemande = $dateDemande;
        return $this;
    }

    public function getDateSignatureDemandeur(): ?\DateTimeInterface
    {
        return $this->dateSignatureDemandeur;
    }

    public function setDateSignatureDemandeur(?\DateTimeInterface $dateSignatureDemandeur): static
    {
        $this->dateSignatureDemandeur = $dateSignatureDemandeur;
        return $this;
    }

    public function getDateSignatureResponsable(): ?\DateTimeInterface
    {
        return $this->dateSignatureResponsable;
    }

    public function setDateSignatureResponsable(?\DateTimeInterface $dateSignatureResponsable): static
    {
        $this->dateSignatureResponsable = $dateSignatureResponsable;
        return $this;
    }

    public function getAvisDirecteur(): ?AvisDirecteurEnum
    {
        return $this->avisDirecteur;
    }

    public function setAvisDirecteur(?AvisDirecteurEnum $avisDirecteur): static
    {
        $this->avisDirecteur = $avisDirecteur;
        return $this;
    }

    public function getAvisDirecteurCommentaire(): ?string
    {
        return $this->avisDirecteurCommentaire;
    }

    public function setAvisDirecteurCommentaire(?string $avisDirecteurCommentaire): static
    {
        $this->avisDirecteurCommentaire = $avisDirecteurCommentaire;
        return $this;
    }

    public function getDateAvisDirecteur(): ?\DateTimeInterface
    {
        return $this->dateAvisDirecteur;
    }

    public function setDateAvisDirecteur(?\DateTimeInterface $dateAvisDirecteur): static
    {
        $this->dateAvisDirecteur = $dateAvisDirecteur;
        return $this;
    }

    public function getSignataireDirecteur(): ?Personnel
    {
        return $this->signataireDirecteur;
    }

    public function setSignataireDirecteur(?Personnel $signataireDirecteur): static
    {
        $this->signataireDirecteur = $signataireDirecteur;
        return $this;
    }

    public function getCentreFinancier(): ?string
    {
        return $this->centreFinancier;
    }

    public function setCentreFinancier(?string $centreFinancier): static
    {
        $this->centreFinancier = $centreFinancier;
        return $this;
    }

    public function getGestionnaire(): ?Personnel
    {
        return $this->gestionnaire;
    }

    public function setGestionnaire(?Personnel $gestionnaire): static
    {
        $this->gestionnaire = $gestionnaire;
        return $this;
    }

    public function getFournisseur(): ?FinanceFournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?FinanceFournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;
        return $this;
    }

    public function isCommandeSurMarche(): bool
    {
        return $this->commandeSurMarche;
    }

    public function setCommandeSurMarche(bool $commandeSurMarche): static
    {
        $this->commandeSurMarche = $commandeSurMarche;
        return $this;
    }

    public function getNumeroBonCommande(): ?string
    {
        return $this->numeroBonCommande;
    }

    public function setNumeroBonCommande(?string $numeroBonCommande): static
    {
        $this->numeroBonCommande = $numeroBonCommande;
        return $this;
    }

    public function getDateBonCommande(): ?\DateTimeInterface
    {
        return $this->dateBonCommande;
    }

    public function setDateBonCommande(?\DateTimeInterface $dateBonCommande): static
    {
        $this->dateBonCommande = $dateBonCommande;
        return $this;
    }

    public function isDateLivraisonRenseignee(): bool
    {
        return $this->dateLivraisonRenseignee;
    }

    public function setDateLivraisonRenseignee(bool $dateLivraisonRenseignee): static
    {
        $this->dateLivraisonRenseignee = $dateLivraisonRenseignee;
        return $this;
    }

    public function isPjSurSifac(): bool
    {
        return $this->pjSurSifac;
    }

    public function setPjSurSifac(bool $pjSurSifac): static
    {
        $this->pjSurSifac = $pjSurSifac;
        return $this;
    }

    public function isBcTransmis(): bool
    {
        return $this->bcTransmis;
    }

    public function setBcTransmis(bool $bcTransmis): static
    {
        $this->bcTransmis = $bcTransmis;
        return $this;
    }

    public function getColisAttendu(): ?string
    {
        return $this->colisAttendu;
    }

    public function setColisAttendu(?string $colisAttendu): static
    {
        $this->colisAttendu = $colisAttendu;
        return $this;
    }

    public function getDateVerificationFinanciere(): ?\DateTimeInterface
    {
        return $this->dateVerificationFinanciere;
    }

    public function setDateVerificationFinanciere(?\DateTimeInterface $dateVerificationFinanciere): static
    {
        $this->dateVerificationFinanciere = $dateVerificationFinanciere;
        return $this;
    }

    public function getVerificateurFinancier(): ?Personnel
    {
        return $this->verificateurFinancier;
    }

    public function setVerificateurFinancier(?Personnel $verificateurFinancier): static
    {
        $this->verificateurFinancier = $verificateurFinancier;
        return $this;
    }

    public function getDateVisaEngagement(): ?\DateTimeInterface
    {
        return $this->dateVisaEngagement;
    }

    public function setDateVisaEngagement(?\DateTimeInterface $dateVisaEngagement): static
    {
        $this->dateVisaEngagement = $dateVisaEngagement;
        return $this;
    }

    public function getSignataireVisaEngagement(): ?Personnel
    {
        return $this->signataireVisaEngagement;
    }

    public function setSignataireVisaEngagement(?Personnel $signataireVisaEngagement): static
    {
        $this->signataireVisaEngagement = $signataireVisaEngagement;
        return $this;
    }

    /**
     * @return Collection<int, FinanceReception>
     */
    public function getReceptions(): Collection
    {
        return $this->receptions;
    }

    public function addReception(FinanceReception $reception): static
    {
        if (!$this->receptions->contains($reception)) {
            $this->receptions->add($reception);
            $reception->setBonCommande($this);
        }
        return $this;
    }

    public function removeReception(FinanceReception $reception): static
    {
        if ($this->receptions->removeElement($reception)) {
            if ($reception->getBonCommande() === $this) {
                $reception->setBonCommande(null);
            }
        }
        return $this;
    }

    public function getDateCertificationServiceFait(): ?\DateTimeInterface
    {
        return $this->dateCertificationServiceFait;
    }

    public function setDateCertificationServiceFait(?\DateTimeInterface $dateCertificationServiceFait): static
    {
        $this->dateCertificationServiceFait = $dateCertificationServiceFait;
        return $this;
    }

    public function getCertificateurServiceFait(): ?Personnel
    {
        return $this->certificateurServiceFait;
    }

    public function setCertificateurServiceFait(?Personnel $certificateurServiceFait): static
    {
        $this->certificateurServiceFait = $certificateurServiceFait;
        return $this;
    }

    public function getDateArriveeFacture(): ?\DateTimeInterface
    {
        return $this->dateArriveeFacture;
    }

    public function setDateArriveeFacture(?\DateTimeInterface $dateArriveeFacture): static
    {
        $this->dateArriveeFacture = $dateArriveeFacture;
        return $this;
    }

    public function getRejetFactureMotif(): ?string
    {
        return $this->rejetFactureMotif;
    }

    public function setRejetFactureMotif(?string $rejetFactureMotif): static
    {
        $this->rejetFactureMotif = $rejetFactureMotif;
        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;
        return $this;
    }

    public function isFactureFinale(): bool
    {
        return $this->factureFinale;
    }

    public function setFactureFinale(bool $factureFinale): static
    {
        $this->factureFinale = $factureFinale;
        return $this;
    }

    public function getStatut(): StatutCommandeEnum
    {
        return $this->statut;
    }

    public function setStatut(StatutCommandeEnum $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getPdfFilePath(): ?string
    {
        return $this->pdfFilePath;
    }

    public function setPdfFilePath(?string $pdfFilePath): static
    {
        $this->pdfFilePath = $pdfFilePath;
        return $this;
    }
}
