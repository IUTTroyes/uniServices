<?php

namespace App\Entity\Users;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Etudiant\EtudiantAbsence;
use App\Entity\Scolarite\ScolEdtEvent;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Repository\PersonnelRepository;
use App\ValueObject\Adresse;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PersonnelRepository::class)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['personnel:read']]),
        new GetCollection(normalizationContext: ['groups' => ['personnel:read']]),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class Personnel implements UserInterface, PasswordAuthenticatedUserInterface
{
    use LifeCycleTrait;
    use OldIdTrait;

    private const STATUT = [
        'MCF' => 'Maître de conférences',
        'PU' => 'Professeur des universités',
        'ATER' => 'Attaché temporaire d\'enseignement et de recherche',
        'PRAG' => 'Professeur agrégé',
        'IE' => 'Ingénieur d\'études',
        'ENSAM' => 'Enseignant associé',
        'DO' => 'Doctorant',
        'VAC' => 'Enseignant Vacataire',
        'PRCE' => 'Professeur certifié',
        'BIATSS' => 'Personnel Biatss',
        ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['personnel:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 75)]
    #[Groups(['personnel:read'])]
    private string $username;

    #[ORM\Column(length: 255)]
    #[Groups(['personnel:read', 'structure_departement_personnel:read'])]
    private string $mailUniv;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['personnel:read'])]
    private array $roles = [];

    #[ORM\Column(length: 75)]
    #[Groups(['personnel:read', 'structure_departement_personnel:read'])]
    private string $prenom;

    #[ORM\Column(length: 75)]
    #[Groups(['personnel:read', 'structure_departement_personnel:read'])]
    private string $nom;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $photoName = null;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'responsableDiplome')]
    #[Groups(['personnel:read'])]
    private Collection $responsableDiplome;

    /**
     * @var Collection<int, StructureDiplome>
     */
    #[ORM\OneToMany(targetEntity: StructureDiplome::class, mappedBy: 'assistantDiplome')]
    #[Groups(['personnel:read'])]
    private Collection $assistantDiplome;

    #[ORM\ManyToOne(inversedBy: 'personnels')]
    #[Groups(['personnel:read'])]
    private ?StructureAnneeUniversitaire $structureAnneeUniversitaire = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $adressePersonnelle = null;

    /**
     * @var Collection<int, StructureDepartementPersonnel>
     */
    #[ORM\OneToMany(targetEntity: StructureDepartementPersonnel::class, mappedBy: 'personnel', orphanRemoval: true)]
    #[Groups(['personnel:read'])]
    private Collection $structureDepartementPersonnels;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'personnel')]
    private Collection $etudiantAbsences;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $idEduSign = null;

    /**
     * @var Collection<int, ScolEvaluation>
     */
    #[ORM\ManyToMany(targetEntity: ScolEvaluation::class, mappedBy: 'personnelAutorise')]
    private Collection $scolEvaluations;

    /**
     * @var Collection<int, ScolEdtEvent>
     */
    #[ORM\OneToMany(targetEntity: ScolEdtEvent::class, mappedBy: 'personnel')]
    private Collection $scolEdtEvents;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $entreprise = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $telBureau = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['personnel:read'])]
    private ?array $domaines = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $bureau = null;

    #[ORM\Column(nullable: true)]
    private ?int $numeroHarpege = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbHeuresService = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mailPerso = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $sitePerso = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $siteUniv = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $responsabilites = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $posteInterne = null;

    #[ORM\Column(length: 15, nullable: true)]
    #[Groups(['personnel:read'])]
    private ?string $statut = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['personnel:read', 'structure_departement_personnel:read'])]
    private ?string $initiales = null;

    public function __construct()
    {
        $this->responsableDiplome = new ArrayCollection();
        $this->assistantDiplome = new ArrayCollection();
        $this->structureDepartementPersonnels = new ArrayCollection();
        $this->etudiantAbsences = new ArrayCollection();
        $this->scolEvaluations = new ArrayCollection();
        $this->scolEdtEvents = new ArrayCollection();
    }

    public function getMails(): array
    {
        return [$this->mailUniv];
    }

    public function getTypeUser(): ?string
    {
        return 'personnel';
    }

    public function __toString(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getMailUniv(): ?string
    {
        return $this->mailUniv;
    }

    public function setMailUniv(string $mailUniv): static
    {
        $this->mailUniv = $mailUniv;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPhotoName(): ?string
    {
        return $this->photoName;
    }

    public function setPhotoName(?string $photoName): static
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * @return Collection<int, StructureDiplome>
     */
    public function getResponsableDiplome(): Collection
    {
        return $this->responsableDiplome;
    }

    public function addResponsableDiplome(StructureDiplome $responsableDiplome): static
    {
        if (!$this->responsableDiplome->contains($responsableDiplome)) {
            $this->responsableDiplome->add($responsableDiplome);
            $responsableDiplome->setResponsableDiplome($this);
        }

        return $this;
    }

    public function removeResponsableDiplome(StructureDiplome $responsableDiplome): static
    {
        if ($this->responsableDiplome->removeElement($responsableDiplome)) {
            // set the owning side to null (unless already changed)
            if ($responsableDiplome->getResponsableDiplome() === $this) {
                $responsableDiplome->setResponsableDiplome(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructureDiplome>
     */
    public function getAssistantDiplome(): Collection
    {
        return $this->assistantDiplome;
    }

    public function addAssistantDiplome(StructureDiplome $assistantDiplome): static
    {
        if (!$this->assistantDiplome->contains($assistantDiplome)) {
            $this->assistantDiplome->add($assistantDiplome);
            $assistantDiplome->setAssistantDiplome($this);
        }

        return $this;
    }

    public function removeAssistantDiplome(StructureDiplome $assistantDiplome): static
    {
        if ($this->assistantDiplome->removeElement($assistantDiplome)) {
            // set the owning side to null (unless already changed)
            if ($assistantDiplome->getAssistantDiplome() === $this) {
                $assistantDiplome->setAssistantDiplome(null);
            }
        }

        return $this;
    }

    public function getStructureAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->structureAnneeUniversitaire;
    }

    public function setStructureAnneeUniversitaire(?StructureAnneeUniversitaire $structureAnneeUniversitaire): static
    {
        $this->structureAnneeUniversitaire = $structureAnneeUniversitaire;

        return $this;
    }

    /**
     * @return Collection<int, StructureDepartementPersonnel>
     */
    public function getStructureDepartementPersonnels(): Collection
    {
        return $this->structureDepartementPersonnels;
    }

    public function addStructureDepartementPersonnel(StructureDepartementPersonnel $structureDepartementPersonnel): static
    {
        if (!$this->structureDepartementPersonnels->contains($structureDepartementPersonnel)) {
            $this->structureDepartementPersonnels->add($structureDepartementPersonnel);
            $structureDepartementPersonnel->setPersonnel($this);
        }

        return $this;
    }

    public function removeStructureDepartementPersonnel(StructureDepartementPersonnel $structureDepartementPersonnel): static
    {
        if ($this->structureDepartementPersonnels->removeElement($structureDepartementPersonnel)) {
            // set the owning side to null (unless already changed)
            if ($structureDepartementPersonnel->getPersonnel() === $this) {
                $structureDepartementPersonnel->setPersonnel(null);
            }
        }

        return $this;
    }

    public function setAdressePersonnelle(Adresse $adresse): void
    {
        $this->adressePersonnelle = $adresse->toArray();
    }

    public function getAdressePersonnelle(): ?Adresse
    {
        if ($this->adressePersonnelle === null) {
            return null;
        }

        return Adresse::fromArray($this->adressePersonnelle);
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
            $etudiantAbsence->setPersonnel($this);
        }

        return $this;
    }

    public function removeEtudiantAbsence(EtudiantAbsence $etudiantAbsence): static
    {
        if ($this->etudiantAbsences->removeElement($etudiantAbsence)) {
            // set the owning side to null (unless already changed)
            if ($etudiantAbsence->getPersonnel() === $this) {
                $etudiantAbsence->setPersonnel(null);
            }
        }

        return $this;
    }

    public function getIdEduSign(): ?array
    {
        return $this->idEduSign;
    }

    public function setIdEduSign(?array $idEduSign): void
    {
        $this->idEduSign = $idEduSign;
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
            $scolEvaluation->addPersonnelAutorise($this);
        }

        return $this;
    }

    public function removeScolEvaluation(ScolEvaluation $scolEvaluation): static
    {
        if ($this->scolEvaluations->removeElement($scolEvaluation)) {
            $scolEvaluation->removePersonnelAutorise($this);
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
            $scolEdtEvent->setPersonnel($this);
        }

        return $this;
    }

    public function removeScolEdtEvent(ScolEdtEvent $scolEdtEvent): static
    {
        if ($this->scolEdtEvents->removeElement($scolEdtEvent)) {
            // set the owning side to null (unless already changed)
            if ($scolEdtEvent->getPersonnel() === $this) {
                $scolEdtEvent->setPersonnel(null);
            }
        }

        return $this;
    }

    public function getEntreprise(): ?string
    {
        return $this->entreprise;
    }

    public function setEntreprise(?string $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getTelBureau(): ?string
    {
        return $this->telBureau;
    }

    public function setTelBureau(?string $telBureau): static
    {
        $this->telBureau = $telBureau;

        return $this;
    }

    public function getDomaines(): ?array
    {
        return $this->domaines;
    }

    public function setDomaines(?array $domaines): static
    {
        $this->domaines = $domaines;

        return $this;
    }

    public function getBureau(): ?string
    {
        return $this->bureau;
    }

    public function setBureau(?string $bureau): static
    {
        $this->bureau = $bureau;

        return $this;
    }

    public function getNumeroHarpege(): ?int
    {
        return $this->numeroHarpege;
    }

    public function setNumeroHarpege(?int $numeroHarpege): static
    {
        $this->numeroHarpege = $numeroHarpege;

        return $this;
    }

    public function getNbHeuresService(): ?int
    {
        return $this->nbHeuresService;
    }

    public function setNbHeuresService(?int $nbHeuresService): static
    {
        $this->nbHeuresService = $nbHeuresService;

        return $this;
    }

    public function getMailPerso(): ?string
    {
        return $this->mailPerso;
    }

    public function setMailPerso(?string $mailPerso): static
    {
        $this->mailPerso = $mailPerso;

        return $this;
    }

    public function getSitePerso(): ?string
    {
        return $this->sitePerso;
    }

    public function setSitePerso(?string $sitePerso): static
    {
        $this->sitePerso = $sitePerso;

        return $this;
    }

    public function getSiteUniv(): ?string
    {
        return $this->siteUniv;
    }

    public function setSiteUniv(?string $siteUniv): static
    {
        $this->siteUniv = $siteUniv;

        return $this;
    }

    public function getResponsabilites(): ?string
    {
        return $this->responsabilites;
    }

    public function setResponsabilites(?string $responsabilites): static
    {
        $this->responsabilites = $responsabilites;

        return $this;
    }

    public function getPosteInterne(): ?string
    {
        return $this->posteInterne;
    }

    public function setPosteInterne(?string $posteInterne): static
    {
        $this->posteInterne = $posteInterne;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    #[Groups(['personnel:read', 'structure_departement_personnel:read'])]
    public function getDisplayStatut(): ?string
    {
        return self::STATUT[$this->statut] ?? null;
    }

    #[Groups(['personnel:read', 'structure_departement_personnel:read'])]
    public function getDisplay(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function getInitiales(): ?string
    {
        return $this->initiales;
    }

    public function setInitiales(?string $initiales): static
    {
        $this->initiales = $initiales;

        return $this;
    }
}
