<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\QueryParameter;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Filter\EtudiantFilter;
use App\Repository\Structure\StructureScolariteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureScolariteRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['scolarite:read']]),
        new GetCollection(normalizationContext: ['groups' => ['scolarite:read']]),
        new Get(
            normalizationContext: ['groups' => ['scolarite:read']],
            uriTemplate: '/etudiant_scolarites/etudiant/{etudiant}/structureAnneeUniversitaire/{structureAnneeUniversitaire}',
        )
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

    #[ORM\ManyToOne(inversedBy: 'scolarites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['scolarite:read', 'etudiant:read'])]
    private ?StructureAnneeUniversitaire $structureAnneeUniversitaire = null;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, inversedBy: 'etudiantScolarites')]
    #[Groups(['scolarite:read'])]
    private Collection $groupes;

    #[ORM\ManyToOne(inversedBy: 'etudiantScolarites')]
    #[Groups(['scolarite:read'])]
    private ?StructureDepartement $departement = null;

    /**
     * @var Collection<int, EtudiantScolariteSemestre>
     */
    #[ORM\OneToMany(targetEntity: EtudiantScolariteSemestre::class, mappedBy: 'etudiantScolarite')]
    #[Groups(['scolarite:read', 'etudiant:read'])]
    private Collection $scolarite_semestre;

    public function __construct()
    {
        $this->groupes = new ArrayCollection();
        $this->scolarite_semestre = new ArrayCollection();
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

    /**
     * @return Collection<int, EtudiantScolariteSemestre>
     */
    public function getScolariteSemestre(): Collection
    {
        return $this->scolarite_semestre;
    }

    public function addScolariteSemestre(EtudiantScolariteSemestre $scolariteSemestre): static
    {
        if (!$this->scolarite_semestre->contains($scolariteSemestre)) {
            $this->scolarite_semestre->add($scolariteSemestre);
            $scolariteSemestre->setEtudiantScolarite($this);
        }

        return $this;
    }

    public function removeScolariteSemestre(EtudiantScolariteSemestre $scolariteSemestre): static
    {
        if ($this->scolarite_semestre->removeElement($scolariteSemestre)) {
            // set the owning side to null (unless already changed)
            if ($scolariteSemestre->getEtudiantScolarite() === $this) {
                $scolariteSemestre->setEtudiantScolarite(null);
            }
        }

        return $this;
    }
}
