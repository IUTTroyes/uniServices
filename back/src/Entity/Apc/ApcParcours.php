<?php

namespace App\Entity\Apc;

use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Traits\OptionTrait;
use App\Repository\Apc\ApcParcoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ApcParcoursRepository::class)]
class ApcParcours
{
    use OptionTrait;
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['maquette:detail'])]
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
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'parcours')]
    private Collection $diplome;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, inversedBy: 'parcours')]
    private Collection $groupes;

    /**
     * @var Collection<int, ApcNiveau>
     */
    #[ORM\ManyToMany(targetEntity: ApcNiveau::class, mappedBy: 'parcours')]
    private Collection $niveaux;

    public function __construct()
    {
        $this->diplome = new ArrayCollection();
        $this->groupes = new ArrayCollection();
        $this->niveaux = new ArrayCollection();
        $this->setOpt([]);
    }

    #[Groups(['maquette:detail'])]
    public function getDisplay(): string
    {
        // si il ya formation_continue:true dans la propriété option
        // on affiche le libellé du parcours suivi de (FC)
        // sinon on affiche le libellé du parcours
        if ($this->opt && $this->opt['formation_continue']) {
            return $this->libelle . ' (FC)';
        } else {
            return $this->libelle . ' (FI)';
        }
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
            $diplome->setParcourss($this);
        }

        return $this;
    }

    public function removeDiplome(StructureDiplome $diplome): static
    {
        if ($this->diplome->removeElement($diplome)) {
            // set the owning side to null (unless already changed)
            if ($diplome->getParcourss() === $this) {
                $diplome->setParcourss(null);
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
    public function getNiveaux(): Collection
    {
        return $this->niveaux;
    }

    public function addNiveau(ApcNiveau $niveau): static
    {
        if (!$this->niveaux->contains($niveau)) {
            $this->niveaux->add($niveau);
            $niveau->addParcours($this);
        }

        return $this;
    }

    public function removeNiveau(ApcNiveau $niveau): static
    {
        if ($this->niveaux->removeElement($niveau)) {
            $niveau->removeParcours($this);
        }

        return $this;
    }
}
