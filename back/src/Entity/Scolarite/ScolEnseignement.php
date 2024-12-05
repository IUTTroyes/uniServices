<?php

namespace App\Entity\Scolarite;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Etudiant\EtudiantAbsence;
use App\Entity\Structure\StructureUe;
use App\Entity\Traits\ApogeeTrait;
use App\Repository\ScolEnseignementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[ORM\Entity(repositoryClass: ScolEnseignementRepository::class)]
class ScolEnseignement
{
    use ApogeeTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $libelle_court = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $preRequis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motsCles = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codeMatiere = null;

    #[ORM\Column]
    private ?bool $suspendu = null;

    #[ORM\Column(type: Types::JSON)]
    private array $heures = [];

    #[ORM\Column]
    private ?int $nbNotes = null;

    #[ORM\Column]
    private ?bool $mutualisee = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'scolEnseignements')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $scolEnseignements;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfant')]
    private ?self $scolEnseignement = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'scolEnseignement')]
    private Collection $enfant;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $livrables = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $exemple = null;

    #[ORM\Column]
    private ?bool $bonification = null;

    /**
     * @var Collection<int, StructureUe>
     */
    #[ORM\ManyToMany(targetEntity: StructureUe::class, inversedBy: 'scolEnseignements')]
    private Collection $ue;

    /**
     * @var Collection<int, ApcApprentissageCritique>
     */
    #[ORM\ManyToMany(targetEntity: ApcApprentissageCritique::class, inversedBy: 'scolEnseignements')]
    private Collection $apcApprentissageCritique;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'enseignement')]
    private Collection $etudiantAbsences;

    /**
     * @var Collection<int, ScolEvaluation>
     */
    #[ORM\OneToMany(targetEntity: ScolEvaluation::class, mappedBy: 'enseignement')]
    private Collection $scolEvaluations;

    /**
     * @var Collection<int, ScolEdtEvent>
     */
    #[ORM\OneToMany(targetEntity: ScolEdtEvent::class, mappedBy: 'enseignement')]
    private Collection $scolEdtEvents;

    public function __construct()
    {
        $this->scolEnseignements = new ArrayCollection();
        $this->enfant = new ArrayCollection();
        $this->ue = new ArrayCollection();
        $this->apcApprentissageCritique = new ArrayCollection();
        $this->etudiantAbsences = new ArrayCollection();
        $this->scolEvaluations = new ArrayCollection();
        $this->scolEdtEvents = new ArrayCollection();
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

    public function getLibelleCourt(): ?string
    {
        return $this->libelle_court;
    }

    public function setLibelleCourt(?string $libelle_court): static
    {
        $this->libelle_court = $libelle_court;

        return $this;
    }

    public function getPreRequis(): ?string
    {
        return $this->preRequis;
    }

    public function setPreRequis(?string $preRequis): static
    {
        $this->preRequis = $preRequis;

        return $this;
    }

    public function getObjectif(): ?string
    {
        return $this->objectif;
    }

    public function setObjectif(?string $objectif): static
    {
        $this->objectif = $objectif;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMotsCles(): ?string
    {
        return $this->motsCles;
    }

    public function setMotsCles(?string $motsCles): static
    {
        $this->motsCles = $motsCles;

        return $this;
    }

    public function getCodeMatiere(): ?string
    {
        return $this->codeMatiere;
    }

    public function setCodeMatiere(?string $codeMatiere): static
    {
        $this->codeMatiere = $codeMatiere;

        return $this;
    }

    public function isSuspendu(): ?bool
    {
        return $this->suspendu;
    }

    public function setSuspendu(bool $suspendu): static
    {
        $this->suspendu = $suspendu;

        return $this;
    }

    public function getHeures(): array
    {
        return $this->heures;
    }

    public function setHeures(array $heures): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->heures = $resolver->resolve($heures);

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'heures' => [
                'CM' => ['PN' => 0, 'IUT' => 0],
                'TD' => ['PN' => 0, 'IUT' => 0],
                'TP' => ['PN' => 0, 'IUT' => 0],
                'Projet' => ['PN' => 0, 'IUT' => 0],
            ],
        ]);

        $resolver->setAllowedTypes('heures', 'array');
    }

    public function getNbNotes(): ?int
    {
        return $this->nbNotes;
    }

    public function setNbNotes(int $nbNotes): static
    {
        $this->nbNotes = $nbNotes;

        return $this;
    }

    public function isMutualisee(): ?bool
    {
        return $this->mutualisee;
    }

    public function setMutualisee(bool $mutualisee): static
    {
        $this->mutualisee = $mutualisee;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getScolEnseignements(): Collection
    {
        return $this->scolEnseignements;
    }

    public function addScolEnseignement(self $scolEnseignement): static
    {
        if (!$this->scolEnseignements->contains($scolEnseignement)) {
            $this->scolEnseignements->add($scolEnseignement);
            $scolEnseignement->setParent($this);
        }

        return $this;
    }

    public function removeScolEnseignement(self $scolEnseignement): static
    {
        if ($this->scolEnseignements->removeElement($scolEnseignement)) {
            // set the owning side to null (unless already changed)
            if ($scolEnseignement->getParent() === $this) {
                $scolEnseignement->setParent(null);
            }
        }

        return $this;
    }

    public function getScolEnseignement(): ?self
    {
        return $this->scolEnseignement;
    }

    public function setScolEnseignement(?self $scolEnseignement): static
    {
        $this->scolEnseignement = $scolEnseignement;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getEnfant(): Collection
    {
        return $this->enfant;
    }

    public function addEnfant(self $enfant): static
    {
        if (!$this->enfant->contains($enfant)) {
            $this->enfant->add($enfant);
            $enfant->setScolEnseignement($this);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): static
    {
        if ($this->enfant->removeElement($enfant)) {
            // set the owning side to null (unless already changed)
            if ($enfant->getScolEnseignement() === $this) {
                $enfant->setScolEnseignement(null);
            }
        }

        return $this;
    }

    public function getLivrables(): ?string
    {
        return $this->livrables;
    }

    public function setLivrables(?string $livrables): static
    {
        $this->livrables = $livrables;

        return $this;
    }

    public function getExemple(): ?string
    {
        return $this->exemple;
    }

    public function setExemple(?string $exemple): static
    {
        $this->exemple = $exemple;

        return $this;
    }

    public function isBonification(): ?bool
    {
        return $this->bonification;
    }

    public function setBonification(bool $bonification): static
    {
        $this->bonification = $bonification;

        return $this;
    }

    /**
     * @return Collection<int, StructureUe>
     */
    public function getUe(): Collection
    {
        return $this->ue;
    }

    public function addUe(StructureUe $ue): static
    {
        if (!$this->ue->contains($ue)) {
            $this->ue->add($ue);
        }

        return $this;
    }

    public function removeUe(StructureUe $ue): static
    {
        $this->ue->removeElement($ue);

        return $this;
    }

    /**
     * @return Collection<int, ApcApprentissageCritique>
     */
    public function getApcApprentissageCritique(): Collection
    {
        return $this->apcApprentissageCritique;
    }

    public function addApcApprentissageCritique(ApcApprentissageCritique $apcApprentissageCritique): static
    {
        if (!$this->apcApprentissageCritique->contains($apcApprentissageCritique)) {
            $this->apcApprentissageCritique->add($apcApprentissageCritique);
        }

        return $this;
    }

    public function removeApcApprentissageCritique(ApcApprentissageCritique $apcApprentissageCritique): static
    {
        $this->apcApprentissageCritique->removeElement($apcApprentissageCritique);

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
            $etudiantAbsence->setEnseignement($this);
        }

        return $this;
    }

    public function removeEtudiantAbsence(EtudiantAbsence $etudiantAbsence): static
    {
        if ($this->etudiantAbsences->removeElement($etudiantAbsence)) {
            // set the owning side to null (unless already changed)
            if ($etudiantAbsence->getEnseignement() === $this) {
                $etudiantAbsence->setEnseignement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScolEvaluation>
     */
    public function getScolEvaluations(): Collection
    {
        return $this->scolEvaluations;
    }

    public function addScolEvaluation(ScolEvaluation $scolEvaluation): static
    {
        if (!$this->scolEvaluations->contains($scolEvaluation)) {
            $this->scolEvaluations->add($scolEvaluation);
            $scolEvaluation->setEnseignement($this);
        }

        return $this;
    }

    public function removeScolEvaluation(ScolEvaluation $scolEvaluation): static
    {
        if ($this->scolEvaluations->removeElement($scolEvaluation)) {
            // set the owning side to null (unless already changed)
            if ($scolEvaluation->getEnseignement() === $this) {
                $scolEvaluation->setEnseignement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScolEdtEvent>
     */
    public function getScolEdtEvents(): Collection
    {
        return $this->scolEdtEvents;
    }

    public function addScolEdtEvent(ScolEdtEvent $scolEdtEvent): static
    {
        if (!$this->scolEdtEvents->contains($scolEdtEvent)) {
            $this->scolEdtEvents->add($scolEdtEvent);
            $scolEdtEvent->setEnseignement($this);
        }

        return $this;
    }

    public function removeScolEdtEvent(ScolEdtEvent $scolEdtEvent): static
    {
        if ($this->scolEdtEvents->removeElement($scolEdtEvent)) {
            // set the owning side to null (unless already changed)
            if ($scolEdtEvent->getEnseignement() === $this) {
                $scolEdtEvent->setEnseignement(null);
            }
        }

        return $this;
    }
}
