<?php

namespace App\Entity\Apc;

use App\Entity\Structure\StructureAnnee;
use App\Repository\Apc\ApcNiveauRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ApcNiveauRepository::class)]
class ApcNiveau
{
    final public const NIVEAU_1 = 'Novice';
    final public const NIVEAU_2 = 'Intermédiaire';
    final public const NIVEAU_3 = 'Compétent';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?int $ordre = null;

    /**
     * @var Collection<int, ApcParcours>
     */
    #[ORM\ManyToMany(targetEntity: ApcParcours::class, inversedBy: 'niveaux')]
    #[Groups('diplome:read')]
    private Collection $parcours;

    #[ORM\ManyToOne(inversedBy: 'niveaux')]
    #[Groups('enseignement:detail')]
    private ?ApcCompetence $competence = null;

    /**
     * @var Collection<int, ApcApprentissageCritique>
     */
    #[ORM\OneToMany(targetEntity: ApcApprentissageCritique::class, mappedBy: 'niveau')]
    private Collection $apprentissageCritique;

    /**
     * @var Collection<int, StructureAnnee>
     */
    #[ORM\OneToMany(targetEntity: StructureAnnee::class, mappedBy: 'apcNiveau')]
    private Collection $annees;

    public function __construct()
    {
        $this->parcours = new ArrayCollection();
        $this->apprentissageCritique = new ArrayCollection();
        $this->annees = new ArrayCollection();
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

    /**
     * @return Collection<int, ApcParcours>
     */
    public function getParcours(): Collection
    {
        return $this->parcours;
    }

    public function addParcours(ApcParcours $parcours): static
    {
        if (!$this->parcours->contains($parcours)) {
            $this->parcours->add($parcours);
        }

        return $this;
    }

    public function removeParcours(ApcParcours $parcours): static
    {
        $this->parcours->removeElement($parcours);

        return $this;
    }

    public function getCompetence(): ?ApcCompetence
    {
        return $this->competence;
    }

    public function setCompetence(?ApcCompetence $competence): static
    {
        $this->competence = $competence;

        return $this;
    }

    /**
     * @return Collection<int, ApcApprentissageCritique>
     */
    public function getApprentissageCritique(): Collection
    {
        return $this->apprentissageCritique;
    }

    public function addApprentissageCritique(ApcApprentissageCritique $apprentissageCritique): static
    {
        if (!$this->apprentissageCritique->contains($apprentissageCritique)) {
            $this->apprentissageCritique->add($apprentissageCritique);
            $apprentissageCritique->setNiveau($this);
        }

        return $this;
    }

    public function removeApprentissageCritique(ApcApprentissageCritique $apprentissageCritique): static
    {
        if ($this->apprentissageCritique->removeElement($apprentissageCritique)) {
            // set the owning side to null (unless already changed)
            if ($apprentissageCritique->getNiveau() === $this) {
                $apprentissageCritique->setNiveau(null);
            }
        }

        return $this;
    }

    public function display(): string
    {
        $niv = match ($this->ordre) {
            1 => self::NIVEAU_1,
            2 => self::NIVEAU_2,
            3 => self::NIVEAU_3,
            default => null,
        };

        return $this->getCompetence()?->getNomCourt().' - Niveau '.$niv.'('.$this->ordre.')';
    }

    /**
     * @return Collection<int, StructureAnnee>
     */
    public function getAnnees(): Collection
    {
        return $this->annees;
    }

    public function addAnnee(StructureAnnee $annee): static
    {
        if (!$this->annees->contains($annee)) {
            $this->annees->add($annee);
            $annee->setNiveau($this);
        }

        return $this;
    }

    public function removeAnnee(StructureAnnee $annee): static
    {
        if ($this->annees->removeElement($annee)) {
            // set the owning side to null (unless already changed)
            if ($annee->getNiveau() === $this) {
                $annee->setNiveau(null);
            }
        }

        return $this;
    }
}
