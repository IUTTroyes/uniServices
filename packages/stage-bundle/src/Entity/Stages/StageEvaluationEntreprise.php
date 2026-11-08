<?php

namespace StageBundle\Entity\Stages;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use StageBundle\Repository\Stages\StageEvaluationEntrepriseRepository;

/**
 * Évaluation du stagiaire par son tuteur en entreprise, saisie sans compte via un lien à usage unique envoyé par e-mail.
 * Seule l'empreinte (SHA-256) du jeton est conservée ; le jeton lui-même n'existe que dans l'e-mail.
 * Les réponses gardent une copie du libellé de chaque critère (la grille de la période peut évoluer ensuite).
 */
#[ORM\Entity(repositoryClass: StageEvaluationEntrepriseRepository::class)]
#[ORM\Table(name: 'stage_evaluation_entreprise')]
class StageEvaluationEntreprise
{
    public const DUREE_VALIDITE_JOURS = 30;
    public const NIVEAUX = [1 => 'Insuffisant', 2 => 'Passable', 3 => 'Satisfaisant', 4 => 'Très satisfaisant'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: StageEtudiant::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private StageEtudiant $stageEtudiant;

    #[ORM\Column(length: 64, unique: true)]
    private string $jetonHash;

    #[ORM\Column(length: 255)]
    private string $envoyeA;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $envoyeLe;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $expireLe;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $soumisLe = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $evaluateurNom = null;

    /** @var list<array{critere: int, libelle: string, niveau: int, commentaire: ?string}>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $reponses = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $appreciation = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $noteProposee = null;

    public function __construct(StageEtudiant $stageEtudiant)
    {
        $this->stageEtudiant = $stageEtudiant;
    }

    /**
     * (Ré)émet un lien : nouveau jeton, nouvelle échéance ; l'ancien lien cesse de fonctionner.
     *
     * @return string le jeton en clair, à placer dans l'e-mail (jamais stocké)
     */
    public function emettreJeton(string $destinataire): string
    {
        $jeton = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->jetonHash = self::empreinte($jeton);
        $this->envoyeA = $destinataire;
        $this->envoyeLe = new \DateTime();
        $this->expireLe = new \DateTime(sprintf('+%d days', self::DUREE_VALIDITE_JOURS));

        return $jeton;
    }

    public static function empreinte(string $jeton): string
    {
        return hash('sha256', $jeton);
    }

    public function estExpire(): bool
    {
        return $this->expireLe < new \DateTime();
    }

    public function estSoumise(): bool
    {
        return $this->soumisLe !== null;
    }

    /** @param list<array{critere: int, libelle: string, niveau: int, commentaire: ?string}> $reponses */
    public function soumettre(array $reponses, ?string $appreciation, ?float $noteProposee, ?string $evaluateurNom): void
    {
        $this->reponses = $reponses;
        $this->appreciation = $appreciation;
        $this->noteProposee = $noteProposee;
        $this->evaluateurNom = $evaluateurNom;
        $this->soumisLe = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStageEtudiant(): StageEtudiant
    {
        return $this->stageEtudiant;
    }

    public function getEnvoyeA(): string
    {
        return $this->envoyeA;
    }

    public function getEnvoyeLe(): \DateTimeInterface
    {
        return $this->envoyeLe;
    }

    public function getExpireLe(): \DateTimeInterface
    {
        return $this->expireLe;
    }

    public function getSoumisLe(): ?\DateTimeInterface
    {
        return $this->soumisLe;
    }

    public function getEvaluateurNom(): ?string
    {
        return $this->evaluateurNom;
    }

    /** @return list<array{critere: int, libelle: string, niveau: int, commentaire: ?string}> */
    public function getReponses(): array
    {
        return $this->reponses ?? [];
    }

    public function getAppreciation(): ?string
    {
        return $this->appreciation;
    }

    public function getNoteProposee(): ?float
    {
        return $this->noteProposee;
    }
}
