<?php

namespace App\Entity\Email;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Traits\LifeCycleTrait;
use App\Repository\Email\EmailTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Personnalisation d'un email par département (ou globale si departement est null).
 *
 * Hiérarchie de résolution (priorité décroissante) :
 *   1. Personnalisation département (departement = X)
 *   2. Template par défaut Twig du package
 */
#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
#[ORM\Table(name: 'email_template')]
#[ORM\UniqueConstraint(name: 'uq_email_template', columns: ['email_key', 'departement_id', 'locale'])]
#[ORM\HasLifecycleCallbacks]
class EmailTemplate
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Clé unique de l'email, ex : "questionnaire.invitation".
     * Doit correspondre à une clé enregistrée dans EmailRegistry.
     */
    #[ORM\Column(length: 100)]
    private string $emailKey;

    /**
     * Département concerné, ou null pour une personnalisation globale (tous départements).
     */
    #[ORM\ManyToOne(targetEntity: StructureDepartement::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?StructureDepartement $departement = null;

    /**
     * Code de langue, ex : 'fr', 'en'.
     */
    #[ORM\Column(length: 5)]
    private string $locale = 'fr';

    /**
     * Objet du mail. Peut contenir des variables Twig, ex : "Invitation au questionnaire {{ survey.titre }}".
     */
    #[ORM\Column(length: 255)]
    private string $subject;

    /**
     * Corps HTML du mail. Peut contenir du HTML et des variables Twig.
     * Ce contenu est injecté dans le bloc `body` du layout commun.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $bodyHtml;

    /**
     * Corps texte brut du mail.
     * Si null, sera généré automatiquement depuis bodyHtml via strip_tags.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bodyText = null;

    /**
     * Si true, ce template utilise le layout commun.
     * Si false, bodyHtml contient un template HTML complet.
     */
    #[ORM\Column]
    private bool $useLayout = true;

    public function __construct(string $emailKey, string $subject, string $bodyHtml)
    {
        $this->emailKey = $emailKey;
        $this->subject = $subject;
        $this->bodyHtml = $bodyHtml;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailKey(): string
    {
        return $this->emailKey;
    }

    public function setEmailKey(string $emailKey): static
    {
        $this->emailKey = $emailKey;

        return $this;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBodyHtml(): string
    {
        return $this->bodyHtml;
    }

    public function setBodyHtml(string $bodyHtml): static
    {
        $this->bodyHtml = $bodyHtml;

        return $this;
    }

    public function getBodyText(): ?string
    {
        return $this->bodyText;
    }

    public function setBodyText(?string $bodyText): static
    {
        $this->bodyText = $bodyText;

        return $this;
    }

    public function isUseLayout(): bool
    {
        return $this->useLayout;
    }

    public function setUseLayout(bool $useLayout): static
    {
        $this->useLayout = $useLayout;

        return $this;
    }
}
