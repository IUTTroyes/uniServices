<?php

namespace UnifolioBundle\Entity;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\FrequenceResumeEnum;
use UnifolioBundle\Enum\ModeNotificationEnum;
use UnifolioBundle\Enum\TypeNotificationEnum;

/**
 * Réglages de notification d'une personne (étudiant ou personnel). Absence de ligne = réglages par défaut.
 */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_preference_notification')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_pref_notif_etudiant', columns: ['etudiant_id'])]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_pref_notif_personnel', columns: ['personnel_id'])]
class PreferenceNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Etudiant $etudiant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Personnel $personnel = null;

    /** Interrupteur général : faux = aucun e-mail UniFolio. */
    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(length: 20, enumType: FrequenceResumeEnum::class, options: ['default' => 'quotidien'])]
    private FrequenceResumeEnum $frequence = FrequenceResumeEnum::QUOTIDIEN;

    /** @var array<string, string> type de notification → mode ; les types absents suivent leur valeur par défaut */
    #[ORM\Column(type: Types::JSON)]
    private array $reglages = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dernierResumeLe = null;

    public function __construct(Etudiant|Personnel $destinataire)
    {
        if ($destinataire instanceof Etudiant) {
            $this->etudiant = $destinataire;
        } else {
            $this->personnel = $destinataire;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getFrequence(): FrequenceResumeEnum
    {
        return $this->frequence;
    }

    public function setFrequence(FrequenceResumeEnum $frequence): static
    {
        $this->frequence = $frequence;

        return $this;
    }

    public function getDernierResumeLe(): ?\DateTimeImmutable
    {
        return $this->dernierResumeLe;
    }

    public function marquerResumeEnvoye(\DateTimeImmutable $le): static
    {
        $this->dernierResumeLe = $le;

        return $this;
    }

    /** Mode effectif d'un type : interrupteur général, réglage personnel, sinon valeur par défaut. */
    public function modePour(TypeNotificationEnum $type): ModeNotificationEnum
    {
        if (!$this->actif) {
            return ModeNotificationEnum::AUCUN;
        }
        $mode = ModeNotificationEnum::tryFrom((string) ($this->reglages[$type->value] ?? ''));

        return $mode !== null && \in_array($mode, $type->getModesAutorises(), true) ? $mode : $type->getModeParDefaut();
    }

    /** Réglage explicite (ou null s'il suit la valeur par défaut). */
    public function reglage(TypeNotificationEnum $type): ?ModeNotificationEnum
    {
        return ModeNotificationEnum::tryFrom((string) ($this->reglages[$type->value] ?? ''));
    }

    public function definirMode(TypeNotificationEnum $type, ModeNotificationEnum $mode): static
    {
        if (!\in_array($mode, $type->getModesAutorises(), true)) {
            throw new \InvalidArgumentException(sprintf('Le mode « %s » n\'est pas possible pour « %s ».', mb_strtolower($mode->getLibelle()), $type->getLibelle()));
        }
        $this->reglages[$type->value] = $mode->value;

        return $this;
    }
}
