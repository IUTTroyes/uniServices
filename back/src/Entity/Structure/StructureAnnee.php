<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\ApcNiveau;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OptionTrait;
use App\Repository\StructureAnneeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[ORM\Entity(repositoryClass: StructureAnneeRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['annee:read']]),
        new GetCollection(normalizationContext: ['groups' => ['annee:read']]),
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
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private int $ordre = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelleLong = null;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $couleur = null;

    /**
     * @var Collection<int, StructurePn>
     */
    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'structureAnnee')]
    private Collection $pn;

    /**
     * @var Collection<int, StructureSemestre>
     */
    #[ORM\OneToMany(targetEntity: StructureSemestre::class, mappedBy: 'annee')]
    private Collection $structureSemestres;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $apogeeCodeVersion = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $apogeeCodeEtape = null;

    /**
     * @var Collection<int, ApcNiveau>
     */
    #[ORM\OneToMany(targetEntity: ApcNiveau::class, mappedBy: 'annee')]
    private Collection $apcNiveaux;

    public function __construct()
    {
        $this->pn = new ArrayCollection();
        $this->structureSemestres = new ArrayCollection();
        $this->setOpt([]);
        $this->apcNiveaux = new ArrayCollection();
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
     * @return Collection<int, StructurePn>
     */
    public function getPn(): Collection
    {
        return $this->pn;
    }

    public function addPn(StructurePn $pn): static
    {
        if (!$this->pn->contains($pn)) {
            $this->pn->add($pn);
            $pn->setStructureAnnee($this);
        }

        return $this;
    }

    public function removePn(StructurePn $pn): static
    {
        if ($this->pn->removeElement($pn)) {
            // set the owning side to null (unless already changed)
            if ($pn->getStructureAnnee() === $this) {
                $pn->setStructureAnnee(null);
            }
        }

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
            'alternance' => true,
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

    /**
     * @return Collection<int, ApcNiveau>
     */
    public function getApcNiveaux(): Collection
    {
        return $this->apcNiveaux;
    }

    public function addApcNiveau(ApcNiveau $apcNiveau): static
    {
        if (!$this->apcNiveaux->contains($apcNiveau)) {
            $this->apcNiveaux->add($apcNiveau);
            $apcNiveau->setAnnee($this);
        }

        return $this;
    }

    public function removeApcNiveau(ApcNiveau $apcNiveau): static
    {
        if ($this->apcNiveaux->removeElement($apcNiveau)) {
            // set the owning side to null (unless already changed)
            if ($apcNiveau->getAnnee() === $this) {
                $apcNiveau->setAnnee(null);
            }
        }

        return $this;
    }
}
