<?php

namespace App\Entity\Etudiant;

use App\Entity\Traits\UuidTrait;
use App\Repository\EtudiantAbsenceJustificatifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtudiantAbsenceJustificatifRepository::class)]
class EtudiantAbsenceJustificatif
{
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $etat = 0;

    #[ORM\Column(nullable: true, length: 255)]
    private ?string $fichier = null;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;

        return $this;
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

    public function getEtat(): ?int
    {
        return $this->etat;
    }

    public function setEtat(int $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * @return Collection<int, EtudiantAbsence>
     */
    public function getAbsence(): Collection
    {
        return $this->absence;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(?string $fichier): void
    {
        $this->fichier = $fichier;
    }

    //todo: le dépot d'un justificatif pour une date ultérieure doit-il être possible ? si oui : provoque la création d'une ou plusieurs absences pour la durée du justificatif. Impossible si pas de cours sur la période ?
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
}
