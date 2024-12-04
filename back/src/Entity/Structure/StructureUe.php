<?php

namespace App\Entity\Structure;

use App\Entity\ApcCompetence;
use App\Entity\Scolarite\ScolEnseignement;
use App\Repository\UeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UeRepository::class)]
class StructureUe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?int $numero = null;

    #[ORM\Column]
    private ?int $nbEcts = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column]
    private ?bool $bonification = null;

    #[ORM\Column(length: 15)]
    private ?string $codeElement = null;

    #[ORM\ManyToOne(inversedBy: 'ues')]
    private ?ApcCompetence $apcCompetence = null;

    /**
     * @var Collection<int, ScolEnseignement>
     */
    #[ORM\ManyToMany(targetEntity: ScolEnseignement::class, mappedBy: 'ue')]
    private Collection $scolEnseignements;

    public function __construct()
    {
        $this->scolEnseignements = new ArrayCollection();
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

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getNbEcts(): ?int
    {
        return $this->nbEcts;
    }

    public function setNbEcts(int $nbEcts): static
    {
        $this->nbEcts = $nbEcts;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function isBonification(): ?bool
    {
        return $this->bonification;
    }

    public function setBonification(bool $bonification): static
    {
        $this->bonification = $bonification;

        return $this;
    }

    public function getCodeElement(): ?string
    {
        return $this->codeElement;
    }

    public function setCodeElement(string $codeElement): static
    {
        $this->codeElement = $codeElement;

        return $this;
    }

    public function getApcCompetence(): ?ApcCompetence
    {
        return $this->apcCompetence;
    }

    public function setApcCompetence(?ApcCompetence $apcCompetence): static
    {
        $this->apcCompetence = $apcCompetence;

        return $this;
    }

    /**
     * @return Collection<int, ScolEnseignement>
     */
    public function getScolEnseignements(): Collection
    {
        return $this->scolEnseignements;
    }

    public function addScolEnseignement(ScolEnseignement $scolEnseignement): static
    {
        if (!$this->scolEnseignements->contains($scolEnseignement)) {
            $this->scolEnseignements->add($scolEnseignement);
            $scolEnseignement->addUe($this);
        }

        return $this;
    }

    public function removeScolEnseignement(ScolEnseignement $scolEnseignement): static
    {
        if ($this->scolEnseignements->removeElement($scolEnseignement)) {
            $scolEnseignement->removeUe($this);
        }

        return $this;
    }
}
