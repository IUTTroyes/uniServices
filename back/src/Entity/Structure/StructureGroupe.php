<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Apc\ApcParcours;
use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Traits\ApogeeTrait;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\OldIdTrait;
use App\Repository\Structure\StructureGroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureGroupeRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['structure_diplome:read', 'structure_diplome:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['structure_diplome:read']]),
    ]
)]
class StructureGroupe
{
    use ApogeeTrait;
    use EduSignTrait;
    use OldIdTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['semestre:read', 'scolarite:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['semestre:read', 'scolarite:read'])]
    private string $libelle;

    #[ORM\Column(length: 10)]
    #[Groups(['semestre:read', 'scolarite:read'])]
    private string $type;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups(['semestre:read'])]
    private ?self $parent = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['semestre:read'])]
    private ?int $ordre = null;

    #[ORM\ManyToMany(targetEntity: StructureSemestre::class, inversedBy: 'structureGroupes')]
    private Collection $semestres;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    #[Groups(['semestre:read'])]
    private ?Collection $enfants;

    /**
     * @var Collection<int, ApcParcours>
     */
    #[ORM\ManyToMany(targetEntity: ApcParcours::class, mappedBy: 'groupes')]
    #[Groups(['semestre:read'])]
    private Collection $apcParcours;

    /**
     * @var Collection<int, EdtEvent>
     */
    #[ORM\OneToMany(targetEntity: EdtEvent::class, mappedBy: 'groupe')]
    #[Groups(['semestre:read'])]
    private Collection $scolEdtEvents;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\ManyToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'groupes')]
    private Collection $etudiantScolarites;

    public function __construct()
    {
        $this->semestres = new ArrayCollection();
        $this->enfants = new ArrayCollection();
        $this->apcParcours = new ArrayCollection();
        $this->scolEdtEvents = new ArrayCollection();
        $this->etudiantScolarites = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * @return Collection<int, StructureSemestre>
     */
    public function getSemestres(): Collection
    {
        return $this->semestres;
    }

    public function addSemestre(StructureSemestre $semestre): static
    {
        if (!$this->semestres->contains($semestre)) {
            $this->semestres->add($semestre);
        }

        return $this;
    }

    public function removeSemestre(StructureSemestre $semestre): static
    {
        $this->semestres->removeElement($semestre);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(?self $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant?->setParent($this);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            // set the owning side to null (unless already changed)
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApcParcours>
     */
    public function getApcParcours(): Collection
    {
        return $this->apcParcours;
    }

    public function addApcParcour(ApcParcours $apcParcour): static
    {
        if (!$this->apcParcours->contains($apcParcour)) {
            $this->apcParcours->add($apcParcour);
            $apcParcour->addGroupe($this);
        }

        return $this;
    }

    public function removeApcParcour(ApcParcours $apcParcour): static
    {
        if ($this->apcParcours->removeElement($apcParcour)) {
            $apcParcour->removeGroupe($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtEvent>
     */
    public function getScolEdtEvents(): Collection
    {
        return $this->scolEdtEvents;
    }

    public function addScolEdtEvent(EdtEvent $scolEdtEvent): static
    {
        if (!$this->scolEdtEvents->contains($scolEdtEvent)) {
            $this->scolEdtEvents->add($scolEdtEvent);
            $scolEdtEvent->setGroupe($this);
        }

        return $this;
    }

    public function removeScolEdtEvent(EdtEvent $scolEdtEvent): static
    {
        if ($this->scolEdtEvents->removeElement($scolEdtEvent)) {
            // set the owning side to null (unless already changed)
            if ($scolEdtEvent->getGroupe() === $this) {
                $scolEdtEvent->setGroupe(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EtudiantScolarite>
     */
    public function getEtudiantScolarites(): Collection
    {
        return $this->etudiantScolarites;
    }

    public function addEtudiantScolarite(EtudiantScolarite $etudiantScolarite): static
    {
        if (!$this->etudiantScolarites->contains($etudiantScolarite)) {
            $this->etudiantScolarites->add($etudiantScolarite);
            $etudiantScolarite->addGroupe($this);
        }

        return $this;
    }

    public function removeEtudiantScolarite(EtudiantScolarite $etudiantScolarite): static
    {
        if ($this->etudiantScolarites->removeElement($etudiantScolarite)) {
            $etudiantScolarite->removeGroupe($this);
        }

        return $this;
    }
}
