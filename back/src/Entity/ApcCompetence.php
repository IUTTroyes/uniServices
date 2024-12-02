<?php

namespace App\Entity;

use App\Entity\Structure\StructureUe;
use App\Repository\ApcCompetenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApcCompetenceRepository::class)]
class ApcCompetence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nomCourt = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleur = null;

    #[ORM\ManyToOne(inversedBy: 'apcCompetences')]
    private ?ApcReferentiel $referentiel = null;

    /**
     * @var Collection<int, ApcNiveau>
     */
    #[ORM\OneToMany(targetEntity: ApcNiveau::class, mappedBy: 'apcCompetence')]
    private Collection $niveau;

    #[ORM\Column]
    private array $composantesEssentielles = [];

    #[ORM\Column]
    private array $situationsProfessionnelles = [];

    /**
     * @var Collection<int, StructureUe>
     */
    #[ORM\OneToMany(targetEntity: StructureUe::class, mappedBy: 'apcCompetence')]
    private Collection $ues;

    public function __construct()
    {
        $this->niveau = new ArrayCollection();
        $this->ues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getNomCourt(): ?string
    {
        return $this->nomCourt;
    }

    public function setNomCourt(?string $nomCourt): static
    {
        $this->nomCourt = $nomCourt;

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

    public function getReferentiel(): ?ApcReferentiel
    {
        return $this->referentiel;
    }

    public function setReferentiel(?ApcReferentiel $referentiel): static
    {
        $this->referentiel = $referentiel;

        return $this;
    }

    /**
     * @return Collection<int, ApcNiveau>
     */
    public function getNiveau(): Collection
    {
        return $this->niveau;
    }

    public function addNiveau(ApcNiveau $niveau): static
    {
        if (!$this->niveau->contains($niveau)) {
            $this->niveau->add($niveau);
            $niveau->setApcCompetence($this);
        }

        return $this;
    }

    public function removeNiveau(ApcNiveau $niveau): static
    {
        if ($this->niveau->removeElement($niveau)) {
            // set the owning side to null (unless already changed)
            if ($niveau->getApcCompetence() === $this) {
                $niveau->setApcCompetence(null);
            }
        }

        return $this;
    }

    public function getComposantesEssentielles(): array
    {
        return $this->composantesEssentielles;
    }

    public function setComposantesEssentielles(array $composantesEssentielles): static
    {
        $this->composantesEssentielles = $composantesEssentielles;

        return $this;
    }

    public function getSituationsProfessionnelles(): array
    {
        return $this->situationsProfessionnelles;
    }

    public function setSituationsProfessionnelles(array $situationsProfessionnelles): static
    {
        $this->situationsProfessionnelles = $situationsProfessionnelles;

        return $this;
    }

    /**
     * @return Collection<int, StructureUe>
     */
    public function getUes(): Collection
    {
        return $this->ues;
    }

    public function addUe(StructureUe $ue): static
    {
        if (!$this->ues->contains($ue)) {
            $this->ues->add($ue);
            $ue->setApcCompetence($this);
        }

        return $this;
    }

    public function removeUe(StructureUe $ue): static
    {
        if ($this->ues->removeElement($ue)) {
            // set the owning side to null (unless already changed)
            if ($ue->getApcCompetence() === $this) {
                $ue->setApcCompetence(null);
            }
        }

        return $this;
    }
}
