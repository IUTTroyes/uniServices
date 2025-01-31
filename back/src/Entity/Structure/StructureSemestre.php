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
use App\Entity\Scolarite\ScolEvaluation;
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
    #[Groups(['semestre:read', 'structure_diplome:read:full', 'structure_diplome:read', 'scolarite:read', 'scol_enseignement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['semestre:read', 'structure_diplome:read:full', 'structure_diplome:read', 'scolarite:read'])]
    private string $libelle;

    #[ORM\Column]
    #[Groups(['semestre:read', 'structure_diplome:read:full', 'structure_diplome:read'])]
    private int $ordreAnnee = 0;

    #[ORM\Column]
    #[Groups(['semestre:read', 'structure_diplome:read:full'])]
    private int $ordreLmd = 0;

    #[ORM\Column]
    #[Groups(['semestre:read', 'structure_diplome:read:full', 'structure_diplome:read'])]
    private bool $actif = true;

    #[ORM\Column]
    #[Groups(['semestre:read'])]
    private int $nbGroupesCm = 1;

    #[ORM\Column]
    #[Groups(['semestre:read'])]
    private int $nbGroupesTd = 1;

    #[ORM\Column()]
    #[Groups(['semestre:read'])]
    private int $nbGroupesTp = 2;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['semestre:read', 'structure_diplome:read:full'])]
    private ?string $codeElement = null;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, mappedBy: 'semestres')]
    #[Groups(['semestre:read'])]
    private Collection $structureGroupes;

    #[ORM\ManyToOne(inversedBy: 'structureSemestres')]
    #[Groups(['semestre:read', 'scolarite:read'])]
    private ?StructureAnnee $annee = null;

    /**
     * @var Collection<int, StructureUe>
     */
    #[ORM\OneToMany(targetEntity: StructureUe::class, mappedBy: 'semestre')]
    #[Groups(['semestre:read', 'structure_diplome:read:full'])]
    private Collection $structureUes;

    /**
     * @var Collection<int, ScolEvaluation>
     */
    #[ORM\OneToMany(targetEntity: ScolEvaluation::class, mappedBy: 'semestre')]
    #[Groups(['semestre:read'])]
    private Collection $scolEvaluations;

    /**
     * @var Collection<int, EdtEvent>
     */
    #[ORM\OneToMany(targetEntity: EdtEvent::class, mappedBy: 'semestre')]
    #[Groups(['semestre:read'])]
    private Collection $scolEdtEvents;

    /**
     * @var Collection<int, EdtContraintesSemestre>
     */
    #[ORM\OneToMany(targetEntity: EdtContraintesSemestre::class, mappedBy: 'semestre')]
    #[Groups(['semestre:read'])]
    private Collection $edtContraintesSemestres;

    #[ORM\OneToMany(mappedBy: 'structure_semestre', targetEntity: EtudiantScolariteSemestre::class, cascade: ['persist'])]
    private Collection $etudiantScolariteSemestre;



    public function __construct()
    {
        $this->structureGroupes = new ArrayCollection();
        $this->setOpt([]);
        $this->structureUes = new ArrayCollection();
        $this->scolEvaluations = new ArrayCollection();
        $this->scolEdtEvents = new ArrayCollection();
        $this->edtContraintesSemestres = new ArrayCollection();
        $this->etudiantScolariteSemestre = new ArrayCollection();
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
    public function getStructureGroupes(): Collection
    {
        return $this->structureGroupes;
    }

    public function addStructureGroupe(StructureGroupe $structureGroupe): static
    {
        if (!$this->structureGroupes->contains($structureGroupe)) {
            $this->structureGroupes->add($structureGroupe);
            $structureGroupe->addSemestre($this);
        }

        return $this;
    }

    public function removeStructureGroupe(StructureGroupe $structureGroupe): static
    {
        if ($this->structureGroupes->removeElement($structureGroupe)) {
            $structureGroupe->removeSemestre($this);
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
    public function getStructureUes(): Collection
    {
        return $this->structureUes;
    }

    public function addStructureUe(StructureUe $structureUe): static
    {
        if (!$this->structureUes->contains($structureUe)) {
            $this->structureUes->add($structureUe);
            $structureUe->setSemestre($this);
        }

        return $this;
    }

    public function removeStructureUe(StructureUe $structureUe): static
    {
        if ($this->structureUes->removeElement($structureUe)) {
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
    public function getScolEvaluations(): Collection
    {
        return $this->scolEvaluations;
    }

    public function addScolEvaluation(ScolEvaluation $scolEvaluation): static
    {
        if (!$this->scolEvaluations->contains($scolEvaluation)) {
            $this->scolEvaluations->add($scolEvaluation);
            $scolEvaluation->setSemestre($this);
        }

        return $this;
    }

    public function removeScolEvaluation(ScolEvaluation $scolEvaluation): static
    {
        if ($this->scolEvaluations->removeElement($scolEvaluation)) {
            // set the owning side to null (unless already changed)
            if ($scolEvaluation->getSemestre() === $this) {
                $scolEvaluation->setSemestre(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtEvent>
     */
    public function getScolEdtEvents(): Collection
    {
        return $this->scolEdtEvents;
    }

    public function addScolEdtEvent(EdtEvent $scolEdtEvent): static
    {
        if (!$this->scolEdtEvents->contains($scolEdtEvent)) {
            $this->scolEdtEvents->add($scolEdtEvent);
            $scolEdtEvent->setSemestre($this);
        }

        return $this;
    }

    public function removeScolEdtEvent(EdtEvent $scolEdtEvent): static
    {
        if ($this->scolEdtEvents->removeElement($scolEdtEvent)) {
            // set the owning side to null (unless already changed)
            if ($scolEdtEvent->getSemestre() === $this) {
                $scolEdtEvent->setSemestre(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EdtContraintesSemestre>
     */
    public function getEdtContraintesSemestres(): Collection
    {
        return $this->edtContraintesSemestres;
    }

    public function addEdtContraintesSemestre(EdtContraintesSemestre $edtContraintesSemestre): static
    {
        if (!$this->edtContraintesSemestres->contains($edtContraintesSemestre)) {
            $this->edtContraintesSemestres->add($edtContraintesSemestre);
            $edtContraintesSemestre->setSemestre($this);
        }

        return $this;
    }

    public function removeEdtContraintesSemestre(EdtContraintesSemestre $edtContraintesSemestre): static
    {
        if ($this->edtContraintesSemestres->removeElement($edtContraintesSemestre)) {
            // set the owning side to null (unless already changed)
            if ($edtContraintesSemestre->getSemestre() === $this) {
                $edtContraintesSemestre->setSemestre(null);
            }
        }

        return $this;
    }

    public function getEtudiantScolariteSemestre(): Collection
    {
        return $this->etudiantScolariteSemestre;
    }

    public function setEtudiantScolariteSemestre(Collection $etudiantScolariteSemestre): void
    {
        $this->etudiantScolariteSemestre = $etudiantScolariteSemestre;
    }
}
