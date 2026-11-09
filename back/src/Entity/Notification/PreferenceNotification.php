<?php

namespace App\Entity\Notification;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\FrequenceResumeEnum;
use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réglages de notification par e-mail d'une personne, pour toute l'application. Absence de ligne = réglages par défaut.
 */
#[ORM\Entity]
#[ORM\Table(name: 'notification_preference')]
#[ORM\UniqueConstraint(name: 'uniq_notification_pref_etudiant', columns: ['etudiant_id'])]
#[ORM\UniqueConstraint(name: 'uniq_notification_pref_personnel', columns: ['personnel_id'])]
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

    /** Interrupteur général des e-mails (la cloche n'est pas concernée). */
    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(length: 20, enumType: FrequenceResumeEnum::class, options: ['default' => 'quotidien'])]
    private FrequenceResumeEnum $frequence = FrequenceResumeEnum::QUOTIDIEN;

    /** @var array<string, string> clé de type → mode ; les types absents suivent leur valeur par défaut */
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

    /** Mode effectif par e-mail : interrupteur général, réglage personnel (s'il reste valide), sinon valeur par défaut. */
    public function modePour(NotificationType $type): ModeNotificationEnum
    {
        if (!$this->actif) {
            return ModeNotificationEnum::AUCUN;
        }

        return $this->reglageValide($type) ?? $type->modeParDefaut;
    }

    /** Mode affiché dans les préférences : le réglage personnel s'il existe et reste valide, sinon le défaut (hors interrupteur). */
    public function modeAffiche(NotificationType $type): ModeNotificationEnum
    {
        return $this->reglageValide($type) ?? $type->modeParDefaut;
    }

    public function definirMode(NotificationType $type, ModeNotificationEnum $mode): static
    {
        if (!$type->autorise($mode)) {
            throw new \InvalidArgumentException(sprintf('Le mode « %s » n\'est pas possible pour « %s ».', mb_strtolower($mode->getLibelle()), $type->libelle));
        }
        $this->reglages[$type->cle] = $mode->value;

        return $this;
    }

    private function reglageValide(NotificationType $type): ?ModeNotificationEnum
    {
        $mode = ModeNotificationEnum::tryFrom((string) ($this->reglages[$type->cle] ?? ''));

        return $mode !== null && $type->autorise($mode) ? $mode : null;
    }
}
