<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Users\Personnel;
use App\Enum\FicheHeureStatutEnum;
use App\State\FicheHeureRejectProcessor;
use App\State\FicheHeureSubmitProcessor;
use App\State\FicheHeureValidateProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['ficheheure:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['ficheheure:write']],
            security: "is_granted('FICHE_HEURE_CREATE', 'FicheHeure::class')"
        ),
        new Get(
            normalizationContext: ['groups' => ['ficheheure:read']],
            security: "is_granted('FICHE_HEURE_READ_OWN', object) or is_granted('FICHE_HEURE_VALIDATE', object)"
        ),
        new Patch( // General edit
            denormalizationContext: ['groups' => ['ficheheure:write']],
            security: "is_granted('FICHE_HEURE_EDIT_OWN', object)" // General edit should only be by owner
        ),
        new Patch( // Custom Submit operation
            uriTemplate: '/fiches_heures/{id}/submit',
            denormalizationContext: ['groups' => ['ficheheure:submit']],
            security: "is_granted('FICHE_HEURE_SUBMIT_OWN', object)",
            processor: FicheHeureSubmitProcessor::class
        ),
        new Patch( // Custom Validate operation
            uriTemplate: '/fiches_heures/{id}/validate',
            denormalizationContext: ['groups' => ['ficheheure:validate']],
            security: "is_granted('FICHE_HEURE_VALIDATE', object)",
            processor: FicheHeureValidateProcessor::class
        ),
        new Patch( // Custom Reject operation
            uriTemplate: '/fiches_heures/{id}/reject',
            denormalizationContext: ['groups' => ['ficheheure:reject']],
            security: "is_granted('FICHE_HEURE_VALIDATE', object)", // Same role for validate/reject
            processor: FicheHeureRejectProcessor::class
        ),
    ]
)]
class FicheHeure
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['ficheheure:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ficheheure:read', 'ficheheure:write'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['ficheheure:read', 'ficheheure:write'])]
    private ?string $semaineAnnee = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['ficheheure:read', 'ficheheure:write'])]
    private array $heures = [];

    #[ORM\Column(length: 255, enumType: FicheHeureStatutEnum::class)]
    #[Groups(['ficheheure:read'])]
    private ?FicheHeureStatutEnum $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['ficheheure:read'])]
    private ?\DateTimeInterface $dateSoumission = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['ficheheure:read'])]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['ficheheure:read'])]
    private ?Personnel $validateur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ficheheure:read', 'ficheheure:write', 'ficheheure:reject'])]
    private ?string $commentaireValidation = null;

    public function __construct()
    {
        // $this->createdAt = new \DateTimeImmutable(); // LifeCycleTrait handles created/updated
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getSemaineAnnee(): ?string
    {
        return $this->semaineAnnee;
    }

    public function setSemaineAnnee(string $semaineAnnee): static
    {
        $this->semaineAnnee = $semaineAnnee;

        return $this;
    }

    public function getHeures(): array
    {
        return $this->heures;
    }

    public function setHeures(array $heures): static
    {
        $this->heures = $heures;

        return $this;
    }

    public function getStatut(): ?FicheHeureStatutEnum
    {
        return $this->statut;
    }

    public function setStatut(FicheHeureStatutEnum $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateSoumission(): ?\DateTimeInterface
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(?\DateTimeInterface $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getValidateur(): ?Personnel
    {
        return $this->validateur;
    }

    public function setValidateur(?Personnel $validateur): static
    {
        $this->validateur = $validateur;

        return $this;
    }

    public function getCommentaireValidation(): ?string
    {
        return $this->commentaireValidation;
    }

    public function setCommentaireValidation(?string $commentaireValidation): static
    {
        $this->commentaireValidation = $commentaireValidation;

        return $this;
    }
}
