<?php

namespace QuestionnaireBundle\Entity\Questionnaires;

use QuestionnaireBundle\Enum\QuestInvitationStatusEnum;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireInvitationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: QuestionnaireInvitationRepository::class)]
#[ORM\Index(name: 'idx_invitation_token', columns: ['token'])]
#[ORM\Index(name: 'idx_invitation_questionnaire', columns: ['questionnaire_id'])]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['invitation:read']]),
        new GetCollection(normalizationContext: ['groups' => ['invitation:read']]),
    ],
    normalizationContext: ['groups' => ['invitation:read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['questionnaire' => 'exact', 'questionnaire.uuid' => 'exact'])]
class QuestionnaireInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['invitation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[Groups(['invitation:read'])]
    private ?Questionnaire $questionnaire = null;

    #[ORM\Column(length: 64, unique: true)]
    #[Groups(['invitation:read'])]
    private string $token;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['invitation:read'])]
    private ?string $email = null;

    #[ORM\Column(nullable: true, enumType: QuestInvitationStatusEnum::class)]
    #[Groups(['invitation:read'])]
    private ?QuestInvitationStatusEnum $status = QuestInvitationStatusEnum::PENDING;

    #[ORM\Column]
    #[Groups(['invitation:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['invitation:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['invitation:read'])]
    private ?\DateTimeImmutable $submittedAt = null;

    /**
     * @var Collection<int, QuestionnaireAnswer>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireAnswer::class, mappedBy: 'invitation')]
    #[Groups(['invitation:read'])]
    private Collection $questionnaireReponses;

    public function __construct(
        Questionnaire $q, string $token, ?string $email = null
    )
    {
        $this->questionnaireReponses = new ArrayCollection();
        $this->questionnaire = $q;
        $this->token = $token;
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestionnaire(): ?Questionnaire
    {
        return $this->questionnaire;
    }

    public function setQuestionnaire(?Questionnaire $questionnaire): static
    {
        $this->questionnaire = $questionnaire;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getStatus(): ?QuestInvitationStatusEnum
    {
        return $this->status;
    }

    public function setStatus(QuestInvitationStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    /**
     * @return Collection<int, QuestionnaireAnswer>
     */
    public function getQuestionnaireReponses(): Collection
    {
        return $this->questionnaireReponses;
    }

    public function addQuestionnaireReponse(QuestionnaireAnswer $questionnaireReponse): static
    {
        if (!$this->questionnaireReponses->contains($questionnaireReponse)) {
            $this->questionnaireReponses->add($questionnaireReponse);
            $questionnaireReponse->setInvitation($this);
        }

        return $this;
    }

    public function removeQuestionnaireReponse(QuestionnaireAnswer $questionnaireReponse): static
    {
        if ($this->questionnaireReponses->removeElement($questionnaireReponse)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireReponse->getInvitation() === $this) {
                $questionnaireReponse->setInvitation(null);
            }
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function markStarted(): void
    {
        if ($this->status === QuestInvitationStatusEnum::PENDING) {
            $this->status = QuestInvitationStatusEnum::STARTED;
            $this->startedAt = new \DateTimeImmutable();
        }
    }

    public function markSubmitted(): void
    {
        $this->status = QuestInvitationStatusEnum::SUBMITTED;
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function isSubmitted(): bool { return $this->status === QuestInvitationStatusEnum::SUBMITTED; }

}
