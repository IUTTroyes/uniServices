<?php

namespace App\Entity\Apc;

use App\Entity\Structure\StructureUe;
use App\Entity\Traits\OldIdTrait;
use App\Repository\Apc\ApcCompetenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ApcCompetenceRepository::class)]
class ApcCompetence
{
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['diplome:read', 'enseignement:read', 'pn:read'])]
    private ?string $nomCourt = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['diplome:read', 'enseignement:read', 'pn:read'])]
    private ?string $couleur = null;

    #[ORM\ManyToOne(inversedBy: 'competences')]
    private ?ApcReferentiel $referentiel = null;

    /**
     * @var Collection<int, ApcNiveau>
     */
    #[ORM\OneToMany(targetEntity: ApcNiveau::class, mappedBy: 'competence')]
    private Collection $niveaux;

    #[ORM\Column]
    private array $composantesEssentielles = [];

    #[ORM\Column]
    private array $situationsProfessionnelles = [];

    /**
     * @var Collection<int, StructureUe>
     */
    #[ORM\OneToMany(targetEntity: StructureUe::class, mappedBy: 'competence')]
    private Collection $ues;

    public function __construct()
    {
        $this->niveaux = new ArrayCollection();
        $this->ues = new ArrayCollection();
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

    public function getNomCourt(): ?string
    {
        return $this->nomCourt;
    }

    public function setNomCourt(?string $nomCourt): static
    {
        $this->nomCourt = $nomCourt;

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

    public function getReferentiel(): ?ApcReferentiel
    {
        return $this->referentiel;
    }

    public function setReferentiel(?ApcReferentiel $referentiel): static
    {
        $this->referentiel = $referentiel;

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
            $niveau->setCompetence($this);
        }

        return $this;
    }

    public function removeNiveau(ApcNiveau $niveau): static
    {
        if ($this->niveaux->removeElement($niveau)) {
            // set the owning side to null (unless already changed)
            if ($niveau->getCompetence() === $this) {
                $niveau->setCompetence(null);
            }
        }

        return $this;
    }

    public function getComposantesEssentielles(): array
    {
        return $this->composantesEssentielles;
    }

    public function setComposantesEssentielles(array $composantesEssentielles): static
    {
        $this->composantesEssentielles = $composantesEssentielles;

        return $this;
    }

    public function getSituationsProfessionnelles(): array
    {
        return $this->situationsProfessionnelles;
    }

    public function setSituationsProfessionnelles(array $situationsProfessionnelles): static
    {
        $this->situationsProfessionnelles = $situationsProfessionnelles;

        return $this;
    }

    /**
     * @return Collection<int, StructureUe>
     */
    public function getUes(): Collection
    {
        return $this->ues;
    }

    public function addUe(StructureUe $ue): static
    {
        if (!$this->ues->contains($ue)) {
            $this->ues->add($ue);
            $ue->setCompetence($this);
        }

        return $this;
    }

    public function removeUe(StructureUe $ue): static
    {
        if ($this->ues->removeElement($ue)) {
            // set the owning side to null (unless already changed)
            if ($ue->getCompetence() === $this) {
                $ue->setCompetence(null);
            }
        }

        return $this;
    }
}
