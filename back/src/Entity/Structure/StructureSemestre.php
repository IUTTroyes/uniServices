<?php

namespace App\Entity\Structure;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Edt\EdtContraintesSemestre;
use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Personnel\PersonnelEnseignantHrs;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Stages\StagePeriode;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Traits\OptionTrait;
use App\Filter\SemestresFilter;
use App\Repository\Structure\StructureSemestreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureSemestreRepository::class)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
#[ApiFilter(SemestresFilter::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['semestre:read', 'semestre:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['semestre:read']])
    ]
)]
#[ORM\HasLifecycleCallbacks]
class StructureSemestre
{
    use LifeCycleTrait;
    use OptionTrait;
    use EduSignTrait;
    use OldIdTrait; //a supprimer après transfert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['semestre:read', 'diplome:read:full', 'diplome:read', 'scolarite:read', 'enseignement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['semestre:read', 'diplome:read:full', 'diplome:read', 'scolarite:read', 'etudiant:read', 'pn:read', 'enseignant_hrs:read'])]
    private string $libelle;

    #[ORM\Column]
    #[Groups(['semestre:read', 'diplome:read:full', 'diplome:read'])]
    private int $ordreAnnee = 0;

    #[ORM\Column]
    #[Groups(['semestre:read', 'diplome:read:full'])]
    private int $ordreLmd = 0;

    #[ORM\Column]
    #[Groups(['semestre:read', 'diplome:read:full', 'diplome:read'])]
    private bool $actif = true;

    #[ORM\Column]
    #[Groups(['semestre:read', 'pn:read'])]
    private int $nbGroupesCm = 1;

    #[ORM\Column]
    #[Groups(['semestre:read', 'pn:read'])]
    private int $nbGroupesTd = 1;

    #[ORM\Column()]
    #[Groups(['semestre:read', 'pn:read'])]
    private int $nbGroupesTp = 2;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['semestre:read', 'diplome:read:full', 'diplome:read', 'pn:read'])]
    private ?string $codeElement = null;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, mappedBy: 'semestres')]
    #[Groups(['semestre:read'])]
    private Collection $groupes;

    #[ORM\ManyToOne(inversedBy: 'semestres')]
    #[Groups(['semestre:read', 'scolarite:read', 'enseignement:read', 'etudiant:read'])]
    private ?StructureAnnee $annee = null;

    /**
     * @var Collection<int, StructureUe>
     */
    #[ORM\OneToMany(targetEntity: StructureUe::class, mappedBy: 'semestre')]
    #[Groups(['semestre:read', 'diplome:read:full', 'pn:read'])]
    private Collection $ues;

    /**
     * @var Collection<int, ScolEvaluation>
     */
    #[ORM\OneToMany(targetEntity: ScolEvaluation::class, mappedBy: 'semestre')]
    #[Groups(['semestre:read'])]
    private Collection $evaluations;

    /**
     * @var Collection<int, EdtEvent>
     */
    #[ORM\OneToMany(targetEntity: EdtEvent::class, mappedBy: 'semestre')]
    #[Groups(['semestre:read'])]
    private Collection $events;

    /**
     * @var Collection<int, EdtContraintesSemestre>
     */
    #[ORM\OneToMany(targetEntity: EdtContraintesSemestre::class, mappedBy: 'semestre')]
    #[Groups(['semestre:read'])]
    private Collection $contraintesSemestres;

    #[ORM\OneToMany(mappedBy: 'semestre', targetEntity: EtudiantScolariteSemestre::class, cascade: ['persist'])]
    private Collection $scolariteSemestre;

    /**
     * @var Collection<int, StagePeriode>
     */
    #[ORM\OneToMany(targetEntity: StagePeriode::class, mappedBy: 'semestreProgramme')]
    private Collection $stagePeriodes;

    /**
     * @var Collection<int, PersonnelEnseignantHrs>
     */
    #[ORM\OneToMany(targetEntity: PersonnelEnseignantHrs::class, mappedBy: 'semestre')]
    private Collection $enseignantHrs;



    public function __construct()
    {
        $this->groupes = new ArrayCollection();
        $this->setOpt([]);
        $this->ues = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->contraintesSemestres = new ArrayCollection();
        $this->scolariteSemestre = new ArrayCollection();
        $this->stagePeriodes = new ArrayCollection();
        $this->enseignantHrs = new ArrayCollection();
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

    public function getOrdreAnnee(): ?int
    {
        return $this->ordreAnnee;
    }

    public function setOrdreAnnee(int $ordreAnnee): static
    {
        $this->ordreAnnee = $ordreAnnee;

        return $this;
    }

    public function getOrdreLmd(): ?int
    {
        return $this->ordreLmd;
    }

    public function setOrdreLmd(int $ordreLmd): static
    {
        $this->ordreLmd = $ordreLmd;

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

    public function getNbGroupesCm(): ?int
    {
        return $this->nbGroupesCm;
    }

    public function setNbGroupesCm(int $nbGroupesCm): static
    {
        $this->nbGroupesCm = $nbGroupesCm;

        return $this;
    }

    public function getNbGroupesTd(): ?int
    {
        return $this->nbGroupesTd;
    }

    public function setNbGroupesTd(int $nbGroupesTd): static
    {
        $this->nbGroupesTd = $nbGroupesTd;

        return $this;
    }

    public function getNbGroupesTp(): ?int
    {
        return $this->nbGroupesTp;
    }

    public function setNbGroupesTp(int $nbGroupesTp): static
    {
        $this->nbGroupesTp = $nbGroupesTp;

        return $this;
    }

    public function getCodeElement(): ?string
    {
        return $this->codeElement;
    }

    public function setCodeElement(?string $codeElement): static
    {
        $this->codeElement = $codeElement;

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
            $groupe->addSemestre($this);
        }

        return $this;
    }

    public function removeGroupe(StructureGroupe $groupe): static
    {
        if ($this->groupes->removeElement($groupe)) {
            $groupe->removeSemestre($this);
        }

        return $this;
    }

    public function getAnnee(): ?StructureAnnee
    {
        return $this->annee;
    }

    public function setAnnee(?StructureAnnee $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mail_releve' => false,
            'mail_modif_note' => false,
            'dest_mail_releve' => 0,
            'dest_mail_modif_note' => 0,
            'eval_visible' => true,
            'eval_modif' => true,
            'penalite_absence' => 0.5,
            'mail_absence_resp' => false,
            'dest_mail_absence_resp' => 0,
            'mail_absence_etudiant' => false,
            'opt_penalite_absence' => true,
            'mail_assistante_justif_absence' => false,
            'bilan_semestre' => true,
            'rattrapage' => true,
            'mail_rattrapage' => 0,
        ]);

        $resolver->setAllowedTypes('mail_releve', 'bool');
        $resolver->setAllowedTypes('mail_modif_note', 'bool');
        $resolver->setAllowedTypes('dest_mail_releve', 'int');
        $resolver->setAllowedTypes('dest_mail_modif_note', 'int');
        $resolver->setAllowedTypes('eval_visible', 'bool');
        $resolver->setAllowedTypes('eval_modif', 'bool');
        $resolver->setAllowedTypes('penalite_absence', 'float');
        $resolver->setAllowedTypes('mail_absence_resp', 'bool');
        $resolver->setAllowedTypes('dest_mail_absence_resp', 'int');
        $resolver->setAllowedTypes('mail_absence_etudiant', 'bool');
        $resolver->setAllowedTypes('opt_penalite_absence', 'bool');
        $resolver->setAllowedTypes('mail_assistante_justif_absence', 'bool');
        $resolver->setAllowedTypes('bilan_semestre', 'bool');
        $resolver->setAllowedTypes('rattrapage', 'bool');
        $resolver->setAllowedTypes('mail_rattrapage', 'int');
    }

    /**
     * @return Collection<int, StructureUe>
     */
    public function getUes(): Collection
    {
        return $this->ues;
    }

    public function addUe(StructureUe $structureUe): static
    {
        if (!$this->ues->contains($structureUe)) {
            $this->ues->add($structureUe);
            $structureUe->setSemestre($this);
        }

        return $this;
    }

    public function removeUe(StructureUe $structureUe): static
    {
        if ($this->ues->removeElement($structureUe)) {
            // set the owning side to null (unless already changed)
            if ($structureUe->getSemestre() === $this) {
                $structureUe->setSemestre(null);
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
            $evaluation->setSemestre($this);
        }

        return $this;
    }

    public function removeEvaluation(ScolEvaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getSemestre() === $this) {
                $evaluation->setSemestre(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(EdtEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setSemestre($this);
        }

        return $this;
    }

    public function removeEvent(EdtEvent $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getSemestre() === $this) {
                $event->setSemestre(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtContraintesSemestre>
     */
    public function getContraintesSemestres(): Collection
    {
        return $this->contraintesSemestres;
    }

    public function addContraintesSemestre(EdtContraintesSemestre $contraintesSemestre): static
    {
        if (!$this->contraintesSemestres->contains($contraintesSemestre)) {
            $this->contraintesSemestres->add($contraintesSemestre);
            $contraintesSemestre->setSemestre($this);
        }

        return $this;
    }

    public function removeContraintesSemestre(EdtContraintesSemestre $contraintesSemestre): static
    {
        if ($this->contraintesSemestres->removeElement($contraintesSemestre)) {
            // set the owning side to null (unless already changed)
            if ($contraintesSemestre->getSemestre() === $this) {
                $contraintesSemestre->setSemestre(null);
            }
        }

        return $this;
    }

    public function getScolariteSemestre(): Collection
    {
        return $this->scolariteSemestre;
    }

    public function setScolariteSemestre(Collection $scolariteSemestre): void
    {
        $this->scolariteSemestre = $scolariteSemestre;
    }

    /**
     * @return Collection<int, StagePeriode>
     */
    public function getStagePeriodes(): Collection
    {
        return $this->stagePeriodes;
    }

    public function addStagePeriode(StagePeriode $stagePeriode): static
    {
        if (!$this->stagePeriodes->contains($stagePeriode)) {
            $this->stagePeriodes->add($stagePeriode);
            $stagePeriode->setSemestreProgramme($this);
        }

        return $this;
    }

    public function removeStagePeriode(StagePeriode $stagePeriode): static
    {
        if ($this->stagePeriodes->removeElement($stagePeriode)) {
            // set the owning side to null (unless already changed)
            if ($stagePeriode->getSemestreProgramme() === $this) {
                $stagePeriode->setSemestreProgramme(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PersonnelEnseignantHrs>
     */
    public function getEnseignantHrs(): Collection
    {
        return $this->enseignantHrs;
    }

    public function addEnseignantHr(PersonnelEnseignantHrs $enseignantHr): static
    {
        if (!$this->enseignantHrs->contains($enseignantHr)) {
            $this->enseignantHrs->add($enseignantHr);
            $enseignantHr->setSemestre($this);
        }

        return $this;
    }

    public function removeEnseignantHr(PersonnelEnseignantHrs $enseignantHr): static
    {
        if ($this->enseignantHrs->removeElement($enseignantHr)) {
            // set the owning side to null (unless already changed)
            if ($enseignantHr->getSemestre() === $this) {
                $enseignantHr->setSemestre(null);
            }
        }

        return $this;
    }
}
