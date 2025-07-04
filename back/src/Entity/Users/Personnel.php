<?php

namespace App\Entity\Users;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Entity\Edt\EdtEvent;
use App\Entity\Etudiant\EtudiantAbsence;
use App\Entity\Personnel\PersonnelEnseignantHrs;
use App\Entity\Previsionnel\Previsionnel;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OldIdTrait;
use App\Enum\StatutEnum;
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
        new GetCollection(
            normalizationContext: ['groups' => ['personnel:read']],
        ),
        new Patch(normalizationContext: ['groups' => ['personnel:read']]),
    ],
    order: ['nom' => 'ASC'],
)]
#[ORM\HasLifecycleCallbacks]
class Personnel implements UserInterface, PasswordAuthenticatedUserInterface
{
    use LifeCycleTrait;
    use OldIdTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['personnel:read', 'departement_personnel:read', 'departement:read', 'previsionnel:read', 'previsionnel_semestre:read', 'previsionnel_personnel:read', 'edt_event:read:agenda'])]
    private ?int $id = null;

    #[ORM\Column(length: 75)]
    #[Groups(['personnel:read'])]
    private string $username;

    #[ORM\Column(length: 255)]
    #[Groups(['personnel:read', 'departement_personnel:read'])]
    private string $mailUniv;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['personnel:read'])]
    private array $roles = [];

    #[ORM\Column(length: 75)]
    #[Groups(['personnel:read', 'departement_personnel:read', 'previsionnel:read', 'enseignement:read', 'previsionnel_semestre:read', 'previsionnel_enseignement:read'])]
    private string $prenom;

    #[ORM\Column(length: 75)]
    #[Groups(['personnel:read', 'departement_personnel:read', 'previsionnel:read', 'enseignement:read', 'previsionnel_semestre:read', 'previsionnel_enseignement:read'])]
    private string $nom;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['personnel:read', 'edt_event:read:agenda'])]
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
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $adressePersonnelle = null;

    /**
     * @var Collection<int, StructureDepartementPersonnel>
     */
    #[ORM\OneToMany(targetEntity: StructureDepartementPersonnel::class, mappedBy: 'personnel', orphanRemoval: true)]
    #[Groups(['personnel:read'])]
    private Collection $departementPersonnels;

    /**
     * @var Collection<int, EtudiantAbsence>
     */
    #[ORM\OneToMany(targetEntity: EtudiantAbsence::class, mappedBy: 'personnel')]
    private Collection $absences;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $idEduSign = null;

    /**
     * @var Collection<int, ScolEvaluation>
     */
    #[ORM\ManyToMany(targetEntity: ScolEvaluation::class, mappedBy: 'personnelAutorise')]
    private Collection $evaluations;

    /**
     * @var Collection<int, EdtEvent>
     */
    #[ORM\OneToMany(targetEntity: EdtEvent::class, mappedBy: 'personnel')]
    private Collection $events;

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
    #[Groups(['personnel:read'])]
    private ?int $numeroHarpege = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['previsionnel_personnel:read', 'previsionnel_all_personnels:read', 'departement_personnel:read'])]
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

    #[ORM\Column(length: 15, nullable: true, enumType: StatutEnum::class)]
    #[Groups(['personnel:read', 'previsionnel_personnel:read', 'previsionnel_all_personnels:read'])]
    private ?StatutEnum $statut = null;

    #[ORM\Column(length: 3, nullable: true)]
    #[Groups(['personnel:read', 'departement_personnel:read'])]
    private ?string $initiales = null;

    #[ORM\Column(nullable: true)]
    private ?array $contraintesEdt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['personnel:read'])]
    private ?array $applications = null;


    /**
     * @var Collection<int, Previsionnel>
     */
    #[ORM\OneToMany(targetEntity: Previsionnel::class, mappedBy: 'personnel')]
    private Collection $previsionnels;

    /**
     * @var Collection<int, PersonnelEnseignantHrs>
     */
    #[ORM\OneToMany(targetEntity: PersonnelEnseignantHrs::class, mappedBy: 'personnel', orphanRemoval: true)]
    private Collection $enseignantHrs;

    public function __construct()
    {
        $this->responsableDiplome = new ArrayCollection();
        $this->assistantDiplome = new ArrayCollection();
        $this->departementPersonnels = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->previsionnels = new ArrayCollection();
        $this->enseignantHrs = new ArrayCollection();
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
        $this->prenom = ucwords(mb_strtolower($prenom));

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = mb_strtoupper($nom);

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

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

        return $this;
    }

    /**
     * @return Collection<int, StructureDepartementPersonnel>
     */
    public function getDepartementPersonnels(): Collection
    {
        return $this->departementPersonnels;
    }

    public function addDepartementPersonnel(StructureDepartementPersonnel $departementPersonnel): static
    {
        if (!$this->departementPersonnels->contains($departementPersonnel)) {
            $this->departementPersonnels->add($departementPersonnel);
            $departementPersonnel->setPersonnel($this);
        }

        return $this;
    }

    public function removeDepartementPersonnel(StructureDepartementPersonnel $departementPersonnel): static
    {
        if ($this->departementPersonnels->removeElement($departementPersonnel)) {
            // set the owning side to null (unless already changed)
            if ($departementPersonnel->getPersonnel() === $this) {
                $departementPersonnel->setPersonnel(null);
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
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(EtudiantAbsence $absence): static
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setPersonnel($this);
        }

        return $this;
    }

    public function removeAbsence(EtudiantAbsence $absence): static
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getPersonnel() === $this) {
                $absence->setPersonnel(null);
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
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(ScolEvaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->addPersonnelAutorise($this);
        }

        return $this;
    }

    public function removeEvaluation(ScolEvaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            $evaluation->removePersonnelAutorise($this);
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
            $event->setPersonnel($this);
        }

        return $this;
    }

    public function removeEvent(EdtEvent $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getPersonnel() === $this) {
                $event->setPersonnel(null);
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

    public function getStatut(): ?StatutEnum
    {
        return $this->statut;
    }

    public function setStatut(?StatutEnum $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    #[Groups(['personnel:read', 'departement_personnel:read', 'previsionnel_semestre:read'])]
    public function getDisplayStatut(): ?string
    {
        return $this->statut->getLibelle() ?? '-';
    }

    #[Groups(['personnel:read', 'departement_personnel:read', 'previsionnel:read', 'previsionnel_enseignement:read', 'previsionnel_personnel:read', 'previsionnel_semestre:read', 'previsionnel_all_personnels:read', 'diplome:read', 'edt_event:read:agenda'])]
    public function getDisplay(): string
    {
        return $this->getPrenom() . ' ' . $this->getNom();
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

    public function getContraintesEdt(): ?array
    {
        return $this->contraintesEdt ?? [];
    }

    public function setContraintesEdt(?array $contraintesEdt): static
    {
        $this->contraintesEdt = $contraintesEdt;

        return $this;
    }

    public function getApplications(): ?array
    {
        return $this->applications ?? ['UniTranet'];
    }

    public function setApplications(?array $applications): static
    {
        $this->applications = $applications;

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
            $previsionnel->setPersonnel($this);
        }

        return $this;
    }

    public function removePrevisionnel(Previsionnel $previsionnel): static
    {
        if ($this->previsionnels->removeElement($previsionnel)) {
            // set the owning side to null (unless already changed)
            if ($previsionnel->getPersonnel() === $this) {
                $previsionnel->setPersonnel(null);
            }
        }

        return $this;
    }

    #[Groups(['personnel:read', 'previsionnel_personnel:read', 'previsionnel_all_personnels:read'])]
    public function getStatutSeverity(): string {
        return $this->statut->getBadge();
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
            $enseignantHr->setPersonnel($this);
        }

        return $this;
    }

    public function removeEnseignantHr(PersonnelEnseignantHrs $enseignantHr): static
    {
        if ($this->enseignantHrs->removeElement($enseignantHr)) {
            // set the owning side to null (unless already changed)
            if ($enseignantHr->getPersonnel() === $this) {
                $enseignantHr->setPersonnel(null);
            }
        }

        return $this;
    }
}
