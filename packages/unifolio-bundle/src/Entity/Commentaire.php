<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Traits\UuidTrait;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Repository\CommentaireRepository;

/** Échange entre l'étudiant et ses évaluateurs sur une trace (éventuellement sur un de ses rattachements). */
#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: 'unifolio_commentaire')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_commentaire_uuid', columns: ['uuid'])]
class Commentaire implements TimestampableInterface
{
    use TimestampableTrait;
    use UuidTrait;

    public const LONGUEUR_MAX = 5000;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Trace $trace;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TraceRattachement $rattachement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Commentaire $parent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Etudiant $auteurEtudiant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Personnel $auteurPersonnel = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $contenu;

    private function __construct(Trace $trace, string $contenu, ?TraceRattachement $rattachement, ?self $parent)
    {
        $contenu = trim($contenu);
        if ($contenu === '') {
            throw new \InvalidArgumentException('Le commentaire est vide.');
        }
        if (mb_strlen($contenu) > self::LONGUEUR_MAX) {
            throw new \InvalidArgumentException(sprintf('Le commentaire est limité à %d caractères.', self::LONGUEUR_MAX));
        }
        if ($rattachement !== null && $rattachement->getTrace() !== $trace) {
            throw new \InvalidArgumentException('Ce rattachement n\'appartient pas à la trace.');
        }
        if ($parent !== null) {
            if ($parent->trace !== $trace) {
                throw new \InvalidArgumentException('Le commentaire parent n\'appartient pas à cette trace.');
            }
            // Fil à un seul niveau : une réponse à une réponse reste rattachée au commentaire racine.
            $parent = $parent->parent ?? $parent;
        }
        $this->trace = $trace;
        $this->contenu = $contenu;
        $this->rattachement = $rattachement ?? $parent?->rattachement;
        $this->parent = $parent;
        $this->setUuid();
    }

    public static function parEtudiant(Trace $trace, Etudiant $auteur, string $contenu, ?TraceRattachement $rattachement = null, ?self $parent = null): self
    {
        $c = new self($trace, $contenu, $rattachement, $parent);
        $c->auteurEtudiant = $auteur;

        return $c;
    }

    public static function parPersonnel(Trace $trace, Personnel $auteur, string $contenu, ?TraceRattachement $rattachement = null, ?self $parent = null): self
    {
        $c = new self($trace, $contenu, $rattachement, $parent);
        $c->auteurPersonnel = $auteur;

        return $c;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrace(): Trace
    {
        return $this->trace;
    }

    public function getRattachement(): ?TraceRattachement
    {
        return $this->rattachement;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getAuteurEtudiant(): ?Etudiant
    {
        return $this->auteurEtudiant;
    }

    public function getAuteurPersonnel(): ?Personnel
    {
        return $this->auteurPersonnel;
    }

    public function isDeEtudiant(): bool
    {
        return $this->auteurEtudiant !== null;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }
}
