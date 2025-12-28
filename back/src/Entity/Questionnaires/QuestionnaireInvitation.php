<?php

namespace App\Entity\Questionnaires;

use App\Enum\QuestInvitationStatusEnum;
use App\Repository\Questionnaires\QuestionnaireInvitationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionnaireInvitationRepository::class)]
#[ORM\Index(name: 'idx_invitation_token', columns: ['token'])]
#[ORM\Index(name: 'idx_invitation_questionnaire', columns: ['questionnaire_id'])]
class QuestionnaireInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questionnaireInvitations')]
    private ?Questionnaire $questionnaire = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(nullable: true, enumType: QuestInvitationStatusEnum::class)]
    private ?QuestInvitationStatusEnum $status = QuestInvitationStatusEnum::PENDING;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    /**
     * @var Collection<int, QuestionnaireAnswer>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireAnswer::class, mappedBy: 'invitation')]
    private Collection $questionnaireReponses;

    public function __construct(
        Questionnaire $q, string $token
    )
    {
        $this->questionnaireReponses = new ArrayCollection();
        $this->questionnaire = $q;
        $this->token = $token;
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
