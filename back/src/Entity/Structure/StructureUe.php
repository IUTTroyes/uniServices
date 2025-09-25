<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Filter\UeFilter;
use App\Repository\Structure\StructureUeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureUeRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['ue:read', 'ue:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['ue:read']])
    ]
)]
#[ApiFilter(UeFilter::class)]
class StructureUe
{
//    use LifeCycleTrait;
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ue:read', 'maquette:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ue:read', 'maquette:detail'])]
    private string $libelle = '';

    #[ORM\Column]
    #[Groups(['maquette:detail', 'ue:read'])]
    private int $numero = 0;

    #[ORM\Column]
    #[Groups(['ue:read', 'maquette:detail'])]
    private float $nbEcts = 0;

    #[ORM\Column]
    #[Groups(['ue:read'])]
    private bool $actif = true;

    #[ORM\Column]
    #[Groups(['maquette:detail'])]
    private bool $bonification = false;

    #[ORM\Column(length: 15)]
    #[Groups(['ue:read', 'maquette:detail'])]
    private string $codeElement = '';

    #[ORM\ManyToOne(inversedBy: 'ues')]
    #[Groups(['pn:read', 'enseignement:read', 'maquette:detail', 'enseignement:detail'])]
    private ?ApcCompetence $competence = null;

    #[ORM\ManyToOne(inversedBy: 'ues')]
    #[Groups(['enseignement:read'])]
    private ?StructureSemestre $semestre = null;

    /**
     * @var Collection<int, ScolEnseignementUe>
     */
    #[ORM\OneToMany(targetEntity: ScolEnseignementUe::class, mappedBy: 'ue')]
    #[Groups(['maquette:detail'])]
    private Collection $enseignementUes;

    // todo: add coeff. ?

    public function __construct()
    {
        $this->enseignementUes = new ArrayCollection();
    }

    #[Groups(['maquette:detail', 'ue:read'])]
    public function getDisplayApc(): string
    {
        return $this->competence ? $this->libelle.' | '.$this->competence->getNomCourt() : $this->libelle;
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

    public function getCompetence(): ?ApcCompetence
    {
        return $this->competence;
    }

    public function setCompetence(?ApcCompetence $competence): static
    {
        $this->competence = $competence;

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
    public function getEnseignementUes(): Collection
    {
        return $this->enseignementUes;
    }

    public function addEnseignementUe(ScolEnseignementUe $enseignementUe): static
    {
        if (!$this->enseignementUes->contains($enseignementUe)) {
            $this->enseignementUes->add($enseignementUe);
            $enseignementUe->setUe($this);
        }

        return $this;
    }

    public function removeEnseignementUe(ScolEnseignementUe $enseignementUe): static
    {
        if ($this->enseignementUes->removeElement($enseignementUe)) {
            // set the owning side to null (unless already changed)
            if ($enseignementUe->getUe() === $this) {
                $enseignementUe->setUe(null);
            }
        }

        return $this;
    }
}
