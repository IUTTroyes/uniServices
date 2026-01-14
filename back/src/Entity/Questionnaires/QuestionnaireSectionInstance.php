<?php

namespace App\Entity\Questionnaires;

use App\Repository\Questionnaires\QuestionnaireSectionInstanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionnaireSectionInstanceRepository::class)]
#[ORM\Index(name: 'idx_pubsec_questionnaire_order', columns: ['questionnaire_id', 'sort_order'])]
#[ORM\UniqueConstraint(name: 'uniq_pubsec', columns: ['questionnaire_id', 'section_id', 'repeat_section_item_type', 'repeat_section_item_id'])]
class QuestionnaireSectionInstance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questionnaireSectionInstances')]
    private ?Questionnaire $questionnaire = null;

    #[ORM\ManyToOne(inversedBy: 'questionnaireSectionInstances')]
    private ?QuestionnaireSection $section = null;

    #[ORM\Column]
    private ?int $sortOrder = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titleSnapshot = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $repeatSectionItemType = null;

    #[ORM\Column(nullable: true)]
    private ?int $repeatSectionItemId = null;

    /**
     * @var Collection<int, QuestionnaireAnswer>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireAnswer::class, mappedBy: 'section')]
    private Collection $answers;

    public function __construct(
        Questionnaire $q,
        QuestionnaireSection $st,
        int $order,
        string $titleSnapshot,
        ?string $repeatItemType,
        ?string $repeatItemId
    ) {
        $this->answers = new ArrayCollection();


        $this->questionnaire = $q;
        $this->section = $st;
        $this->sortOrder = $order;
        $this->titleSnapshot = $titleSnapshot;
        $this->repeatSectionItemType = $repeatItemType;
        $this->repeatSectionItemId = $repeatItemId;
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

    public function getSection(): ?QuestionnaireSection
    {
        return $this->section;
    }

    public function setSection(?QuestionnaireSection $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getTitleSnapshot(): ?string
    {
        return $this->titleSnapshot;
    }

    public function setTitleSnapshot(?string $titleSnapshot): static
    {
        $this->titleSnapshot = $titleSnapshot;

        return $this;
    }

    public function getRepeatSectionItemType(): ?string
    {
        return $this->repeatSectionItemType;
    }

    public function setRepeatSectionItemType(?string $repeatSectionItemType): static
    {
        $this->repeatSectionItemType = $repeatSectionItemType;

        return $this;
    }

    public function getRepeatSectionItemId(): ?int
    {
        return $this->repeatSectionItemId;
    }

    public function setRepeatSectionItemId(?int $repeatSectionItemId): static
    {
        $this->repeatSectionItemId = $repeatSectionItemId;

        return $this;
    }

    /**
     * @return Collection<int, QuestionnaireAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addQuestionnaireReponse(QuestionnaireAnswer $questionnaireReponse): static
    {
        if (!$this->answers->contains($questionnaireReponse)) {
            $this->answers->add($questionnaireReponse);
            $questionnaireReponse->setSection($this);
        }

        return $this;
    }

    public function removeQuestionnaireReponse(QuestionnaireAnswer $questionnaireReponse): static
    {
        if ($this->answers->removeElement($questionnaireReponse)) {
            // set the owning side to null (unless already changed)
            if ($questionnaireReponse->getSection() === $this) {
                $questionnaireReponse->setSection(null);
            }
        }

        return $this;
    }
}
