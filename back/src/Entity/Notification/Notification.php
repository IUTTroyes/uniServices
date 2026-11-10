<?php

namespace App\Entity\Notification;

use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal des notifications de l'application : alimente la cloche et, pour les types réglés « dans le résumé »,
 * le prochain e-mail groupé. Créée par App\Notification\NotificationService, jamais directement.
 */
#[ORM\Entity]
#[ORM\Table(name: 'notification')]
#[ORM\UniqueConstraint(name: 'uniq_notification_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_notification_etudiant_lue', columns: ['etudiant_id', 'lue_le'])]
#[ORM\Index(name: 'idx_notification_personnel_lue', columns: ['personnel_id', 'lue_le'])]
#[ORM\Index(name: 'idx_notification_email', columns: ['email_traite_le'])]
class Notification
{
    use UuidTrait;

    public const TEXTE_MAX = 500;
    public const URL_MAX = 255;

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

    /** Clé du type (« unifolio.commentaire_enseignant »). */
    #[ORM\Column(length: 80)]
    private string $type;

    /** Package émetteur, recopié pour l'affichage même si le type n'est plus déclaré. */
    #[ORM\Column(length: 40)]
    private string $source;

    #[ORM\Column(length: self::TEXTE_MAX)]
    private string $texte;

    /** Chemin interne de l'application (« /app/unifolio/traces/… »), jamais une adresse externe. */
    #[ORM\Column(length: self::URL_MAX, nullable: true)]
    private ?string $url = null;

    /** Nombre d'événements identiques fusionnés (« ×3 »). */
    #[ORM\Column(options: ['default' => 1])]
    private int $nombre = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creeLe;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $misAJourLe;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lueLe = null;

    /** Null = en attente du prochain résumé par e-mail ; renseigné = e-mail envoyé, ou pas d'e-mail à envoyer. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailTraiteLe = null;

    public function __construct(Etudiant|Personnel $destinataire, string $type, string $source, string $texte, ?string $url = null)
    {
        if ($destinataire instanceof Etudiant) {
            $this->etudiant = $destinataire;
        } else {
            $this->personnel = $destinataire;
        }
        if ($url !== null && !self::urlInterneValide($url)) {
            throw new \InvalidArgumentException('Le lien d\'une notification doit être un chemin interne de l\'application (commençant par « / »).');
        }
        $this->type = $type;
        $this->source = $source;
        $this->texte = mb_substr(trim($texte), 0, self::TEXTE_MAX);
        $this->url = $url !== null ? $url : null;
        $this->creeLe = $this->misAJourLe = new \DateTimeImmutable();
        $this->setUuid();
    }

    /** Un chemin absolu interne : refuse les URL externes, les liens « //hote » et les schémas (javascript:…). */
    public static function urlInterneValide(string $url): bool
    {
        return mb_strlen($url) <= self::URL_MAX && preg_match('#^/(?!/)[^\s\\\\]*$#', $url) === 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDestinataire(): Etudiant|Personnel
    {
        return $this->etudiant ?? $this->personnel ?? throw new \LogicException('Notification sans destinataire.');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTexte(): string
    {
        return $this->texte;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getNombre(): int
    {
        return $this->nombre;
    }

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }

    public function getMisAJourLe(): \DateTimeImmutable
    {
        return $this->misAJourLe;
    }

    public function getLueLe(): ?\DateTimeImmutable
    {
        return $this->lueLe;
    }

    public function isLue(): bool
    {
        return $this->lueLe !== null;
    }

    public function getEmailTraiteLe(): ?\DateTimeImmutable
    {
        return $this->emailTraiteLe;
    }

    public function appartientA(Etudiant|Personnel $personne): bool
    {
        $mien = $this->getDestinataire();

        return $mien::class === $personne::class && $mien->getId() === $personne->getId();
    }

    /** Fusionne un événement identique arrivé entre-temps : compteur +1, texte le plus récent, de nouveau « non lue ». */
    public function fusionner(string $texte): static
    {
        ++$this->nombre;
        $this->texte = mb_substr(trim($texte), 0, self::TEXTE_MAX);
        $this->misAJourLe = new \DateTimeImmutable();
        $this->lueLe = null;

        return $this;
    }

    public function marquerLue(?\DateTimeImmutable $le = null): static
    {
        $this->lueLe ??= $le ?? new \DateTimeImmutable();

        return $this;
    }

    public function marquerEmailTraite(?\DateTimeImmutable $le = null): static
    {
        $this->emailTraiteLe = $le ?? new \DateTimeImmutable();

        return $this;
    }
}
