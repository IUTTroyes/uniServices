<?php

namespace App\Entity\Etudiant;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Filter\EtudiantScolariteFilter;
use App\Repository\Structure\StructureScolariteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureScolariteRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['scolarite:detail', 'etudiant:light', 'annee:light', 'annee-univ:light']]),
        new Get(
            uriTemplate: '/mini/etudiant_scolarites/{id}',
            normalizationContext: ['groups' => ['scolarite:light']],
        ),
        new Get(
            uriTemplate: '/maxi/etudiant_scolarites/{id}',
            normalizationContext: ['groups' => ['scolarite:datail']],
        ),
        new GetCollection(normalizationContext: ['groups' => ['scolarite:detail', 'etudiant:light', 'annee:light', 'annee-univ:light']]),
        new GetCollection(
            uriTemplate: '/mini/etudiant_scolarites',
            normalizationContext: ['groups' => ['scolarite:detail']],
        ),
        new GetCollection(
            uriTemplate: '/maxi/etudiant_scolarites',
            normalizationContext: ['groups' => ['scolarite:detail', 'etudiant:detail']],
        ),

        new Get(
            uriTemplate: '/etudiant_scolarites/etudiant/{etudiant}/structureAnneeUniversitaire/{structureAnneeUniversitaire}',
            normalizationContext: ['groups' => ['scolarite:detail']],
        )
    ],
)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
#[ApiFilter(EtudiantScolariteFilter::class)]
class EtudiantScolarite
{
    use UuidTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['scolarite:detail', 'scolarite:light'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'scolarites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['scolarite:detail'])]
    private ?Etudiant $etudiant = null;

    #[ORM\Column]
    #[Groups(['scolarite:detail', 'scolarite:light'])]
    private int $ordre = 1;

    /**
     * @deprecated
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite:detail'])]
    private ?float $moyenne = null;

    #[ORM\Column]
    #[Groups(['scolarite:detail'])]
    private int $nbAbsences = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['scolarite:detail'])]
    private ?string $commentaire = null;

    #[ORM\Column]
    #[Groups(['scolarite:detail', 'scolarite:light'])]
    private bool $public = false;

    /**
     * @deprecated
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite:detail'])]
    private ?array $moyennesMatiere = null;

    // moyennes annuelles
    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite:detail'])]
    private ?array $moyennesUe = null;

    #[ORM\ManyToOne(inversedBy: 'scolarites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['scolarite:detail', 'scolarite:light'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\ManyToOne(inversedBy: 'scolarites')]
    #[Groups(['scolarite:detail'])]
    private ?StructureDepartement $departement = null;

    /**
     * @var Collection<int, EtudiantScolariteSemestre>
     */
    #[ORM\OneToMany(targetEntity: EtudiantScolariteSemestre::class, mappedBy: 'scolarite')]
    #[Groups(['scolarite:detail'])]
    private Collection $scolariteSemestre;

    /**
     * @var Collection<int, StructureAnnee>
     * @deprecated
     */
    #[ORM\ManyToMany(targetEntity: StructureAnnee::class, inversedBy: 'scolarites')]
    #[Groups(['scolarite:detail'])]
    private Collection $annee;

    #[ORM\Column]
    #[Groups(['scolarite:detail', 'scolarite:light'])]
    private bool $actif = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['scolarite:detail'])]
    private ?bool $decision = null;

    #[ORM\ManyToOne(inversedBy: 'etudiantScolaritesPropositions')]
    private ?StructureAnnee $proposition = null;

    public function __construct()
    {
        $this->scolariteSemestre = new ArrayCollection();
        $this->annee = new ArrayCollection();
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

    public function setOrdre(int $ordre = 1): static
    {
        $this->ordre = $ordre;

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

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

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
        return $this->scolariteSemestre;
    }

    public function addScolariteSemestre(EtudiantScolariteSemestre $scolariteSemestre): static
    {
        if (!$this->scolariteSemestre->contains($scolariteSemestre)) {
            $this->scolariteSemestre->add($scolariteSemestre);
            $scolariteSemestre->setScolarite($this);
        }

        return $this;
    }

    public function removeScolariteSemestre(EtudiantScolariteSemestre $scolariteSemestre): static
    {
        if ($this->scolariteSemestre->removeElement($scolariteSemestre)) {
            // set the owning side to null (unless already changed)
            if ($scolariteSemestre->getScolarite() === $this) {
                $scolariteSemestre->setScolarite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructureAnnee>
     */
    public function getAnnee(): Collection
    {
        return $this->annee;
    }

    public function addAnnee(StructureAnnee $annee): static
    {
        if (!$this->annee->contains($annee)) {
            $this->annee->add($annee);
        }

        return $this;
    }

    public function removeAnnee(StructureAnnee $annee): static
    {
        $this->annee->removeElement($annee);

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): void
    {
        $this->actif = $actif;
    }

    public function getDecision(): ?bool
    {
        return $this->decision;
    }

    public function setDecision(?bool $decision): void
    {
        $this->decision = $decision;
    }

    public function getProposition(): ?StructureAnnee
    {
        return $this->proposition;
    }

    public function setProposition(?StructureAnnee $proposition): static
    {
        $this->proposition = $proposition;

        return $this;
    }
}
