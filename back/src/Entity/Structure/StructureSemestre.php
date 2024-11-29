<?php

namespace App\Entity\Structure;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OptionTrait;
use App\Repository\StructureSemestreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[ORM\Entity(repositoryClass: StructureSemestreRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['semestre:read']]),
        new GetCollection(normalizationContext: ['groups' => ['semestre:read']]),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class StructureSemestre
{
    use LifeCycleTrait;
    use OptionTrait;
    use EduSignTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private ?int $ordre_annee = null;

    #[ORM\Column]
    private ?int $ordre_lmd = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column]
    private ?int $nb_groupes_cm = null;

    #[ORM\Column]
    private ?int $nb_groupes_td = null;

    #[ORM\Column()]
    private ?int $nb_groupes_tp = 2;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $code_element = null;

    /**
     * @var Collection<int, StructureGroupe>
     */
    #[ORM\ManyToMany(targetEntity: StructureGroupe::class, mappedBy: 'semestres')]
    private Collection $structureGroupes;

    /**
     * @var Collection<int, StructureScolarite>
     */
    #[ORM\OneToMany(targetEntity: StructureScolarite::class, mappedBy: 'semestre')]
    private Collection $structureScolarites;

    #[ORM\ManyToOne(inversedBy: 'structureSemestres')]
    private ?StructureAnnee $annee = null;

    public function __construct()
    {
        $this->structureGroupes = new ArrayCollection();
        $this->structureScolarites = new ArrayCollection();
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

    public function getOrdreAnnee(): ?int
    {
        return $this->ordre_annee;
    }

    public function setOrdreAnnee(int $ordre_annee): static
    {
        $this->ordre_annee = $ordre_annee;

        return $this;
    }

    public function getOrdreLmd(): ?int
    {
        return $this->ordre_lmd;
    }

    public function setOrdreLmd(int $ordre_lmd): static
    {
        $this->ordre_lmd = $ordre_lmd;

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
        return $this->nb_groupes_cm;
    }

    public function setNbGroupesCm(int $nb_groupes_cm): static
    {
        $this->nb_groupes_cm = $nb_groupes_cm;

        return $this;
    }

    public function getNbGroupesTd(): ?int
    {
        return $this->nb_groupes_td;
    }

    public function setNbGroupesTd(int $nb_groupes_td): static
    {
        $this->nb_groupes_td = $nb_groupes_td;

        return $this;
    }

    public function getNbGroupesTp(): ?int
    {
        return $this->nb_groupes_tp;
    }

    public function setNbGroupesTp(int $nb_groupes_tp): static
    {
        $this->nb_groupes_tp = $nb_groupes_tp;

        return $this;
    }

    public function getCodeElement(): ?string
    {
        return $this->code_element;
    }

    public function setCodeElement(?string $code_element): static
    {
        $this->code_element = $code_element;

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

    /**
     * @return Collection<int, StructureScolarite>
     */
    public function getStructureScolarites(): Collection
    {
        return $this->structureScolarites;
    }

    public function addStructureScolarite(StructureScolarite $structureScolarite): static
    {
        if (!$this->structureScolarites->contains($structureScolarite)) {
            $this->structureScolarites->add($structureScolarite);
            $structureScolarite->setSemestre($this);
        }

        return $this;
    }

    public function removeStructureScolarite(StructureScolarite $structureScolarite): static
    {
        if ($this->structureScolarites->removeElement($structureScolarite)) {
            // set the owning side to null (unless already changed)
            if ($structureScolarite->getSemestre() === $this) {
                $structureScolarite->setSemestre(null);
            }
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
}
