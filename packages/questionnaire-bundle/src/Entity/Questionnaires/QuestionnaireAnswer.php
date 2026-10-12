<?php

namespace QuestionnaireBundle\Entity\Questionnaires;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireReponseRepository;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: QuestionnaireReponseRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_answer', columns: ['invitation_id', 'section_id', 'question_id'])]
#[ORM\Index(name: 'idx_answer_invitation', columns: ['invitation_id'])]
class QuestionnaireAnswer implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['invitation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questionnaireReponses')]
    private ?QuestionnaireInvitation $invitation = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    private ?QuestionnaireSectionInstance $section = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[Groups(['invitation:read'])]
    private ?QuestionnaireQuestion $question = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['invitation:read'])]
    private mixed $value = null;

    public function __construct(QuestionnaireInvitation $inv, QuestionnaireSectionInstance $psi, QuestionnaireQuestion $qt, mixed $value)
    {
        $this->invitation = $inv;
        $this->section = $psi;
        $this->question = $qt;
        $this->value = $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvitation(): ?QuestionnaireInvitation
    {
        return $this->invitation;
    }

    public function setInvitation(?QuestionnaireInvitation $invitation): static
    {
        $this->invitation = $invitation;

        return $this;
    }

    public function getSection(): ?QuestionnaireSectionInstance
    {
        return $this->section;
    }

    public function setSection(?QuestionnaireSectionInstance $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getQuestion(): ?QuestionnaireQuestion
    {
        return $this->question;
    }

    public function setQuestion(?QuestionnaireQuestion $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }
}
