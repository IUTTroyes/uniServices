<?php

namespace IntranetBundle\Entity\Etudiant;

use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use IntranetBundle\Enum\EtatJustificatifEnum;
use IntranetBundle\Repository\Etudiant\EtudiantAbsenceJustificatifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtudiantAbsenceJustificatifRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EtudiantAbsenceJustificatif
{
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::SMALLINT, enumType: EtatJustificatifEnum::class)]
    private EtatJustificatifEnum $etat = EtatJustificatifEnum::EN_ATTENTE;

    #[ORM\Column(nullable: true, length: 255)]
    private ?string $fichier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etudiant $etudiant = null;

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

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): static
    {
        $this->etudiant = $etudiant;

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
}
