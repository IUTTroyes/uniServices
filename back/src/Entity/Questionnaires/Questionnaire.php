<?php

namespace App\Entity\Questionnaires;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Traits\LifeCycleTrait;
use App\Entity\Traits\OptionTrait;
use App\Enum\QuestStatutEnum;
use App\Repository\Questionnaires\QuestionnaireRepository;
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
    normalizationContext: ['groups' => ['questionnaire:read']],
)]
#[ORM\HasLifecycleCallbacks]
class Questionnaire
{
    use LifeCycleTrait;
    use OptionTrait;

    #[ORM\Column(type: UuidType::NAME)]
    #[ApiProperty(identifier: true)]
    #[Groups(['questionnaire:read'])]
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
    #[Groups(['questionnaire:read'])]
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

    public function __construct()
    {
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
        ]);

        $resolver->setAllowedTypes('anonymous', 'bool');
        $resolver->setAllowedTypes('autoSave', 'bool');
        $resolver->setAllowedTypes('allowBack', 'bool');
        $resolver->setAllowedTypes('showProgress', 'bool');
        $resolver->setAllowedTypes('requireCompletion', 'bool');
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
}
