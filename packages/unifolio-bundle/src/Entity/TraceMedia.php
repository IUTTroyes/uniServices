<?php

namespace UnifolioBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\TypeMediaEnum;
use UnifolioBundle\Service\Media\EmbedPolicy;
use UnifolioBundle\Service\Media\StoredMedia;

/** Une preuve d'une trace : fichier stocké, lien, contenu intégré, texte ou code. */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_trace_media')]
class TraceMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Trace $trace;

    #[ORM\Column(length: 20, enumType: TypeMediaEnum::class)]
    private TypeMediaEnum $type;

    #[ORM\Column]
    private int $ordre = 0;

    /** Chemin relatif dans le stockage des médias (médias à fichier). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $chemin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomOriginal = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    private ?int $taille = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $url = null;

    /** Texte libre ou extrait de code. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legende = null;

    /** @var array<string, mixed> ex. ['langage' => 'php'] pour un extrait de code */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    private function __construct(Trace $trace, TypeMediaEnum $type)
    {
        $this->trace = $trace;
        $this->type = $type;
    }

    public static function depuisFichier(Trace $trace, TypeMediaEnum $type, StoredMedia $fichier): self
    {
        if (!$type->requiertFichier()) {
            throw new \InvalidArgumentException('Ce type de média ne repose pas sur un fichier.');
        }
        $media = new self($trace, $type);
        $media->chemin = $fichier->chemin;
        $media->nomOriginal = $fichier->nomOriginal;
        $media->mimeType = $fichier->mimeType;
        $media->taille = $fichier->taille;

        return $media;
    }

    public static function depuisUrl(Trace $trace, TypeMediaEnum $type, string $url): self
    {
        $url = trim($url);
        if (!\in_array($type, [TypeMediaEnum::LIEN, TypeMediaEnum::EMBED], true)) {
            throw new \InvalidArgumentException('Seuls les liens et contenus intégrés reposent sur une URL.');
        }
        if (!self::isUrlHttp($url) || mb_strlen($url) > 2048) {
            throw new \InvalidArgumentException('L\'adresse doit être une URL http(s) valide.');
        }
        if ($type === TypeMediaEnum::EMBED && !EmbedPolicy::isAutorise($url)) {
            throw new \InvalidArgumentException('Ce site ne fait pas partie des contenus intégrables autorisés.');
        }
        $media = new self($trace, $type);
        $media->url = $url;

        return $media;
    }

    public static function depuisTexte(Trace $trace, TypeMediaEnum $type, string $contenu, ?string $langage = null): self
    {
        if (!\in_array($type, [TypeMediaEnum::TEXTE, TypeMediaEnum::CODE], true)) {
            throw new \InvalidArgumentException('Seuls les textes et extraits de code ont un contenu saisi.');
        }
        if (trim($contenu) === '') {
            throw new \InvalidArgumentException('Le contenu ne peut pas être vide.');
        }
        $media = new self($trace, $type);
        $media->contenu = $contenu;
        $media->setLangage($langage);

        return $media;
    }

    private static function isUrlHttp(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return \in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrace(): Trace
    {
        return $this->trace;
    }

    public function getType(): TypeMediaEnum
    {
        return $this->type;
    }

    /** @return TypeMediaEnum[] */
    public function getTypesCompatibles(): array
    {
        return $this->type->typesCompatibles($this->mimeType);
    }

    public function changerType(TypeMediaEnum $type): static
    {
        if ($type === $this->type) {
            return $this;
        }
        if (!\in_array($type, $this->getTypesCompatibles(), true)) {
            throw new \InvalidArgumentException(sprintf('Cette preuve ne peut pas devenir « %s ».', $type->getLibelle()));
        }
        if ($type === TypeMediaEnum::EMBED && !EmbedPolicy::isAutorise((string) $this->url)) {
            throw new \InvalidArgumentException('Ce site ne fait pas partie des contenus intégrables autorisés.');
        }
        $this->type = $type;
        if ($type !== TypeMediaEnum::CODE) {
            unset($this->metadata['langage']);
        }

        return $this;
    }

    public function changerUrl(string $url): static
    {
        $verifie = self::depuisUrl($this->trace, $this->type, $url);
        $this->url = $verifie->url;

        return $this;
    }

    public function setLangage(?string $langage): static
    {
        if ($this->type === TypeMediaEnum::CODE && $langage !== null && preg_match('/^[a-z0-9+#.-]{1,30}$/i', $langage)) {
            $this->metadata['langage'] = strtolower($langage);
        } else {
            unset($this->metadata['langage']);
        }

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getChemin(): ?string
    {
        return $this->chemin;
    }

    public function hasFichier(): bool
    {
        return $this->chemin !== null;
    }

    public function getNomOriginal(): ?string
    {
        return $this->nomOriginal;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getTaille(): ?int
    {
        return $this->taille;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        if (!\in_array($this->type, [TypeMediaEnum::TEXTE, TypeMediaEnum::CODE], true) || trim($contenu) === '') {
            throw new \InvalidArgumentException('Contenu invalide pour ce média.');
        }
        $this->contenu = $contenu;

        return $this;
    }

    public function getLegende(): ?string
    {
        return $this->legende;
    }

    public function setLegende(?string $legende): static
    {
        $legende = $legende !== null ? trim($legende) : null;
        $this->legende = $legende !== '' && $legende !== null ? mb_substr($legende, 0, 255) : null;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
