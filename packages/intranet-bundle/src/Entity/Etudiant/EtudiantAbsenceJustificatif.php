<?php

namespace IntranetBundle\Entity\Etudiant;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use IntranetBundle\Enum\EtatJustificatifEnum;
use IntranetBundle\Filter\JustificatifAbsenceFilter;
use IntranetBundle\Repository\Etudiant\EtudiantAbsenceJustificatifRepository;
use IntranetBundle\State\Processor\Absence\EtudiantAbsenceJustificatifCreateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EtudiantAbsenceJustificatifRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(JustificatifAbsenceFilter::class)]
#[ApiFilter(OrderFilter::class, properties: [
    'debut',
    'fin',
    'motif',
    'etat',
    'scolariteSemestre.scolarite.etudiant.nom',
])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/administration/etudiant_absence_justificatifs',
            normalizationContext: ['groups' => ['justificatif:administration']],
        ),
        new Post(
            uriTemplate: '/administration/etudiant_absence_justificatifs',
            denormalizationContext: ['groups' => ['justificatif:write:administration']],
            normalizationContext: ['groups' => ['justificatif:administration']],
            processor: EtudiantAbsenceJustificatifCreateProcessor::class,
        ),
        new Patch(
            uriTemplate: '/administration/etudiant_absence_justificatifs/{id}',
            denormalizationContext: ['groups' => ['justificatif:write:administration']],
            normalizationContext: ['groups' => ['justificatif:administration']],
        ),
        new Delete(
            uriTemplate: '/administration/etudiant_absence_justificatifs/{id}',
            normalizationContext: ['groups' => ['justificatif:administration']],
        )
    ],
    order: ['debut' => 'DESC']
)]
class EtudiantAbsenceJustificatif
{
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['absence:administration', 'justificatif:administration'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['justificatif:administration', 'justificatif:write:administration'])]
    private ?\DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['justificatif:administration', 'justificatif:write:administration'])]
    private ?\DateTimeInterface $fin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['justificatif:administration', 'justificatif:write:administration'])]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['justificatif:administration', 'justificatif:write:administration'])]
    private ?string $motif_refus = null;

    #[ORM\Column(type: Types::SMALLINT, enumType: EtatJustificatifEnum::class)]
    #[Groups(['absence:administration', 'justificatif:administration', 'justificatif:write:administration'])]
    private EtatJustificatifEnum $etat = EtatJustificatifEnum::EN_ATTENTE;

    #[ORM\Column(nullable: true, length: 255)]
    #[Groups(['justificatif:administration', 'justificatif:write:administration'])]
    private ?string $fichier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['justificatif:administration', 'justificatif:write:administration'])]
    private ?EtudiantScolariteSemestre $scolariteSemestre = null;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'absenceJustificatif')]
    private Collection $absence;

    public function __construct()
    {
        $this->absence = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDebut(): ?\DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(?\DateTimeInterface $debut): void
    {
        $this->debut = $debut;
    }

    public function getFin(): ?\DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(?\DateTimeInterface $fin): void
    {
        $this->fin = $fin;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getEtat(): EtatJustificatifEnum
    {
        return $this->etat;
    }

    public function setEtat(EtatJustificatifEnum $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    #[Groups(['justificatif:administration'])]
    public function getEtatOptions(): array
    {
        return $this->etat->getOptions();
    }


    #[Groups(['justificatif:administration'])]
    public function getEtatLibelle(): string
    {
        return $this->etat->getLibelle();
    }

    #[Groups(['justificatif:administration'])]
    public function getEtatBadge(): string
    {
        return $this->etat->getBadge();
    }

    public function getScolariteSemestre(): ?EtudiantScolariteSemestre
    {
        return $this->scolariteSemestre;
    }

    public function setScolariteSemestre(?EtudiantScolariteSemestre $scolariteSemestre): static
    {
        $this->scolariteSemestre = $scolariteSemestre;

        return $this;
    }

    /**
     * @return Collection<int, EtudiantAbsence>
     */
    #[Groups(['justificatif:administration'])]
    public function getAbsence(): Collection
    {
        return $this->absence;
    }

    #[Groups(['justificatif:administration'])]
    public function getAbsencesCount(): int
    {
        return $this->absence->count();
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(?string $fichier): void
    {
        $this->fichier = $fichier;
    }

    public function couvre(\DateTimeInterface $eventDebut, \DateTimeInterface $eventFin): bool
    {
        if (null === $this->debut || null === $this->fin) {
            return false;
        }

        return $this->debut <= $eventDebut && $this->fin >= $eventFin;
    }

    public function addAbsence(EtudiantAbsence $absence): static
    {
        if (!$this->absence->contains($absence)) {
            $this->absence->add($absence);
            $absence->setAbsenceJustificatif($this);
        }

        return $this;
    }

    public function removeAbsence(EtudiantAbsence $absence): static
    {
        if ($this->absence->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getAbsenceJustificatif() === $this) {
                $absence->setAbsenceJustificatif(null);
            }
        }

        return $this;
    }

    public function getMotifRefus(): ?string
    {
        return $this->motif_refus;
    }

    public function setMotifRefus(?string $motif_refus): void
    {
        $this->motif_refus = $motif_refus;
    }

    #[Groups(['justificatif:administration'])]
    public function getEtudiant(): ?Etudiant
    {
        return $this->scolariteSemestre->getScolarite()?->getEtudiant();
    }
}
