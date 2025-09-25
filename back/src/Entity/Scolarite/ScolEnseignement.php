<?php

namespace App\Entity\Scolarite;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantAbsence;
use App\Entity\Previsionnel\Previsionnel;
use App\Entity\Traits\ApogeeTrait;
use App\Entity\Traits\OldIdTrait;
use App\Enum\TypeEnseignementEnum;
use App\Filter\EnseignementFilter;
use App\Repository\ScolEnseignementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ScolEnseignementRepository::class)]
#[ApiFilter(EnseignementFilter::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['enseignement:read', 'enseignement_ue:read']],
)]
class ScolEnseignement
{
    use ApogeeTrait;
    use OldIdTrait;

    public const MAJORATION_CM = 1.5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['maquette:detail', 'enseignement:read', 'enseignement_ue:read', 'previsionnel_personnel:read', 'edt_event:read:agenda'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['maquette:detail', 'previsionnel:read', 'enseignement:read', 'previsionnel_semestre:read', 'previsionnel_personnel:read', 'enseignement_ue:read'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 25, nullable: true)]
    #[Groups(['maquette:detail', 'previsionnel:read', 'enseignement:read'])]
    private ?string $libelle_court = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['enseignement:read'])]
    private ?string $preRequis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['enseignement:read'])]
    private ?string $objectif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['enseignement:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['enseignement:read'])]
    private ?string $motsCles = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['maquette:detail', 'enseignement:read', 'previsionnel:read', 'previsionnel_semestre:read', 'previsionnel_personnel:read', 'enseignement_ue:read'])]
    private ?string $codeEnseignement = null;

    #[ORM\Column]
    #[Groups(['enseignement:read'])]
    private ?bool $suspendu = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['previsionnel:read', 'enseignement:read', 'previsionnel_semestre:read'])]
    private array $heures = [];

    #[ORM\Column(type: 'string', enumType: TypeEnseignementEnum::class)]
    #[Groups(['maquette:detail', 'previsionnel:read', 'enseignement:read', 'enseignement_ue:read', 'edt_event:read:agenda'])]
    private TypeEnseignementEnum $type = TypeEnseignementEnum::TYPE_RESSOURCE;

    #[ORM\Column]
    #[Groups(['enseignement:read'])]
    private ?int $nbNotes = null;

    #[ORM\Column]
    private ?bool $mutualisee = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[Groups(['maquette:detail', 'enseignement_ue:read', 'enseignement:read'])]
    private Collection $enfants;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[Groups(['maquette:detail', 'enseignement_ue:read', 'enseignement:read'])]
    private ?self $parent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['enseignement_ue:read'])]
    private ?string $livrables = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $exemple = null;

    #[ORM\Column]
    #[Groups(['enseignement:read'])]
    private ?bool $bonification = null;

    /**
     * @var Collection<int, ApcApprentissageCritique>
     */
    #[ORM\ManyToMany(targetEntity: ApcApprentissageCritique::class, inversedBy: 'enseignements')]
    #[Groups(['enseignement:read'])]
    private Collection $apprentissageCritique;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'enseignement')]
    private Collection $absences;

    /**
     * @var Collection<int, ScolEvaluation>
     */
    #[ORM\OneToMany(targetEntity: ScolEvaluation::class, mappedBy: 'enseignement')]
    private Collection $evaluations;

    /**
     * @var Collection<int, EdtEvent>
     */
    #[ORM\OneToMany(targetEntity: EdtEvent::class, mappedBy: 'enseignement')]
    private Collection $edtEvents;

    /**
     * @var Collection<int, ScolEnseignementUe>
     */
    #[ORM\OneToMany(targetEntity: ScolEnseignementUe::class, mappedBy: 'enseignement', cascade: ['persist', 'remove'])]
    #[Groups(['enseignement:read'])]
    private Collection $enseignementUes;

    /**
     * @var Collection<int, Previsionnel>
     */
    #[ORM\OneToMany(targetEntity: Previsionnel::class, mappedBy: 'enseignement')]
    #[Groups(['enseignement:read', 'edt_event:read:agenda'])]
    private Collection $previsionnels;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
        $this->apprentissageCritique = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->edtEvents = new ArrayCollection();
        $this->enseignementUes = new ArrayCollection();
        $this->previsionnels = new ArrayCollection();
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

    public function getDisplay(): string
    {
        return $this->codeEnseignement.' - '.$this->libelle;
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

    public function getCodeEnseignement(): ?string
    {
        return $this->codeEnseignement;
    }

    public function setCodeEnseignement(?string $codeEnseignement): static
    {
        $this->codeEnseignement = $codeEnseignement;

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
                'CM' => ['PN' => 0, 'IUT' => 0],
                'TD' => ['PN' => 0, 'IUT' => 0],
                'TP' => ['PN' => 0, 'IUT' => 0],
                'Projet' => ['PN' => 0, 'IUT' => 0],
        ]);

        $resolver->setAllowedTypes('CM', 'array');
        $resolver->setAllowedTypes('TD', 'array');
        $resolver->setAllowedTypes('TP', 'array');
        $resolver->setAllowedTypes('Projet', 'array');
    }

    public function getType(): TypeEnseignementEnum
    {
        return $this->type;
    }

    public function setType(TypeEnseignementEnum $type): static
    {
        $this->type = $type;

        return $this;
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
        }

        return $this;
    }

    public function removeApprentissageCritique(ApcApprentissageCritique $apprentissageCritique): static
    {
        $this->apprentissageCritique->removeElement($apprentissageCritique);

        return $this;
    }

    /**
     * @return Collection<int, EtudiantAbsence>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(EtudiantAbsence $absence): static
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setEnseignement($this);
        }

        return $this;
    }

    public function removeAbsence(EtudiantAbsence $absence): static
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getEnseignement() === $this) {
                $absence->setEnseignement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScolEvaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(ScolEvaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setEnseignement($this);
        }

        return $this;
    }

    public function removeEvaluation(ScolEvaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getEnseignement() === $this) {
                $evaluation->setEnseignement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtEvent>
     */
    public function getEdtEvents(): Collection
    {
        return $this->edtEvents;
    }

    public function addEdtEvent(EdtEvent $edtEvent): static
    {
        if (!$this->edtEvents->contains($edtEvent)) {
            $this->edtEvents->add($edtEvent);
            $edtEvent->setEnseignement($this);
        }

        return $this;
    }

    public function removeEdtEvent(EdtEvent $edtEvent): static
    {
        if ($this->edtEvents->removeElement($edtEvent)) {
            // set the owning side to null (unless already changed)
            if ($edtEvent->getEnseignement() === $this) {
                $edtEvent->setEnseignement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScolEnseignementUe>
     */
    public function getEnseignementUes(): Collection
    {
        return $this->enseignementUes;
    }

    public function addEnseignementUe(ScolEnseignementUe $enseignementUe): static
    {
        if (!$this->enseignementUes->contains($enseignementUe)) {
            $this->enseignementUes->add($enseignementUe);
            $enseignementUe->setEnseignement($this);
        }

        return $this;
    }

    public function removeEnseignementUe(ScolEnseignementUe $enseignementUe): static
    {
        if ($this->enseignementUes->removeElement($enseignementUe)) {
            // set the owning side to null (unless already changed)
            if ($enseignementUe->getEnseignement() === $this) {
                $enseignementUe->setEnseignement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Previsionnel>
     */
    public function getPrevisionnels(): Collection
    {
        return $this->previsionnels;
    }

    public function addPrevisionnel(Previsionnel $previsionnel): static
    {
        if (!$this->previsionnels->contains($previsionnel)) {
            $this->previsionnels->add($previsionnel);
            $previsionnel->setEnseignement($this);
        }

        return $this;
    }

    public function removePrevisionnel(Previsionnel $previsionnel): static
    {
        if ($this->previsionnels->removeElement($previsionnel)) {
            // set the owning side to null (unless already changed)
            if ($previsionnel->getEnseignement() === $this) {
                $previsionnel->setEnseignement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ScolEnseignement>
     */
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(ScolEnseignement $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }

        return $this;
    }

    public function removeEnfant(ScolEnseignement $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            // set the owning side to null (unless already changed)
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }

        return $this;
    }
}
