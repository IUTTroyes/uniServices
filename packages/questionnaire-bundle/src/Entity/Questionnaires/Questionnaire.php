<?php

namespace QuestionnaireBundle\Entity\Questionnaires;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\OptionTrait;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Enum\QuestInvitationStatusEnum;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository;
use QuestionnaireBundle\State\QuestionnaireProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuestionnaireRepository::class)]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('CAN_VIEW_QUESTIONNAIRE', object)", normalizationContext: ['groups' => ['questionnaire:read', 'timestamp:read']]),
        // Accès défini par les règles locales ; invitations filtrées par DepartmentCollectionAccessExtension.
        new GetCollection(security: "is_granted('IS_AUTHENTICATED_FULLY')", normalizationContext: ['groups' => ['questionnaire:read', 'timestamp:read']]),
        new Post(securityPostDenormalize: "is_granted('CAN_EDIT_QUESTIONNAIRE', object)", processor: QuestionnaireProcessor::class),
        // Changer de département exige d'être gestionnaire du nouveau département.
        new Patch(security: "is_granted('CAN_EDIT_QUESTIONNAIRE', object)", securityPostDenormalize: "is_granted('CAN_EDIT_QUESTIONNAIRE', object)", processor: QuestionnaireProcessor::class),
        new Delete(security: "is_granted('CAN_DELETE_QUESTIONNAIRE', object)"),
    ],
    normalizationContext: ['groups' => ['questionnaire:read', 'timestamp:read']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'departement' => 'exact',
    'status' => 'exact',
    'title' => 'partial',
])]
#[ApiFilter(ExistsFilter::class, properties: ['departement'])]
#[ApiFilter(BooleanFilter::class, properties: ['estModele'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'title', 'openingDate', 'closingDate'], arguments: ['orderParameterName' => 'order'])]
class Questionnaire implements TimestampableInterface
{
    use TimestampableTrait;
    use OptionTrait;

    #[ORM\Column(type: UuidType::NAME)]
    #[ApiProperty(identifier: true)]
    #[Groups(['questionnaire:read', 'invitation:read'])]
    private Uuid $uuid;

    public function getUuidString(): string
    {
        return (string)$this->getUuid();
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): void
    {
        $this->uuid = $uuid;
    }


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['questionnaire:read', 'invitation:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?\DateTimeInterface $openingDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?\DateTimeInterface $closingDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?string $startText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?string $endText = null;

    /**
     * @var Collection<int, QuestionnaireSection>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireSection::class, mappedBy: 'questionnaire', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $sections;

    #[ORM\Column(length: 50, nullable: false, enumType: QuestStatutEnum::class)]
    #[Groups(['questionnaire:read'])]
    private ?QuestStatutEnum $status = null;

    /**
     * @var Collection<int, QuestionnaireSectionInstance>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireSectionInstance::class, mappedBy: 'questionnaire', cascade: ['persist', 'remove'])]
    private Collection $sectionInstances;

    /**
     * @var Collection<int, QuestionnaireInvitation>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireInvitation::class, mappedBy: 'questionnaire', cascade: ['persist', 'remove'])]
    private Collection $invitations;

    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?int $estimatedTime = null;

    #[ORM\ManyToOne(targetEntity: StructureDepartement::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['questionnaire:read', 'invitation:read'])]
    private ?StructureDepartement $departement = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['questionnaire:read', 'invitation:read'])]
    private ?Personnel $author = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['questionnaire:read'])]
    private bool $completionNotified = false;

    /** Modèle réutilisable (bibliothèque) : jamais publié, sert à créer des questionnaires. */
    #[ORM\Column(options: ['default' => false])]
    #[Groups(['questionnaire:read'])]
    private bool $estModele = false;

    /** Modèle dont ce questionnaire est issu : les campagnes d'un même modèle se comparent d'une année sur l'autre. */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Questionnaire $modeleOrigine = null;

    /** Année universitaire de la campagne (comparaison d'une année sur l'autre). */
    #[ORM\ManyToOne(targetEntity: StructureAnneeUniversitaire::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['questionnaire:read'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    /** Date de clôture effective (début de la relecture des résultats). */
    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?\DateTimeImmutable $clotureLe = null;

    /** La diffusion a été pré-remplie (suggestions calculées au moins une fois). */
    #[ORM\Column(options: ['default' => false])]
    private bool $diffusionInitialisee = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?\DateTimeImmutable $diffusionValideeLe = null;

    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Personnel $diffusionValideePar = null;

    /** Purge des réponses (durée de conservation dépassée) : seuls les résultats agrégés restent, archivés ici. */
    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?\DateTimeImmutable $purgeLe = null;

    /** @var array<string, mixed>|null statistiques agrégées figées au moment de la purge */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $resultatsArchives = null;

    /** Publication des résultats aux collègues destinataires. */
    #[ORM\Column(nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?\DateTimeImmutable $resultatsPubliesLe = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['questionnaire:read'])]
    private ?\DateTimeImmutable $completionNotifiedAt = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->setOpt([]);
        $this->sections = new ArrayCollection();
        $this->sectionInstances = new ArrayCollection();
        $this->invitations = new ArrayCollection();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'anonymous' => true,
            'autoSave' => true,
            'allowBack' => true,
            'showProgress' => true,
            'requireCompletion' => false,
            // Recopiés depuis les réglages du département à la création (QuestionnaireReglages) :
            'modificationReponses' => true,   // modifier ses réponses envoyées tant que le questionnaire est ouvert
            'seuilMinReponses' => 0,          // questionnaire anonyme : résultats masqués sous ce nombre de réponses (0 = aucun seuil)
        ]);

        $resolver->setAllowedTypes('anonymous', 'bool');
        $resolver->setAllowedTypes('autoSave', 'bool');
        $resolver->setAllowedTypes('allowBack', 'bool');
        $resolver->setAllowedTypes('showProgress', 'bool');
        $resolver->setAllowedTypes('requireCompletion', 'bool');
        $resolver->setAllowedTypes('modificationReponses', 'bool');
        $resolver->setAllowedTypes('seuilMinReponses', 'int');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

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

    public function getOpeningDate(): ?\DateTimeInterface
    {
        return $this->openingDate;
    }

    public function setOpeningDate(?\DateTimeInterface $openingDate): static
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closingDate;
    }

    public function setClosingDate(?\DateTimeInterface $closingDate): static
    {
        $this->closingDate = $closingDate;

        return $this;
    }

    public function getStartText(): ?string
    {
        return $this->startText;
    }

    public function setStartText(?string $startText): static
    {
        $this->startText = $startText;

        return $this;
    }

    public function getEndText(): ?string
    {
        return $this->endText;
    }

    public function setEndText(?string $endText): static
    {
        $this->endText = $endText;

        return $this;
    }

    /**
     * @return Collection<int, QuestionnaireSection>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addQuestionnaireSection(QuestionnaireSection $questionnaireSection): static
    {
        if (!$this->sections->contains($questionnaireSection)) {
            $this->sections->add($questionnaireSection);
            $questionnaireSection->setQuestionnaire($this);
        }

        return $this;
    }

    public function removeQuestionnaireSection(QuestionnaireSection $questionnaireSection): static
    {
        if ($this->sections->removeElement($questionnaireSection)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireSection->getQuestionnaire() === $this) {
                $questionnaireSection->setQuestionnaire(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?QuestStatutEnum
    {
        return $this->status;
    }

    public function setStatus(QuestStatutEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    #[Groups(['questionnaire:read'])]
    public function getStatutSeverity(): string {
        return $this->status->getBadge();
    }

    /**
     * @return Collection<int, QuestionnaireSectionInstance>
     */
    public function getSectionInstances(): Collection
    {
        return $this->sectionInstances;
    }

    public function addQuestionnaireSectionInstance(QuestionnaireSectionInstance $questionnaireSectionInstance): static
    {
        if (!$this->sectionInstances->contains($questionnaireSectionInstance)) {
            $this->sectionInstances->add($questionnaireSectionInstance);
            $questionnaireSectionInstance->setQuestionnaire($this);
        }

        return $this;
    }

    public function removeQuestionnaireSectionInstance(QuestionnaireSectionInstance $questionnaireSectionInstance): static
    {
        if ($this->sectionInstances->removeElement($questionnaireSectionInstance)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireSectionInstance->getQuestionnaire() === $this) {
                $questionnaireSectionInstance->setQuestionnaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, QuestionnaireInvitation>
     */
    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    public function addQuestionnaireInvitation(QuestionnaireInvitation $questionnaireInvitation): static
    {
        if (!$this->invitations->contains($questionnaireInvitation)) {
            $this->invitations->add($questionnaireInvitation);
            $questionnaireInvitation->setQuestionnaire($this);
        }

        return $this;
    }

    public function removeQuestionnaireInvitation(QuestionnaireInvitation $questionnaireInvitation): static
    {
        if ($this->invitations->removeElement($questionnaireInvitation)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireInvitation->getQuestionnaire() === $this) {
                $questionnaireInvitation->setQuestionnaire(null);
            }
        }

        return $this;
    }

    #[Groups(['questionnaire:read'])]
    public function isPublished(): bool {
        return $this->getStatus() === QuestStatutEnum::PUBLISHED;
    }

    public function estBrouillon(): bool
    {
        return $this->status === null || $this->status === QuestStatutEnum::DRAFT;
    }

    public function estAnonyme(): bool
    {
        return (bool) ($this->getOpt()['anonymous'] ?? true);
    }

    public function autoriseModificationReponses(): bool
    {
        return (bool) ($this->getOpt()['modificationReponses'] ?? true);
    }

    /** Seuil d'anonymat : nombre minimal de réponses envoyées pour consulter les résultats (0 si sans objet). */
    public function seuilResultats(): int
    {
        return $this->estAnonyme() ? max(0, (int) ($this->getOpt()['seuilMinReponses'] ?? 0)) : 0;
    }

    /** Ouvert aux réponses : publié, et dans la période d'ouverture si elle est fixée. */
    public function estOuvert(?\DateTimeInterface $maintenant = null): bool
    {
        $maintenant ??= new \DateTime();

        return $this->status === QuestStatutEnum::PUBLISHED
            && ($this->openingDate === null || $this->openingDate <= $maintenant)
            && ($this->closingDate === null || $this->closingDate >= $maintenant);
    }

    public function publishNow(): void
    {
        $this->setStatus(QuestStatutEnum::PUBLISHED);
        $this->setPublishedAt(new \DateTimeImmutable());
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getEstimatedTime(): ?int
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(?int $estimatedTime): static
    {
        $this->estimatedTime = $estimatedTime;

        return $this;
    }


    #[Groups(['questionnaire:read'])]
    public function getTotalInvited(): int
    {
        return $this->invitations->count();
    }

    #[Groups(['questionnaire:read'])]
    public function getTotalResponses(): int
    {
        return $this->invitations->filter(
            fn(QuestionnaireInvitation $inv) => $inv->getStatus() === QuestInvitationStatusEnum::SUBMITTED
        )->count();
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

    public function getAuthor(): ?Personnel
    {
        return $this->author;
    }

    public function setAuthor(?Personnel $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function isEstModele(): bool
    {
        return $this->estModele;
    }

    public function setEstModele(bool $estModele): static
    {
        $this->estModele = $estModele;

        return $this;
    }

    public function getModeleOrigine(): ?Questionnaire
    {
        return $this->modeleOrigine;
    }

    public function setModeleOrigine(?Questionnaire $modele): static
    {
        $this->modeleOrigine = $modele;

        return $this;
    }

    /** @return array{uuid: string, title: ?string}|null */
    #[Groups(['questionnaire:read'])]
    public function getModeleOrigineResume(): ?array
    {
        return $this->modeleOrigine ? ['uuid' => $this->modeleOrigine->getUuidString(), 'title' => $this->modeleOrigine->getTitle()] : null;
    }

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $annee): static
    {
        $this->anneeUniversitaire = $annee;

        return $this;
    }

    /** Clôture (manuelle ou automatique) : plus de réponses, début de la relecture des résultats. */
    public function cloturer(): void
    {
        $this->status = QuestStatutEnum::CLOSED;
        $this->clotureLe ??= new \DateTimeImmutable();
    }

    public function getClotureLe(): ?\DateTimeImmutable
    {
        return $this->clotureLe;
    }

    public function isDiffusionInitialisee(): bool
    {
        return $this->diffusionInitialisee;
    }

    public function setDiffusionInitialisee(bool $initialisee): static
    {
        $this->diffusionInitialisee = $initialisee;

        return $this;
    }

    public function getDiffusionValideeLe(): ?\DateTimeImmutable
    {
        return $this->diffusionValideeLe;
    }

    public function getDiffusionValideePar(): ?Personnel
    {
        return $this->diffusionValideePar;
    }

    public function validerDiffusion(?Personnel $par): void
    {
        $this->diffusionValideeLe = new \DateTimeImmutable();
        $this->diffusionValideePar = $par;
    }

    public function getPurgeLe(): ?\DateTimeImmutable
    {
        return $this->purgeLe;
    }

    /** @return array<string, mixed>|null */
    public function getResultatsArchives(): ?array
    {
        return $this->resultatsArchives;
    }

    /** @param array<string, mixed> $resultats statistiques agrégées (aucune réponse individuelle) */
    public function archiverResultats(array $resultats): void
    {
        $this->resultatsArchives = $resultats;
        $this->purgeLe = new \DateTimeImmutable();
    }

    public function getResultatsPubliesLe(): ?\DateTimeImmutable
    {
        return $this->resultatsPubliesLe;
    }

    public function resultatsPublies(): bool
    {
        return $this->resultatsPubliesLe !== null;
    }

    public function publierResultats(): void
    {
        $this->resultatsPubliesLe ??= new \DateTimeImmutable();
    }

    /** Étape des résultats : en cours (questionnaire ouvert), en relecture (clôturé), publiés. */
    #[Groups(['questionnaire:read'])]
    public function getEtapeResultats(): string
    {
        return match (true) {
            $this->resultatsPublies() => 'publies',
            $this->status === QuestStatutEnum::CLOSED => 'relecture',
            default => 'collecte',
        };
    }

    public function isCompletionNotified(): bool
    {
        return $this->completionNotified;
    }

    public function setCompletionNotified(bool $completionNotified): static
    {
        $this->completionNotified = $completionNotified;

        return $this;
    }

    public function getCompletionNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->completionNotifiedAt;
    }

    public function setCompletionNotifiedAt(?\DateTimeImmutable $completionNotifiedAt): static
    {
        $this->completionNotifiedAt = $completionNotifiedAt;

        return $this;
    }
}
