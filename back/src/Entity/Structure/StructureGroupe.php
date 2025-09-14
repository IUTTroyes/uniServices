<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\Structure\GroupesParSemestreController;
use App\Entity\Apc\ApcParcours;
use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Traits\ApogeeTrait;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Users\Etudiant;
use App\Repository\Structure\StructureGroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureGroupeRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['diplome:read', 'diplome:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['diplome:read']]),
        new GetCollection(
            uriTemplate: '/structure_groupes/semestre/{semestreId}',
            controller: GroupesParSemestreController::class,
            normalizationContext: ['groups' => ['semestre:read']],
            read: false,
            name: 'groupes_par_semestre'
        ),
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
    #[Groups(['semestre:read', 'scolarite:read', 'edt_event:read:agenda'])]
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

    #[ORM\ManyToMany(targetEntity: StructureSemestre::class, inversedBy: 'groupes')]
    private Collection $semestres;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    #[Groups(['semestre:read'])]
    private ?Collection $enfants;

    /**
     * @var Collection<int, ApcParcours>
     */
    #[ORM\ManyToMany(targetEntity: ApcParcours::class, mappedBy: 'groupes')]
    #[Groups(['semestre:read'])]
    private Collection $parcours;

    /**
     * @var Collection<int, EdtEvent>
     */
    #[ORM\OneToMany(targetEntity: EdtEvent::class, mappedBy: 'groupe')]
    #[Groups(['semestre:read'])]
    private Collection $edtEvents;

    /**
     * @var Collection<int, EtudiantScolariteSemestre>
     */
    #[ORM\ManyToMany(targetEntity: EtudiantScolariteSemestre::class, mappedBy: 'groupes')]
    private Collection $scolariteSemestres;

    /**
     * @var Collection<int, Etudiant>
     */
    #[ORM\ManyToMany(targetEntity: Etudiant::class, mappedBy: 'groupes')]
    #[Groups(['scolarite:read', 'edt_event:read:agenda'])]
    private Collection $etudiants;

    public function __construct()
    {
        $this->semestres = new ArrayCollection();
        $this->enfants = new ArrayCollection();
        $this->parcours = new ArrayCollection();
        $this->edtEvents = new ArrayCollection();
        $this->scolariteSemestres = new ArrayCollection();
        $this->etudiants = new ArrayCollection();
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
    public function getParcours(): Collection
    {
        return $this->parcours;
    }

    public function addParcour(ApcParcours $parcour): static
    {
        if (!$this->parcours->contains($parcour)) {
            $this->parcours->add($parcour);
            $parcour->addGroupe($this);
        }

        return $this;
    }

    public function removeParcour(ApcParcours $parcour): static
    {
        if ($this->parcours->removeElement($parcour)) {
            $parcour->removeGroupe($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtEvent>
     */
    public function getEdtEvents(): Collection
    {
        return $this->edtEvents;
    }

    public function addEdtEvent(EdtEvent $edtEvent): static
    {
        if (!$this->edtEvents->contains($edtEvent)) {
            $this->edtEvents->add($edtEvent);
            $edtEvent->setGroupe($this);
        }

        return $this;
    }

    public function removeEdtEvent(EdtEvent $edtEvent): static
    {
        if ($this->edtEvents->removeElement($edtEvent)) {
            // set the owning side to null (unless already changed)
            if ($edtEvent->getGroupe() === $this) {
                $edtEvent->setGroupe(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EtudiantScolariteSemestre>
     */
    public function getScolariteSemestres(): Collection
    {
        return $this->scolariteSemestres;
    }

    public function addScolariteSemestre(EtudiantScolariteSemestre $scolariteSemestre): static
    {
        if (!$this->scolariteSemestres->contains($scolariteSemestre)) {
            $this->scolariteSemestres->add($scolariteSemestre);
            $scolariteSemestre->addGroupe($this);
        }

        return $this;
    }

    public function removeScolariteSemestre(EtudiantScolariteSemestre $scolariteSemestre): static
    {
        if ($this->scolariteSemestres->removeElement($scolariteSemestre)) {
            $scolariteSemestre->removeGroupe($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Etudiant>
     */
    public function getEtudiants(): Collection
    {
        return $this->etudiants;
    }

    public function addEtudiant(Etudiant $etudiant): static
    {
        if (!$this->etudiants->contains($etudiant)) {
            $this->etudiants->add($etudiant);
            $etudiant->addGroupe($this);
        }

        return $this;
    }

    public function removeEtudiant(Etudiant $etudiant): static
    {
        if ($this->etudiants->removeElement($etudiant)) {
            $etudiant->removeGroupe($this);
        }

        return $this;
    }
}
