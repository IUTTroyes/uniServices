<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Repository\Structure\StructureScolariteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureScolariteRepository::class)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['scolarite:read']]),
        new GetCollection(normalizationContext: ['groups' => ['scolarite:read']]),
        new GetCollection(
            uriTemplate: '/etudiant_scolarites/by_etudiant/{etudiantId}',
            uriVariables: [
                'etudiantId' => new Link(fromClass: Etudiant::class, identifiers: ['id'], toProperty: 'etudiant')
            ],
            paginationEnabled: false,
            normalizationContext: ['groups' => ['scolarite:read']]
        ),
    ]
)]
class EtudiantScolarite
{
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['scolarite:read', 'etudiant:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'etudiantScolarites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['scolarite:read'])]
    private ?Etudiant $etudiant = null;

    #[ORM\Column]
    #[Groups(['scolarite:read'])]
    private ?int $ordre = null;

    // prop. annuelle
    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['scolarite:read'])]
    private ?string $proposition = null;

    // switch rel. scol.semestre
    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite:read'])]
    private ?float $moyenne = null;

    #[ORM\Column]
    #[Groups(['scolarite:read'])]
    private int $nbAbsences = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['scolarite:read'])]
    private ?string $commentaire = null;

    #[ORM\Column]
    #[Groups(['scolarite:read'])]
    private bool $public = false;

    // switch rel. scol.semestre
    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite:read'])]
    private ?array $moyennesMatiere = null;

    // moyennes annuelles
    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite:read'])]
    private ?array $moyennesUe = null;

    // off
    #[ORM\Column]
    #[Groups(['scolarite:read'])]
    private bool $actif = false;

    #[ORM\ManyToOne(inversedBy: 'scolarites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['scolarite:read'])]
    private ?StructureAnneeUniversitaire $structureAnneeUniversitaire = null;

    // switch rel. scol.semestre
    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'scolarite', orphanRemoval: true)]
    private Collection $etudiantAbsences;

    // switch rel. scol.semestre
    /**
     * @var Collection<int, EtudiantNote>
     */
    #[ORM\OneToMany(targetEntity: EtudiantNote::class, mappedBy: 'scolarite')]
    private Collection $etudiantNotes;

    /**
     * @var Collection<int, StructureSemestre>
     */
    #[ORM\ManyToMany(targetEntity: StructureSemestre::class, inversedBy: 'etudiantScolarites')]
    #[Groups(['scolarite:read'])]
    private Collection $semestres;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, inversedBy: 'etudiantScolarites')]
    #[Groups(['scolarite:read'])]
    private Collection $groupes;

    #[ORM\ManyToOne(inversedBy: 'etudiantScolarites')]
    #[Groups(['scolarite:read'])]
    private ?StructureDepartement $departement = null;

    public function __construct()
    {
        $this->etudiantAbsences = new ArrayCollection();
        $this->etudiantNotes = new ArrayCollection();
        $this->semestres = new ArrayCollection();
        $this->groupes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre = 0): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getProposition(): ?string
    {
        return $this->proposition;
    }

    public function setProposition(?string $proposition): static
    {
        $this->proposition = $proposition;

        return $this;
    }

    public function getMoyenne(): ?float
    {
        return $this->moyenne;
    }

    public function setMoyenne(?float $moyenne): static
    {
        $this->moyenne = $moyenne;

        return $this;
    }

    public function getNbAbsences(): ?int
    {
        return $this->nbAbsences;
    }

    public function setNbAbsences(int $nbAbsences = 0): static
    {
        $this->nbAbsences = $nbAbsences;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getMoyennesMatiere(): ?array
    {
        return $this->moyennesMatiere;
    }

    public function setMoyennesMatiere(?array $moyennesMatiere): static
    {
        $this->moyennesMatiere = $moyennesMatiere;

        return $this;
    }

    public function getMoyennesUe(): ?array
    {
        return $this->moyennesUe;
    }

    public function setMoyennesUe(?array $moyennesUe): static
    {
        $this->moyennesUe = $moyennesUe;

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

    public function getStructureAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->structureAnneeUniversitaire;
    }

    public function setStructureAnneeUniversitaire(?StructureAnneeUniversitaire $structureAnneeUniversitaire): static
    {
        $this->structureAnneeUniversitaire = $structureAnneeUniversitaire;

        return $this;
    }

    /**
     * @return Collection<int, EtudiantAbsence>
     */
    public function getEtudiantAbsences(): Collection
    {
        return $this->etudiantAbsences;
    }

    public function addEtudiantAbsence(EtudiantAbsence $etudiantAbsence): static
    {
        if (!$this->etudiantAbsences->contains($etudiantAbsence)) {
            $this->etudiantAbsences->add($etudiantAbsence);
            $etudiantAbsence->setScolarite($this);
        }

        return $this;
    }

    public function removeEtudiantAbsence(EtudiantAbsence $etudiantAbsence): static
    {
        if ($this->etudiantAbsences->removeElement($etudiantAbsence)) {
            // set the owning side to null (unless already changed)
            if ($etudiantAbsence->getScolarite() === $this) {
                $etudiantAbsence->setScolarite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EtudiantNote>
     */
    public function getEtudiantNotes(): Collection
    {
        return $this->etudiantNotes;
    }

    public function addEtudiantNote(EtudiantNote $etudiantNote): static
    {
        if (!$this->etudiantNotes->contains($etudiantNote)) {
            $this->etudiantNotes->add($etudiantNote);
            $etudiantNote->setScolarite($this);
        }

        return $this;
    }

    public function removeEtudiantNote(EtudiantNote $etudiantNote): static
    {
        if ($this->etudiantNotes->removeElement($etudiantNote)) {
            // set the owning side to null (unless already changed)
            if ($etudiantNote->getScolarite() === $this) {
                $etudiantNote->setScolarite(null);
            }
        }

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
     * @return Collection<int, StructureGroupe>
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addGroupe(StructureGroupe $groupe): static
    {
        if (!$this->groupes->contains($groupe)) {
            $this->groupes->add($groupe);
        }

        return $this;
    }

    public function removeGroupe(StructureGroupe $groupe): static
    {
        $this->groupes->removeElement($groupe);

        return $this;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }
}
