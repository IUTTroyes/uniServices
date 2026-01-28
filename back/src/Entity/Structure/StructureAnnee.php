<?php

namespace App\Entity\Structure;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OptionTrait;
use App\Filter\AnneeFilter;
use App\Repository\Structure\StructureAnneeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StructureAnneeRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['annee:read']]),
        new GetCollection(normalizationContext: ['groups' => ['annee:read']]),
        new Delete(),
    ]
)]
#[ApiFilter(AnneeFilter::class)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
#[ORM\HasLifecycleCallbacks()]
class StructureAnnee
{
    use LifeCycleTrait;
    use OptionTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['maquette:detail', 'annee:light', 'annee:read', 'semestre:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['maquette:detail', 'scolarite:read', 'annee:read', 'annee:light', 'semestre:detail'])]
    private ?string $libelle = null;

    #[ORM\Column]
    #[Groups(['maquette:detail'])]
    private int $ordre = 0;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['diplome:read'])]
    private ?string $libelleLong = null;

    #[ORM\Column]
    #[Groups(['annee:read'])]
    private bool $actif = true;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $couleur = null;

    /**
     * @var Collection<int, StructureSemestre>
     */
    #[ORM\OneToMany(targetEntity: StructureSemestre::class, mappedBy: 'annee', orphanRemoval: true, cascade: ['remove'])]
    #[Groups(['maquette:detail', 'annee:read'])]
    private Collection $semestres;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['annee:read', 'maquette:detail'])]
    private ?string $apogeeCodeVersion = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['annee:read', 'maquette:detail'])]
    private ?string $apogeeCodeEtape = null;

    // todo: inverser car 1 annee a N niveaux
    #[ORM\ManyToOne(inversedBy: 'annees')]
    private ?ApcNiveau $niveau = null;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\ManyToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'annee')]
    private Collection $scolarites;

    #[ORM\ManyToOne(inversedBy: 'annees')]
    private ?StructurePn $pn = null;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\OneToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'proposition')]
    private Collection $etudiantScolaritesPropositions;

    public function __construct()
    {
        $this->semestres = new ArrayCollection();
        $this->setOpt([]);
        $this->scolarites = new ArrayCollection();
        $this->etudiantScolaritesPropositions = new ArrayCollection();
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

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getLibelleLong(): ?string
    {
        return $this->libelleLong;
    }

    public function setLibelleLong(?string $libelleLong): static
    {
        $this->libelleLong = $libelleLong;

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

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;

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
            $semestre->setAnnee($this);
        }

        return $this;
    }

    public function removeSemestre(StructureSemestre $semestre): static
    {
        if ($this->semestres->removeElement($semestre)) {
            // set the owning side to null (unless already changed)
            if ($semestre->getAnnee() === $this) {
                $semestre->setAnnee(null);
            }
        }

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'alternance' => false,
        ]);

        $resolver->setAllowedTypes('alternance', 'bool');
    }

    public function getApogeeCodeVersion(): ?string
    {
        return $this->apogeeCodeVersion;
    }

    public function setApogeeCodeVersion(?string $apogeeCodeVersion): static
    {
        $this->apogeeCodeVersion = $apogeeCodeVersion;

        return $this;
    }

    public function getApogeeCodeEtape(): ?string
    {
        return $this->apogeeCodeEtape;
    }

    public function setApogeeCodeEtape(?string $apogeeCodeEtape): static
    {
        $this->apogeeCodeEtape = $apogeeCodeEtape;

        return $this;
    }

    public function getNiveau(): ?ApcNiveau
    {
        return $this->niveau;
    }

    public function setNiveau(?ApcNiveau $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * @return Collection<int, EtudiantScolarite>
     */
    public function getScolarites(): Collection
    {
        return $this->scolarites;
    }

    public function addScolarite(EtudiantScolarite $scolarite): static
    {
        if (!$this->scolarites->contains($scolarite)) {
            $this->scolarites->add($scolarite);
            $scolarite->addAnnee($this);
        }

        return $this;
    }

    public function removeScolarite(EtudiantScolarite $scolarite): static
    {
        if ($this->scolarites->removeElement($scolarite)) {
            $scolarite->removeAnnee($this);
        }

        return $this;
    }

    public function getPn(): ?StructurePn
    {
        return $this->pn;
    }

    public function setPn(?StructurePn $pn): static
    {
        $this->pn = $pn;

        return $this;
    }

    public function getDiplome(): ?StructureDiplome
    {
        return $this->getPn()?->getDiplome();
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setSemestres(Collection $semestres): void
    {
        $this->semestres = $semestres;
    }

    public function setScolarites(Collection $scolarites): void
    {
        $this->scolarites = $scolarites;
    }

    public function setEtudiantScolaritesPropositions(Collection $etudiantScolaritesPropositions): void
    {
        $this->etudiantScolaritesPropositions = $etudiantScolaritesPropositions;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->getDiplome()?->getDepartement();
    }

    /**
     * @return Collection<int, EtudiantScolarite>
     */
    public function getEtudiantScolaritesPropositions(): Collection
    {
        return $this->etudiantScolaritesPropositions;
    }

    public function addEtudiantScolaritesProposition(EtudiantScolarite $etudiantScolaritesProposition): static
    {
        if (!$this->etudiantScolaritesPropositions->contains($etudiantScolaritesProposition)) {
            $this->etudiantScolaritesPropositions->add($etudiantScolaritesProposition);
            $etudiantScolaritesProposition->setProposition($this);
        }

        return $this;
    }

    public function removeEtudiantScolaritesProposition(EtudiantScolarite $etudiantScolaritesProposition): static
    {
        if ($this->etudiantScolaritesPropositions->removeElement($etudiantScolaritesProposition)) {
            // set the owning side to null (unless already changed)
            if ($etudiantScolaritesProposition->getProposition() === $this) {
                $etudiantScolaritesProposition->setProposition(null);
            }
        }

        return $this;
    }
}
