<?php

namespace App\Entity\Structure;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
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
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
#[ApiFilter(AnneeFilter::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['structure_annee:read']]),
        new GetCollection(normalizationContext: ['groups' => ['structure_annee:read']]),
        new GetCollection(
            uriTemplate: '/annees-par-diplome/{diplomeId}',
            uriVariables: [
                'diplomeId' => new Link(fromClass: StructureDiplome::class, identifiers: ['id'], toProperty: 'diplome')
            ],
            normalizationContext: ['groups' => ['structure_annee:read']]
        )
    ]
)]
#[ORM\HasLifecycleCallbacks()]
class StructureAnnee
{
    use LifeCycleTrait;
    use OptionTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['structure_diplome:read:full', 'structure_diplome:read', 'etudiant:read', 'structure_annee:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['structure_diplome:read:full', 'structure_diplome:read', 'scolarite:read', 'semestre:read', 'structure_pn:read', 'structure_annee:read', 'etudiant:read'])]
    private ?string $libelle = null;

    #[ORM\Column]
    #[Groups(['structure_diplome:read:full'])]
    private int $ordre = 0;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['structure_diplome:read'])]
    private ?string $libelleLong = null;

    #[ORM\Column]
    #[Groups(['structure_annee:read'])]
    private bool $actif = true;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $couleur = null;

    /**
     * @var Collection<int, StructureSemestre>
     */
    #[ORM\OneToMany(targetEntity: StructureSemestre::class, mappedBy: 'annee')]
    #[Groups(['structure_diplome:read:full', 'structure_diplome:read', 'structure_pn:read'])]
    private Collection $structureSemestres;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['structure_pn:read'])]
    private ?string $apogeeCodeVersion = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['structure_pn:read'])]
    private ?string $apogeeCodeEtape = null;

    #[ORM\ManyToOne(inversedBy: 'annees')]
    private ?ApcNiveau $apcNiveau = null;

    #[ORM\ManyToOne(inversedBy: 'structureAnnees')]
    private ?StructurePn $pn = null;

    #[ORM\ManyToOne(inversedBy: 'annees')]
    private ?StructureDiplome $structureDiplome = null;

    /**
     * @var Collection<int, EtudiantScolarite>
     */
    #[ORM\ManyToMany(targetEntity: EtudiantScolarite::class, mappedBy: 'structure_annee')]
    private Collection $etudiantScolarites;

    public function __construct()
    {
        $this->structureSemestres = new ArrayCollection();
        $this->setOpt([]);
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
    public function getStructureSemestres(): Collection
    {
        return $this->structureSemestres;
    }

    public function addStructureSemestre(StructureSemestre $structureSemestre): static
    {
        if (!$this->structureSemestres->contains($structureSemestre)) {
            $this->structureSemestres->add($structureSemestre);
            $structureSemestre->setAnnee($this);
        }

        return $this;
    }

    public function removeStructureSemestre(StructureSemestre $structureSemestre): static
    {
        if ($this->structureSemestres->removeElement($structureSemestre)) {
            // set the owning side to null (unless already changed)
            if ($structureSemestre->getAnnee() === $this) {
                $structureSemestre->setAnnee(null);
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

    public function getApcNiveau(): ?ApcNiveau
    {
        return $this->apcNiveau;
    }

    public function setApcNiveau(?ApcNiveau $apcNiveau): static
    {
        $this->apcNiveau = $apcNiveau;

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

    public function getStructureDiplome(): ?StructureDiplome
    {
        return $this->structureDiplome;
    }

    public function setStructureDiplome(?StructureDiplome $structureDiplome): static
    {
        $this->structureDiplome = $structureDiplome;

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
            $etudiantScolarite->addStructureAnnee($this);
        }

        return $this;
    }

    public function removeEtudiantScolarite(EtudiantScolarite $etudiantScolarite): static
    {
        if ($this->etudiantScolarites->removeElement($etudiantScolarite)) {
            $etudiantScolarite->removeStructureAnnee($this);
        }

        return $this;
    }
}
