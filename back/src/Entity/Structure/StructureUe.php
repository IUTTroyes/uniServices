<?php

namespace App\Entity\Structure;

use App\Entity\Apc\ApcCompetence;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Repository\Structure\StructureUeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureUeRepository::class)]
class StructureUe
{
//    use LifeCycleTrait;
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['semestre:read:full'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['semestre:read:full', 'structure_pn:read'])]
    private string $libelle = '';

    #[ORM\Column]
    #[Groups(['semestre:read:full', 'structure_pn:read'])]
    private int $numero = 0;

    #[ORM\Column]
    #[Groups(['semestre:read:full', 'structure_pn:read'])]
    private float $nbEcts = 0;

    #[ORM\Column]
    #[Groups(['semestre:read:full'])]
    private bool $actif = true;

    #[ORM\Column]
    #[Groups(['semestre:read:full'])]
    private bool $bonification = false;

    #[ORM\Column(length: 15)]
    #[Groups(['semestre:read:full', 'structure_pn:read'])]
    private string $codeElement = '';

    #[ORM\ManyToOne(inversedBy: 'ues')]
    #[Groups(['structure_pn:read', 'scol_enseignement:read'])]
    private ?ApcCompetence $apcCompetence = null;

    #[ORM\ManyToOne(inversedBy: 'structureUes')]
    #[Groups(['scol_enseignement:read'])]
    private ?StructureSemestre $semestre = null;

    /**
     * @var Collection<int, ScolEnseignementUe>
     */
    #[ORM\OneToMany(targetEntity: ScolEnseignementUe::class, mappedBy: 'ue')]
    #[Groups(['semestre:read:full', 'structure_pn:read'])]
    private Collection $scolEnseignementUes;

    // todo: add coeff. ?

    public function __construct()
    {
        $this->scolEnseignementUes = new ArrayCollection();
    }

    #[Groups(['structure_pn:read'])]
    public function getDisplayApc(): string
    {
        return $this->apcCompetence ? $this->libelle.' | '.$this->apcCompetence->getNomCourt() : $this->libelle;
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

    public function getNbEcts(): ?float
    {
        return $this->nbEcts;
    }

    public function setNbEcts(float $nbEcts): static
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

    public function getSemestre(): ?StructureSemestre
    {
        return $this->semestre;
    }

    public function setSemestre(?StructureSemestre $semestre): static
    {
        $this->semestre = $semestre;

        return $this;
    }

    /**
     * @return Collection<int, ScolEnseignementUe>
     */
    public function getScolEnseignementUes(): Collection
    {
        return $this->scolEnseignementUes;
    }

    public function addScolEnseignementUe(ScolEnseignementUe $scolEnseignementUe): static
    {
        if (!$this->scolEnseignementUes->contains($scolEnseignementUe)) {
            $this->scolEnseignementUes->add($scolEnseignementUe);
            $scolEnseignementUe->setUe($this);
        }

        return $this;
    }

    public function removeScolEnseignementUe(ScolEnseignementUe $scolEnseignementUe): static
    {
        if ($this->scolEnseignementUes->removeElement($scolEnseignementUe)) {
            // set the owning side to null (unless already changed)
            if ($scolEnseignementUe->getUe() === $this) {
                $scolEnseignementUe->setUe(null);
            }
        }

        return $this;
    }
}
