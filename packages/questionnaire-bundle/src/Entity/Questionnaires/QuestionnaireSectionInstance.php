<?php

namespace QuestionnaireBundle\Entity\Questionnaires;

use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireSectionInstanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Users\Personnel;
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

    #[ORM\ManyToOne(inversedBy: 'sectionInstances')]
    private ?Questionnaire $questionnaire = null;

    #[ORM\ManyToOne(inversedBy: 'sectionInstances')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?QuestionnaireSection $section = null;

    #[ORM\Column]
    private ?int $sortOrder = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titleSnapshot = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $repeatSectionItemType = null;

    /** Identifiant de l'élément répété (matière, ressource…), tel qu'enregistré dans la section configurable. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $repeatSectionItemId = null;

    /**
     * @var Collection<int, QuestionnaireAnswer>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireAnswer::class, mappedBy: 'section')]
    private Collection $answers;

    /** Enseignement évalué par cette section (matière, ressource, SAÉ ou ligne de prévisionnel), s'il est connu. */
    #[ORM\ManyToOne(targetEntity: ScolEnseignement::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ScolEnseignement $enseignement = null;

    /** Intervenant évalué (section répétée sur le prévisionnel : un enseignement assuré par une personne). */
    #[ORM\ManyToOne(targetEntity: Personnel::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Personnel $personnelCible = null;

    /**
     * Collègues destinataires des résultats de cette section.
     *
     * @var Collection<int, QuestionnaireDiffusion>
     */
    #[ORM\OneToMany(targetEntity: QuestionnaireDiffusion::class, mappedBy: 'sectionInstance', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $diffusions;

    public function __construct(
        Questionnaire $q,
        QuestionnaireSection $st,
        int $order,
        string $titleSnapshot,
        ?string $repeatItemType,
        ?string $repeatItemId
    ) {
        $this->answers = new ArrayCollection();
        $this->diffusions = new ArrayCollection();

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

    public function getEnseignement(): ?ScolEnseignement
    {
        return $this->enseignement;
    }

    public function setEnseignement(?ScolEnseignement $enseignement): static
    {
        $this->enseignement = $enseignement;

        return $this;
    }

    public function getPersonnelCible(): ?Personnel
    {
        return $this->personnelCible;
    }

    public function setPersonnelCible(?Personnel $personnel): static
    {
        $this->personnelCible = $personnel;

        return $this;
    }

    /** @return Collection<int, QuestionnaireDiffusion> */
    public function getDiffusions(): Collection
    {
        return $this->diffusions;
    }

    public function estDestinataire(Personnel $personnel): bool
    {
        foreach ($this->diffusions as $diffusion) {
            if ($diffusion->getPersonnel()->getId() === $personnel->getId()) {
                return true;
            }
        }

        return false;
    }

    public function ajouterDestinataire(Personnel $personnel, string $origine): ?QuestionnaireDiffusion
    {
        if ($this->estDestinataire($personnel)) {
            return null;
        }
        $diffusion = new QuestionnaireDiffusion($this, $personnel, $origine);
        $this->diffusions->add($diffusion);

        return $diffusion;
    }

    public function retirerDestinataire(QuestionnaireDiffusion $diffusion): void
    {
        $this->diffusions->removeElement($diffusion);
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

    public function getRepeatSectionItemId(): ?string
    {
        return $this->repeatSectionItemId;
    }

    public function setRepeatSectionItemId(?string $repeatSectionItemId): static
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
