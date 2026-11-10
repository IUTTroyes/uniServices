<?php

namespace FinanceBundle\Entity;

use App\Entity\Users\Personnel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Bon de livraison (PDF) d'une réception. Fichier stocké hors webroot (FinanceDocumentStorage) ; lu par
 * GET /api/finance_receptions/{reception}/bons-livraison/{id}, jamais par un chemin public.
 */
#[ORM\Entity]
class FinanceReceptionDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reception:read', 'finance:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Ignore]
    private ?FinanceReception $reception = null;

    /** Chemin relatif au dossier des documents : jamais exposé. */
    #[ORM\Column(length: 255)]
    #[Ignore]
    private string $chemin = '';

    #[ORM\Column(length: 255)]
    #[Groups(['reception:read', 'finance:read'])]
    private string $nomFichier = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['reception:read', 'finance:read'])]
    private \DateTimeInterface $dateDepot;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['reception:read', 'finance:read'])]
    private ?Personnel $deposePar = null;

    public function __construct()
    {
        $this->dateDepot = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReception(): ?FinanceReception
    {
        return $this->reception;
    }

    public function setReception(?FinanceReception $reception): static
    {
        $this->reception = $reception;

        return $this;
    }

    public function getChemin(): string
    {
        return $this->chemin;
    }

    public function setChemin(string $chemin): static
    {
        $this->chemin = $chemin;

        return $this;
    }

    public function getNomFichier(): string
    {
        return $this->nomFichier;
    }

    /** Nom affiché : nom d'origine nettoyé (sans chemin ni caractères de contrôle), toujours en .pdf. */
    public function setNomFichier(string $nomFichier): static
    {
        $nom = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '', basename($nomFichier)));
        $nom = $nom === '' ? 'bon_de_livraison.pdf' : $nom;
        if (!str_ends_with(mb_strtolower($nom), '.pdf')) {
            $nom .= '.pdf';
        }
        $this->nomFichier = mb_substr($nom, -255);

        return $this;
    }

    public function getDateDepot(): \DateTimeInterface
    {
        return $this->dateDepot;
    }

    public function getDeposePar(): ?Personnel
    {
        return $this->deposePar;
    }

    public function setDeposePar(?Personnel $deposePar): static
    {
        $this->deposePar = $deposePar;

        return $this;
    }
}
