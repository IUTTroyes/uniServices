<?php

namespace StageBundle\Entity\Stages;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\ValueObject\Adresse;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Repository\Stages\StageEtudiantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StageEtudiantRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['stage_etudiant:read']]),
        new GetCollection(normalizationContext: ['groups' => ['stage_etudiant:read']]),
        new Post(
            normalizationContext: ['groups' => ['stage_etudiant:read']],
            denormalizationContext: ['groups' => ['stage_etudiant:write']]
        ),
        new Patch(
            normalizationContext: ['groups' => ['stage_etudiant:read']],
            denormalizationContext: ['groups' => ['stage_etudiant:write']]
        ),
        new Delete()
    ]
)]
class StageEtudiant
{
    use UuidTrait;
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_periode_gestion', 'stage_etudiant:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StagePeriode::class, inversedBy: 'stageEtudiants')]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?StagePeriode $stagePeriode = null;

    #[ORM\ManyToOne(targetEntity: Etudiant::class, inversedBy: 'stageEtudiants')]
    #[Groups(['stage_periode_gestion', 'stage_etudiant:read', 'stage_etudiant:write'])]
    private ?Etudiant $etudiant = null;

    #[ORM\OneToOne(targetEntity: Contact::class, cascade: ['persist', 'remove'])]
    #[Groups(['stage_periode_gestion', 'stage_entreprise', 'stage_etudiant:read', 'stage_etudiant:write'])]
    private ?Contact $tuteur = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['stage_periode_gestion', 'stage_entreprise', 'stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $serviceStageEntreprise = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_entreprise', 'stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $sujetStage = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?\DateTimeInterface $dateDepotFormulaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?\DateTimeInterface $dateConventionEnvoyee = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?\DateTimeInterface $dateConventionRecu = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: EtatStageEnum::class)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private EtatStageEnum $etatStage = EtatStageEnum::AUTORISE;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['stage_entreprise_administration', 'stage_entreprise', 'stage_periode_gestion', 'stage_etudiant:read', 'stage_etudiant:write'])]
    private ?\DateTimeInterface $dateDebutStage = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['stage_entreprise_administration', 'stage_entreprise', 'stage_periode_gestion', 'stage_etudiant:read', 'stage_etudiant:write'])]
    private ?\DateTimeInterface $dateFinStage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $activites = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $amenagementStage = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private bool $gratification = false;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Type(
        type: 'float',
        message: 'La valeur {{ value }} doit être une valeur décimale positive.',
    )]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?float $gratificationMontant = null;

    #[ORM\Column(type: Types::STRING, length: 1, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $gratificationPeriode = 'H';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $avantages = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\Type(
        type: 'float',
        message: 'La valeur {{ value }} doit être une valeur décimale positive.',
    )]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private float $dureeHebdomadaire = 35.0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Type(
        type: 'integer',
        message: 'La valeur {{ value }} doit être entière et positive.',
    )]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private int $dureeJoursStage = 0;

    #[ORM\ManyToOne(targetEntity: Personnel::class, inversedBy: 'stageEtudiants')]
    #[Groups(['stage_periode_gestion', 'stage_etudiant:read', 'stage_etudiant:write'])]
    private ?Personnel $tuteurUniversitaire = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'stageEtudiants', cascade: ['persist', 'remove'])]
    #[Groups(['stage_entreprise_administration', 'stage_periode_gestion', 'stage_etudiant:read', 'stage_etudiant:write'])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $assuranceCompagnie = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $assuranceNumero = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?\DateTimeInterface $dateAutorise = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?\DateTimeInterface $dateImprime = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?array $adresseStage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $periodesInterruptions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['stage_etudiant:read', 'stage_etudiant:write'])]
    private ?string $commentaireDureeHebdomadaire = null;

    public function __construct(?float $gratificationMontant = null)
    {
        $this->setUuid();
        $this->gratificationMontant = $gratificationMontant;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStagePeriode(): ?StagePeriode
    {
        return $this->stagePeriode;
    }

    public function setStagePeriode(?StagePeriode $stagePeriode): self
    {
        $this->stagePeriode = $stagePeriode;

        return $this;
    }

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): self
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getTuteur(): ?Contact
    {
        return $this->tuteur;
    }

    public function setTuteur(?Contact $tuteur): self
    {
        $this->tuteur = $tuteur;

        return $this;
    }

    public function getServiceStageEntreprise(): ?string
    {
        return $this->serviceStageEntreprise;
    }

    public function setServiceStageEntreprise(?string $serviceStageEntreprise): self
    {
        $this->serviceStageEntreprise = $serviceStageEntreprise;

        return $this;
    }

    public function getSujetStage(): ?string
    {
        return $this->sujetStage;
    }

    public function setSujetStage(?string $sujetStage): self
    {
        $this->sujetStage = $sujetStage;

        return $this;
    }

    public function getDateDepotFormulaire(): ?\DateTimeInterface
    {
        return $this->dateDepotFormulaire;
    }

    public function setDateDepotFormulaire(?\DateTimeInterface $dateDepotFormulaire): self
    {
        $this->dateDepotFormulaire = $dateDepotFormulaire;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getDateConventionEnvoyee(): ?\DateTimeInterface
    {
        return $this->dateConventionEnvoyee;
    }

    public function setDateConventionEnvoyee(?\DateTimeInterface $dateConventionEnvoyee): self
    {
        $this->dateConventionEnvoyee = $dateConventionEnvoyee;

        return $this;
    }

    public function getDateConventionRecu(): ?\DateTimeInterface
    {
        return $this->dateConventionRecu;
    }

    public function setDateConventionRecu(?\DateTimeInterface $dateConventionRecu): self
    {
        $this->dateConventionRecu = $dateConventionRecu;

        return $this;
    }

    public function getEtatStage(): EtatStageEnum
    {
        return $this->etatStage;
    }

    public function setEtatStage(EtatStageEnum $etatStage): self
    {
        $this->etatStage = $etatStage;

        return $this;
    }

    public function getActivites(): ?string
    {
        return $this->activites;
    }

    public function setActivites(?string $activites): self
    {
        $this->activites = $activites;

        return $this;
    }

    public function getAmenagementStage(): ?string
    {
        return $this->amenagementStage;
    }

    public function setAmenagementStage(?string $amenagementStage): self
    {
        $this->amenagementStage = $amenagementStage;

        return $this;
    }

    public function getGratification(): bool
    {
        return $this->gratification;
    }

    public function setGratification(bool $gratification): self
    {
        $this->gratification = $gratification;

        return $this;
    }

    public function getGratificationMontant(): ?float
    {
        return $this->gratificationMontant;
    }

    public function setGratificationMontant(?float $gratificationMontant): self
    {
        $this->gratificationMontant = $gratificationMontant;

        return $this;
    }

    public function getGratificationPeriode(): ?string
    {
        return $this->gratificationPeriode;
    }

    public function setGratificationPeriode(?string $gratificationPeriode): self
    {
        $this->gratificationPeriode = $gratificationPeriode;

        return $this;
    }

    public function getAvantages(): ?string
    {
        return $this->avantages;
    }

    public function setAvantages(?string $avantages): self
    {
        $this->avantages = $avantages;

        return $this;
    }

    public function getDureeHebdomadaire(): float
    {
        return $this->dureeHebdomadaire;
    }

    public function setDureeHebdomadaire(float $dureeHebdomadaire): self
    {
        $this->dureeHebdomadaire = $dureeHebdomadaire;

        return $this;
    }

    public function getDureeJoursStage(): int
    {
        return $this->dureeJoursStage;
    }

    public function setDureeJoursStage(int $dureeJoursStage): self
    {
        $this->dureeJoursStage = $dureeJoursStage;

        return $this;
    }

    public function getTuteurUniversitaire(): ?Personnel
    {
        return $this->tuteurUniversitaire;
    }

    public function setTuteurUniversitaire(?Personnel $tuteurUniversitaire): self
    {
        $this->tuteurUniversitaire = $tuteurUniversitaire;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getDateAutorise(): ?\DateTimeInterface
    {
        return $this->dateAutorise;
    }

    public function setDateAutorise(?\DateTimeInterface $dateAutorise): self
    {
        $this->dateAutorise = $dateAutorise;

        return $this;
    }

    public function getDateImprime(): ?\DateTimeInterface
    {
        return $this->dateImprime;
    }

    public function setDateImprime(?\DateTimeInterface $dateImprime): self
    {
        $this->dateImprime = $dateImprime;

        return $this;
    }

    public function getAdresseStage(): ?Adresse
    {
        if ($this->adresseStage === null) {
            return null;
        }
        return Adresse::fromArray($this->adresseStage);
    }

    public function setAdresseStage(?Adresse $adresseStage): self
    {
        $this->adresseStage = $adresseStage ? $adresseStage->toArray() : null;

        return $this;
    }

    public function getPeriodesInterruptions(): ?string
    {
        return $this->periodesInterruptions;
    }

    public function setPeriodesInterruptions(?string $periodesInterruptions): self
    {
        $this->periodesInterruptions = $periodesInterruptions;

        return $this;
    }

    public function getCommentaireDureeHebdomadaire(): ?string
    {
        return $this->commentaireDureeHebdomadaire;
    }

    public function setCommentaireDureeHebdomadaire(?string $commentaireDureeHebdomadaire): self
    {
        $this->commentaireDureeHebdomadaire = $commentaireDureeHebdomadaire;

        return $this;
    }

    public function dateDebutStageFr(): string
    {
        return null !== $this->getDateDebutStage() ? $this->getDateDebutStage()->format('d/m/Y') : '-';
    }

    public function getDateDebutStage(): ?\DateTimeInterface
    {
        return $this->dateDebutStage;
    }

    public function setDateDebutStage(?\DateTimeInterface $dateDebutStage): self
    {
        $this->dateDebutStage = $dateDebutStage;

        return $this;
    }

    public function dateFinStageFr(): string
    {
        return null !== $this->getDateFinStage() ? $this->getDateFinStage()->format('d/m/Y') : '-';
    }

    public function getDateFinStage(): ?\DateTimeInterface
    {
        return $this->dateFinStage;
    }

    public function setDateFinStage(?\DateTimeInterface $dateFinStage): self
    {
        $this->dateFinStage = $dateFinStage;

        return $this;
    }

    public function getAssuranceCompagnie(): ?string
    {
        return $this->assuranceCompagnie;
    }

    public function setAssuranceCompagnie(?string $assuranceCompagnie): self
    {
        $this->assuranceCompagnie = $assuranceCompagnie;

        return $this;
    }

    public function getAssuranceNumero(): ?string
    {
        return $this->assuranceNumero;
    }

    public function setAssuranceNumero(?string $assuranceNumero): self
    {
        $this->assuranceNumero = $assuranceNumero;

        return $this;
    }
}
