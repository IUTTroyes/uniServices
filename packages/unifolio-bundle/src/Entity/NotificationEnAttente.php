<?php

namespace UnifolioBundle\Entity;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\TypeNotificationEnum;

/**
 * Événement mis de côté pour le prochain résumé. Des événements identiques (même type, même lien) sont fusionnés
 * (compteur) : dix commentaires sur la même trace ne font qu'une ligne.
 */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_notification_attente')]
#[ORM\Index(name: 'idx_unifolio_notif_attente_envoi', columns: ['envoyee_le'])]
class NotificationEnAttente
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

    #[ORM\Column(length: 40, enumType: TypeNotificationEnum::class)]
    private TypeNotificationEnum $type;

    #[ORM\Column(length: 500)]
    private string $texte;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(options: ['default' => 1])]
    private int $nombre = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creeeLe;

    /** Renseignée quand l'événement a été envoyé dans un résumé (ou écarté faute d'abonnement). */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $envoyeeLe = null;

    public function __construct(Etudiant|Personnel $destinataire, TypeNotificationEnum $type, string $texte, ?string $url = null)
    {
        if ($destinataire instanceof Etudiant) {
            $this->etudiant = $destinataire;
        } else {
            $this->personnel = $destinataire;
        }
        $this->type = $type;
        $this->texte = mb_substr($texte, 0, 500);
        $this->url = $url !== null ? mb_substr($url, 0, 255) : null;
        $this->creeeLe = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDestinataire(): Etudiant|Personnel
    {
        return $this->etudiant ?? $this->personnel ?? throw new \LogicException('Notification sans destinataire.');
    }

    public function getType(): TypeNotificationEnum
    {
        return $this->type;
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

    public function getCreeeLe(): \DateTimeImmutable
    {
        return $this->creeeLe;
    }

    public function getEnvoyeeLe(): ?\DateTimeImmutable
    {
        return $this->envoyeeLe;
    }

    /** Fusionne un nouvel événement identique : compteur +1, texte le plus récent. */
    public function fusionner(string $texte): static
    {
        ++$this->nombre;
        $this->texte = mb_substr($texte, 0, 500);

        return $this;
    }

    public function marquerEnvoyee(\DateTimeImmutable $le): static
    {
        $this->envoyeeLe = $le;

        return $this;
    }
}
