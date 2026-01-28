<?php

namespace App\Entity\Edt;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\ApiDto\Edt\EdtStatsDto;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Personnel;
use App\Filter\EdtFilter;
use App\Repository\Edt\EdtEventRepository;
use App\State\Edt\EdtStatsProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity(repositoryClass: EdtEventRepository::class)]
#[ApiFilter(BooleanFilter::class, properties: ['aPlacer'])]
#[ApiFilter(EdtFilter::class)]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['edt_event:read:agenda']]),
        new GetCollection(
            uriTemplate: '/stats/edt_events',
            normalizationContext: ['groups' => ['edt_stats:read']],
            provider: EdtStatsProvider::class,
            output: EdtStatsDto::class,
        ),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class EdtEvent
{
    use UuidTrait;
    use LifeCycleTrait;
    use EduSignTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['edt_event:read:agenda'])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $semaineFormation = null;

    #[ORM\Column(nullable: true)]
    private ?int $jour = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?\DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?\DateTimeInterface $fin = null;

    #[ORM\Column(length: 25)]
    #[Groups(['edt_event:read:agenda'])]
    private ?string $salle = '-';

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $codeSalle = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[Groups(['edt_event:read:agenda'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codePersonnel = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?string $libPersonnel = null;

    #[ORM\ManyToOne(inversedBy: 'edtEvents')]
    #[Groups(['edt_event:read:agenda'])]
    private ?ScolEnseignement $enseignement = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?string $codeModule = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?string $libModule = null;

    #[ORM\ManyToOne(inversedBy: 'edtEvents')]
    #[Groups(['edt_event:read:agenda'])]
    private ?StructureGroupe $groupe = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?string $codeGroupe = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?string $libGroupe = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?string $couleur = null;

    #[ORM\Column(nullable: true)]
    private ?int $celcatId = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['edt_event:read:agenda'])]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'edtEvents')]
    #[Groups(['edt_event:read:agenda'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\Column(nullable: true)]
    private ?int $departementCodeCelcat = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[Groups(['edt_event:read:agenda'])]
    private ?StructureSemestre $semestre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedEvent = null;

    #[ORM\Column]
    #[Groups(['edt_event:read:agenda'])]
    private bool $evaluation = false;

    #[ORM\Column]
    private ?bool $aPlacer = true;

    #[ORM\Column(nullable: true)]
    private ?int $ordreSeance = null;

    public function __construct() {
        $this->uuid = new UuidV4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSemaineFormation(): ?int
    {
        return $this->semaineFormation;
    }

    public function setSemaineFormation(?int $semaineFormation): static
    {
        $this->semaineFormation = $semaineFormation;

        return $this;
    }

    public function getJour(): ?int
    {
        return $this->jour;
    }

    public function setJour(?int $jour): static
    {
        $this->jour = $jour;

        return $this;
    }

    public function getUuid(): UuidV4
    {
        return $this->uuid;
    }

    public function setUuid(UuidV4 $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDebut(): ?\DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(?\DateTimeInterface $debut): static
    {
        $this->debut = $debut;

        return $this;
    }

    public function getFin(): ?\DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(?\DateTimeInterface $fin): static
    {
        $this->fin = $fin;

        return $this;
    }

    public function getSalle(): ?string
    {
        return $this->salle;
    }

    public function setSalle(string $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getCodeSalle(): ?string
    {
        return $this->codeSalle;
    }

    public function setCodeSalle(?string $codeSalle): static
    {
        $this->codeSalle = $codeSalle;

        return $this;
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

    public function getCodePersonnel(): ?string
    {
        return $this->codePersonnel;
    }

    public function setCodePersonnel(?string $codePersonnel): static
    {
        $this->codePersonnel = $codePersonnel;

        return $this;
    }

    public function getLibPersonnel(): ?string
    {
        return $this->libPersonnel;
    }

    public function setLibPersonnel(?string $libPersonnel): static
    {
        $this->libPersonnel = $libPersonnel;

        return $this;
    }

    public function getEnseignement(): ?ScolEnseignement
    {
        return $this->enseignement;
    }

    public function setEnseignement(?ScolEnseignement $enseignement): static
    {
        $this->enseignement = $enseignement;

        return $this;
    }

    public function getCodeModule(): ?string
    {
        return $this->codeModule;
    }

    public function setCodeModule(?string $codeModule): static
    {
        $this->codeModule = $codeModule;

        return $this;
    }

    public function getLibModule(): ?string
    {
        return $this->libModule;
    }

    public function setLibModule(?string $libModule): static
    {
        $this->libModule = $libModule;

        return $this;
    }

    public function getGroupe(): ?StructureGroupe
    {
        return $this->groupe;
    }

    public function setGroupe(?StructureGroupe $groupe): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function getCodeGroupe(): ?string
    {
        return $this->codeGroupe;
    }

    public function setCodeGroupe(?string $codeGroupe): static
    {
        $this->codeGroupe = $codeGroupe;

        return $this;
    }

    public function getLibGroupe(): ?string
    {
        return $this->libGroupe;
    }

    public function setLibGroupe(?string $libGroupe): static
    {
        $this->libGroupe = $libGroupe;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getCelcatId(): ?int
    {
        return $this->celcatId;
    }

    public function setCelcatId(?int $celcatId): static
    {
        $this->celcatId = $celcatId;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

        return $this;
    }

    public function getDepartementCodeCelcat(): ?int
    {
        return $this->departementCodeCelcat;
    }

    public function setDepartementCodeCelcat(?int $departementCodeCelcat): static
    {
        $this->departementCodeCelcat = $departementCodeCelcat;

        return $this;
    }

    public function getSemestre(): ?StructureSemestre
    {
        return $this->semestre;
    }

    public function setSemestre(?StructureSemestre $semestre): static
    {
        $this->semestre = $semestre;

        return $this;
    }

    public function getUpdatedEvent(): ?\DateTimeInterface
    {
        return $this->updatedEvent;
    }

    public function setUpdatedEvent(?\DateTimeInterface $updatedEvent): static
    {
        $this->updatedEvent = $updatedEvent;

        return $this;
    }

    public function isEvaluation(): ?bool
    {
        return $this->evaluation;
    }

    public function setEvaluation(bool $evaluation): static
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    public function isAPlacer(): ?bool
    {
        return $this->aPlacer;
    }

    public function setAPlacer(bool $aPlacer): static
    {
        $this->aPlacer = $aPlacer;

        return $this;
    }

    public function getOrdreSeance(): ?int
    {
        return $this->ordreSeance;
    }

    public function setOrdreSeance(?int $ordreSeance): static
    {
        $this->ordreSeance = $ordreSeance;

        return $this;
    }
}
