<?php

namespace StageBundle\Entity\Stages;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\LifeCycleTrait;
use StageBundle\Repository\Stages\StageAvenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StageAvenantRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['stage_avenant:read']]),
        new GetCollection(normalizationContext: ['groups' => ['stage_avenant:read']]),
        new Post(
            normalizationContext: ['groups' => ['stage_avenant:read']],
            denormalizationContext: ['groups' => ['stage_avenant:write']]
        ),
        new Patch(
            normalizationContext: ['groups' => ['stage_avenant:read']],
            denormalizationContext: ['groups' => ['stage_avenant:write']]
        ),
        new Delete()
    ]
)]
class StageAvenant
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stage_avenant:read', 'stage_etudiant:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StageEtudiant::class, inversedBy: 'avenants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['stage_avenant:read', 'stage_avenant:write'])]
    private ?StageEtudiant $stageEtudiant = null;

    #[ORM\Column(length: 150)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?string $texte = null;

    #[ORM\Column(length: 25)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?string $typeModification = 'AUTRE'; // DATES, HORAIRES, GRATIFICATION, TUTEUR, AUTRE

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private bool $valide = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['stage_avenant:read', 'stage_etudiant:read'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?\DateTimeInterface $newDateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?\DateTimeInterface $newDateFin = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?float $newDureeHebdomadaire = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?float $newGratificationMontant = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['stage_avenant:read', 'stage_avenant:write', 'stage_etudiant:read'])]
    private ?Contact $newTuteur = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStageEtudiant(): ?StageEtudiant
    {
        return $this->stageEtudiant;
    }

    public function setStageEtudiant(?StageEtudiant $stageEtudiant): self
    {
        $this->stageEtudiant = $stageEtudiant;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(string $texte): self
    {
        $this->texte = $texte;

        return $this;
    }

    public function getTypeModification(): ?string
    {
        return $this->typeModification;
    }

    public function setTypeModification(string $typeModification): self
    {
        $this->typeModification = $typeModification;

        return $this;
    }

    public function isValide(): bool
    {
        return $this->valide;
    }

    public function setValide(bool $valide): self
    {
        $this->valide = $valide;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

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

    public function getNewDateDebut(): ?\DateTimeInterface
    {
        return $this->newDateDebut;
    }

    public function setNewDateDebut(?\DateTimeInterface $newDateDebut): self
    {
        $this->newDateDebut = $newDateDebut;

        return $this;
    }

    public function getNewDateFin(): ?\DateTimeInterface
    {
        return $this->newDateFin;
    }

    public function setNewDateFin(?\DateTimeInterface $newDateFin): self
    {
        $this->newDateFin = $newDateFin;

        return $this;
    }

    public function getNewDureeHebdomadaire(): ?float
    {
        return $this->newDureeHebdomadaire;
    }

    public function setNewDureeHebdomadaire(?float $newDureeHebdomadaire): self
    {
        $this->newDureeHebdomadaire = $newDureeHebdomadaire;

        return $this;
    }

    public function getNewGratificationMontant(): ?float
    {
        return $this->newGratificationMontant;
    }

    public function setNewGratificationMontant(?float $newGratificationMontant): self
    {
        $this->newGratificationMontant = $newGratificationMontant;

        return $this;
    }

    public function getNewTuteur(): ?Contact
    {
        return $this->newTuteur;
    }

    public function setNewTuteur(?Contact $newTuteur): self
    {
        $this->newTuteur = $newTuteur;

        return $this;
    }
}
