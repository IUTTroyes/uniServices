<?php

namespace App\Entity\Structure;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Apc\ApcParcours;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Personnel\PersonnelEnseignantHrs;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Traits\OptionTrait;
use App\Entity\Users\Personnel;
use App\Filter\DiplomeFilter;
use App\Repository\Structure\StructureDiplomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureDiplomeRepository::class)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
#[ApiFilter(DiplomeFilter::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['diplome:detail', 'type-diplome:light']]),
        new Get(
            uriTemplate: '/mini/structure_diplomes/{id}',
            normalizationContext: ['groups' => ['diplome:light', 'type-diplome:light']],
        ),
        new Get(
            uriTemplate: '/maxi/structure_diplomes/{id}',
            normalizationContext: ['groups' => ['diplome:detail', 'type-diplome:light']],
        ),
        new Get(
            uriTemplate: '/pn-light/structure_diplomes/{id}',
            normalizationContext: ['groups' => ['diplome:light', 'type-diplome:light', 'pn:light']],
        ),
        new Get(
            uriTemplate: '/pn-detail/structure_diplomes/{id}',
            normalizationContext: ['groups' => ['diplome:light', 'type-diplome:light', 'pn:detail']],
        ),
        new GetCollection(normalizationContext: ['groups' => ['diplome:detail', 'type-diplome:light']]),
        new GetCollection(
            uriTemplate: '/mini/structure_diplomes',
            normalizationContext: ['groups' => ['diplome:light', 'type-diplome:light']],
        ),
        new GetCollection(
            uriTemplate: '/maxi/structure_diplomes',
            normalizationContext: ['groups' => ['diplome:detail', 'type-diplome:light']],
        ),
        new GetCollection(
            uriTemplate: '/pn-light/structure_diplomes',
            normalizationContext: ['groups' => ['diplome:light', 'type-diplome:light', 'pn:light']],
        ),
        new GetCollection(
            uriTemplate: '/pn-detail/structure_diplomes',
            normalizationContext: ['groups' => ['diplome:light', 'type-diplome:light', 'pn:detail']],
        ),
        new GetCollection(
            uriTemplate: '/maquette/structure_diplomes',
            normalizationContext: ['groups' => ['maquette:detail']],
        ),
        new Delete(),
    ],
    paginationEnabled: false
)]
#[ORM\HasLifecycleCallbacks]
class StructureDiplome
{
    use EduSignTrait;
    use LifeCycleTrait;
    use OptionTrait;
    use OldIdTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['diplome:detail', 'diplome:light', 'maquette:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['diplome:detail', 'diplome:light', 'maquette:detail'])]
    private string $libelle;

    #[ORM\ManyToOne(inversedBy: 'responsableDiplome', cascade: ['persist'])]
    #[Groups(['diplome:detail', 'maquette:detail'])]
    private ?Personnel $responsableDiplome = null;

    #[ORM\ManyToOne(inversedBy: 'assistantDiplome', cascade: ['persist'])]
    #[Groups(['diplome:detail'])]
    private ?Personnel $assistantDiplome = null;

    #[ORM\Column]
    #[Groups(['diplome:detail'])]
    private int $volumeHoraire = 0;

    #[ORM\Column(nullable: true)]
    #[Groups(['diplome:detail'])]
    private ?int $codeCelcatDepartement = null;

    #[ORM\Column(length: 40, nullable: true)]
    #[Groups(['diplome:detail', 'diplome:light', 'maquette:detail'])]
    private ?string $sigle = null;

    #[ORM\Column]
    #[Groups(['diplome:detail', 'diplome:light', 'maquette:detail'])]
    private bool $actif = true;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups(['diplome:detail', 'maquette:detail'])]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    #[Groups(['diplome:detail', 'maquette:detail'])]
    private ?Collection $enfants;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['diplome:detail'])]
    private ?string $logoPartenaireName = null;

    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'diplome', cascade: ['remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[Groups(['diplome:detail', 'maquette:detail'])]
    private Collection $pns;

    #[ORM\ManyToOne(inversedBy: 'diplomes')]
    #[Groups(['diplome:detail'])]
    private ?StructureDepartement $departement = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['diplome:detail', 'maquette:detail'])]
    private ?string $apogeeCodeVersion = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['diplome:detail', 'maquette:detail'])]
    private ?string $apogeeCodeDiplome = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['diplome:detail', 'maquette:detail'])]
    private ?string $apogeeCodeDepartement = null;

    #[ORM\ManyToOne(inversedBy: 'diplomes')]
    #[Groups(['diplome:detail', 'diplome:light', 'maquette:detail'])]
    private ?StructureTypeDiplome $typeDiplome = null;

    #[ORM\ManyToOne(inversedBy: 'diplomes')]
    #[Groups(['diplome:detail'])]
    private ?ApcReferentiel $referentiel = null;

    #[ORM\ManyToOne(cascade: ['remove'], inversedBy: 'diplome')]
    #[Groups(['diplome:detail', 'maquette:detail'])]
    private ?ApcParcours $parcours = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['diplome:detail'])]
    private ?int $cleOreof = null;

    /**
     * @var Collection<int, PersonnelEnseignantHrs>
     */
    #[ORM\OneToMany(targetEntity: PersonnelEnseignantHrs::class, mappedBy: 'diplome')]
    #[Groups(['diplome:detail'])]
    private Collection $enseignantHrs;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
        $this->pns = new ArrayCollection();
        $this->setOpt([]);
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

    public function getResponsableDiplome(): ?Personnel
    {
        return $this->responsableDiplome;
    }

    public function setResponsableDiplome(?Personnel $responsableDiplome): static
    {
        $this->responsableDiplome = $responsableDiplome;

        return $this;
    }

    public function getAssistantDiplome(): ?Personnel
    {
        return $this->assistantDiplome;
    }

    public function setAssistantDiplome(?Personnel $assistantDiplome): static
    {
        $this->assistantDiplome = $assistantDiplome;

        return $this;
    }

    public function getVolumeHoraire(): ?int
    {
        return $this->volumeHoraire;
    }

    public function setVolumeHoraire(int $volumeHoraire): static
    {
        $this->volumeHoraire = $volumeHoraire;

        return $this;
    }

    public function getCodeCelcatDepartement(): ?int
    {
        return $this->codeCelcatDepartement;
    }

    public function setCodeCelcatDepartement(?int $codeCelcatDepartement): static
    {
        $this->codeCelcatDepartement = $codeCelcatDepartement;

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
    public function getEnfants(): Collection
    {
        return $this->enfants;
    }

    public function addEnfant(?self $enfant): static
    {
        if (!$this->enfants->contains($enfant)) {
            $this->enfants->add($enfant);
            $enfant->setParent($this);
        }

        return $this;
    }

    public function removeEnfant(self $enfant): static
    {
        if ($this->enfants->removeElement($enfant)) {
            // set the owning side to null (unless already changed)
            if ($enfant->getParent() === $this) {
                $enfant->setParent(null);
            }
        }

        return $this;
    }

    public function getLogoPartenaire(): ?string
    {
        return $this->logoPartenaireName;
    }

    public function setLogoPartenaire(?string $logoPartenaireName): static
    {
        $this->logoPartenaireName = $logoPartenaireName;

        return $this;
    }

    /**
     * @return Collection<int, StructurePn>
     */
    public function getPns(): Collection
    {
        return $this->pns;
    }

    public function addPn(StructurePn $pn): static
    {
        if (!$this->pns->contains($pn)) {
            $this->pns->add($pn);
            $pn->setDiplome($this);
        }

        return $this;
    }

    public function removePn(StructurePn $pn): static
    {
        if ($this->pns->removeElement($pn)) {
            // set the owning side to null (unless already changed)
            if ($pn->getDiplome() === $this) {
                $pn->setDiplome(null);
            }
        }

        return $this;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'nb_jours_saisie_absence' => 15,
            'supp_absence' => true,
            'anonymat' => false,
            'commentaire_releve' => false,
            'espace_perso_visible' => false,
            'semaine_visible' => 2,
            'certif_qualite' => true,
            'resp_qualite' => 0,
            'update_celcat' => false,
            'saisie_cm_autorisee' => true,
        ]);

        $resolver->setAllowedTypes('nb_jours_saisie_absence', 'int');
        $resolver->setAllowedTypes('supp_absence', 'bool');
        $resolver->setAllowedTypes('anonymat', 'bool');
        $resolver->setAllowedTypes('commentaire_releve', 'bool');
        $resolver->setAllowedTypes('espace_perso_visible', 'bool');
        $resolver->setAllowedTypes('semaine_visible', 'int');
        $resolver->setAllowedTypes('certif_qualite', 'bool');
        $resolver->setAllowedTypes('resp_qualite', 'int');
        $resolver->setAllowedTypes('update_celcat', 'bool');
        $resolver->setAllowedTypes('saisie_cm_autorisee', 'bool');
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

    public function getApogeeCodeDiplome(): ?string
    {
        return $this->apogeeCodeDiplome;
    }

    public function setApogeeCodeDiplome(?string $apogeeCodeDiplome): static
    {
        $this->apogeeCodeDiplome = $apogeeCodeDiplome;

        return $this;
    }

    public function getApogeeCodeDepartement(): ?string
    {
        return $this->apogeeCodeDepartement;
    }

    public function setApogeeCodeDepartement(?string $apogeeCodeDepartement): static
    {
        $this->apogeeCodeDepartement = $apogeeCodeDepartement;

        return $this;
    }

    public function getTypeDiplome(): ?StructureTypeDiplome
    {
        return $this->typeDiplome;
    }

    public function setTypeDiplome(?StructureTypeDiplome $typeDiplome): static
    {
        $this->typeDiplome = $typeDiplome;

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

    public function getParcours(): ?ApcParcours
    {
        return $this->parcours;
    }

    public function setParcours(?ApcParcours $parcours): static
    {
        $this->parcours = $parcours;

        return $this;
    }

    public function getCleOreof(): ?int
    {
        return $this->cleOreof;
    }

    public function setCleOreof(?int $cleOreof): static
    {
        $this->cleOreof = $cleOreof;

        return $this;
    }

    public function getAnnees(): ?array
    {
        $annees = [];
        foreach ($this->getPns() as $pn) {
            if ($pn->isActif()) {
                foreach ($pn->getAnnees() as $annee) {
                    $annees[] = $annee;
                }
            }
        }
        return $annees;
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
            $enseignantHr->setDiplome($this);
        }

        return $this;
    }

    public function removeEnseignantHr(PersonnelEnseignantHrs $enseignantHr): static
    {
        if ($this->enseignantHrs->removeElement($enseignantHr)) {
            // set the owning side to null (unless already changed)
            if ($enseignantHr->getDiplome() === $this) {
                $enseignantHr->setDiplome(null);
            }
        }

        return $this;
    }
}
