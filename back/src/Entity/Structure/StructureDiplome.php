<?php

namespace App\Entity\Structure;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Apc\ApcParcours;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Traits\EduSignTrait;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Entity\Traits\OptionTrait;
use App\Entity\Users\Personnel;
use App\Repository\Structure\StructureDiplomeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StructureDiplomeRepository::class)]
#[ApiFilter(BooleanFilter::class, properties: ['actif'])]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(normalizationContext: ['groups' => ['structure_diplome:read', 'structure_diplome:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['structure_diplome:read']]),
        new GetCollection(
            uriTemplate: '/diplomes-par-departement/{departementId}',
            uriVariables: [
                'departementId' => new Link(fromClass: StructureDepartement::class, identifiers: ['id'], toProperty: 'departement')
            ],
            normalizationContext: ['groups' => ['structure_diplome:read']]
        )
    ]
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
    #[Groups(['structure_diplome:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['structure_diplome:read'])]
    private string $libelle;

    #[ORM\ManyToOne(inversedBy: 'responsableDiplome')]
    private ?Personnel $responsableDiplome = null;

    #[ORM\ManyToOne(inversedBy: 'assistantDiplome')]
    private ?Personnel $assistantDiplome = null;

    #[ORM\Column]
    #[Groups(['structure_diplome:read:full'])]
    private int $volumeHoraire = 0;

    #[ORM\Column(nullable: true)]
    #[Groups(['structure_diplome:read:full'])]
    private ?int $codeCelcatDepartement = null;

    #[ORM\Column(length: 40, nullable: true)]
    #[Groups(['structure_diplome:read'])]
    private ?string $sigle = null;

    #[ORM\Column]
    #[Groups(['structure_diplome:read'])]
    private bool $actif = true;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'enfants')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    private ?Collection $enfants;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPartenaireName = null;

    #[ORM\OneToMany(targetEntity: StructurePn::class, mappedBy: 'diplome', fetch: 'EAGER')]
    #[Groups(['structure_diplome:read:full'])]
    private Collection $structurePns;

    #[ORM\ManyToOne(inversedBy: 'structureDiplomes')]
    private ?StructureDepartement $departement = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['structure_diplome:read:full'])]
    private ?string $apogeeCodeVersion = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['structure_diplome:read:full'])]
    private ?string $apogeeCodeDiplome = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['structure_diplome:read:full'])]
    private ?string $apogeeCodeDepartement = null;

    #[ORM\ManyToOne(inversedBy: 'structureDiplomes')]
    #[Groups(['structure_diplome:read'])]
    private ?StructureTypeDiplome $typeDiplome = null;

    #[ORM\ManyToOne(inversedBy: 'diplomes')]
    private ?ApcReferentiel $apcReferentiel = null;

    #[ORM\ManyToOne(inversedBy: 'diplome')]
    private ?ApcParcours $apcParcours = null;

    /**
     * @var Collection<int, StructureAnnee>
     */
    #[ORM\OneToMany(targetEntity: StructureAnnee::class, mappedBy: 'structureDiplome')]
    #[Groups(['structure_diplome:read'])]
    private Collection $annees;

    #[ORM\Column(nullable: true)]
    private ?int $cleOreof = null;

    public function __construct()
    {
        $this->enfants = new ArrayCollection();
        $this->structurePns = new ArrayCollection();
        $this->setOpt([]);
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
    public function getStructurePns(): Collection
    {
        return $this->structurePns;
    }

    public function addStructurePn(StructurePn $structurePn): static
    {
        if (!$this->structurePns->contains($structurePn)) {
            $this->structurePns->add($structurePn);
            $structurePn->setDiplome($this);
        }

        return $this;
    }

    public function removeStructurePn(StructurePn $structurePn): static
    {
        if ($this->structurePns->removeElement($structurePn)) {
            // set the owning side to null (unless already changed)
            if ($structurePn->getDiplome() === $this) {
                $structurePn->setDiplome(null);
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

    public function getApcReferentiel(): ?ApcReferentiel
    {
        return $this->apcReferentiel;
    }

    public function setApcReferentiel(?ApcReferentiel $apcReferentiel): static
    {
        $this->apcReferentiel = $apcReferentiel;

        return $this;
    }

    public function getApcParcours(): ?ApcParcours
    {
        return $this->apcParcours;
    }

    public function setApcParcours(?ApcParcours $apcParcours): static
    {
        $this->apcParcours = $apcParcours;

        return $this;
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
            $annee->setStructureDiplome($this);
        }

        return $this;
    }

    public function removeAnnee(StructureAnnee $annee): static
    {
        if ($this->annees->removeElement($annee)) {
            // set the owning side to null (unless already changed)
            if ($annee->getStructureDiplome() === $this) {
                $annee->setStructureDiplome(null);
            }
        }

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
}
