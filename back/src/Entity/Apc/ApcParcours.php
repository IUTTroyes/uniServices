<?php

namespace App\Entity\Apc;

use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Traits\OptionTrait;
use App\Repository\ApcParcoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[ORM\Entity(repositoryClass: ApcParcoursRepository::class)]
class ApcParcours
{
    use OptionTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $sigle = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleur = null;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'apcParcours')]
    private Collection $diplome;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, inversedBy: 'apcParcours')]
    private Collection $groupes;

    /**
     * @var Collection<int, ApcNiveau>
     */
    #[ORM\ManyToMany(targetEntity: ApcNiveau::class, mappedBy: 'apcParcours')]
    private Collection $apcNiveaux;

    #[ORM\ManyToOne(inversedBy: 'apcParcours')]
    private ?ApcReferentiel $apcReferentiel = null;

    public function __construct()
    {
        $this->diplome = new ArrayCollection();
        $this->groupes = new ArrayCollection();
        $this->apcNiveaux = new ArrayCollection();
        $this->setOpt([]);
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

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(?string $sigle): static
    {
        $this->sigle = $sigle;

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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'formation_continue' => false,
        ]);

        $resolver->setAllowedTypes('formation_continue', 'bool');
    }

    /**
     * @return Collection<int, StructureDiplome>
     */
    public function getDiplome(): Collection
    {
        return $this->diplome;
    }

    public function addDiplome(StructureDiplome $diplome): static
    {
        if (!$this->diplome->contains($diplome)) {
            $this->diplome->add($diplome);
            $diplome->setApcParcours($this);
        }

        return $this;
    }

    public function removeDiplome(StructureDiplome $diplome): static
    {
        if ($this->diplome->removeElement($diplome)) {
            // set the owning side to null (unless already changed)
            if ($diplome->getApcParcours() === $this) {
                $diplome->setApcParcours(null);
            }
        }

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
            $apcNiveau->addApcParcour($this);
        }

        return $this;
    }

    public function removeApcNiveau(ApcNiveau $apcNiveau): static
    {
        if ($this->apcNiveaux->removeElement($apcNiveau)) {
            $apcNiveau->removeApcParcour($this);
        }

        return $this;
    }

    public function getApcReferentiel(): ?ApcReferentiel
    {
        return $this->apcReferentiel;
    }

    public function setApcReferentiel(?ApcReferentiel $apcReferentiel): static
    {
        $this->apcReferentiel = $apcReferentiel;

        return $this;
    }
}
