<?php

declare(strict_types=1);

namespace IntranetBundle\Entity\Enseignement;

use ApiPlatform\Metadata\{ApiResource, GetCollection, Post, QueryParameter};
use IntranetBundle\Filter\Enseignement\AvancementEnseignementFilter;
use IntranetBundle\Dto\Enseignement\{AvancementEnseignementListeDto, AvancementEnseignementHistoriqueDto, AvancementEnseignementRevisionDto, AvancementEnseignementSaisieDto};
use IntranetBundle\State\Provider\Enseignement\{AvancementEnseignementProvider, AvancementEnseignementHistoriqueProvider};
use IntranetBundle\State\Processor\Enseignement\AvancementEnseignementProcessor;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\{StructureAnneeUniversitaire, StructureGroupe};
use App\Entity\Users\Personnel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Une révision immuable : le dernier repère et son historique partagent le même périmètre. */
#[ORM\Entity]
#[ORM\Table(name: 'intranet_avancement_enseignement')]
#[ORM\Index(name: 'idx_avancement_perimetre', columns: ['enseignement_id', 'annee_universitaire_id', 'groupe_id', 'id'])]
#[ApiResource(
    operations: [
        new GetCollection(
            name: 'intranet_avancement_liste',
            uriTemplate: '/avancement_enseignements',
            output: AvancementEnseignementListeDto::class,
            provider: AvancementEnseignementProvider::class,
        ),
        new GetCollection(
            name: 'intranet_avancement_historique',
            uriTemplate: '/historique/avancement_enseignements',
            parameters: [
                'enseignement' => new QueryParameter(filter: AvancementEnseignementFilter::class, required: true, schema: ['type' => 'integer', 'minimum' => 1]),
                'anneeUniversitaire' => new QueryParameter(filter: AvancementEnseignementFilter::class, schema: ['type' => 'integer', 'minimum' => 1]),
                'groupe' => new QueryParameter(filter: AvancementEnseignementFilter::class, required: true, schema: ['type' => 'integer', 'minimum' => 1]),
                'avant' => new QueryParameter(filter: AvancementEnseignementFilter::class, schema: ['type' => 'integer', 'minimum' => 1]),
            ],
            output: AvancementEnseignementHistoriqueDto::class,
            provider: AvancementEnseignementHistoriqueProvider::class,
        ),
        new Post(
            name: 'intranet_avancement_enregistrer',
            uriTemplate: '/avancement_enseignements',
            parameters: [
                'enseignement' => new QueryParameter(filter: AvancementEnseignementFilter::class, required: true, schema: ['type' => 'integer', 'minimum' => 1]),
                'anneeUniversitaire' => new QueryParameter(filter: AvancementEnseignementFilter::class, schema: ['type' => 'integer', 'minimum' => 1]),
                'groupe' => new QueryParameter(filter: AvancementEnseignementFilter::class, required: true, schema: ['type' => 'integer', 'minimum' => 1]),
            ],
            input: AvancementEnseignementSaisieDto::class,
            output: AvancementEnseignementRevisionDto::class,
            read: false,
            processor: AvancementEnseignementProcessor::class,
        ),
    ],
    formats: ['json' => ['application/json']],
    security: "is_granted('IS_PERSONNEL')",
    paginationEnabled: false,
    cacheHeaders: ['max_age' => 0, 'shared_max_age' => 0, 'no_store' => true, 'public' => false],
    parameters: [
        'enseignement' => new QueryParameter(filter: AvancementEnseignementFilter::class, required: true, schema: ['type' => 'integer', 'minimum' => 1]),
        'anneeUniversitaire' => new QueryParameter(filter: AvancementEnseignementFilter::class, schema: ['type' => 'integer', 'minimum' => 1]),
    ],
)]
class AvancementEnseignement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ScolEnseignement $enseignement;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureGroupe $groupe;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureAnneeUniversitaire $anneeUniversitaire;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Personnel $auteur;

    #[ORM\Column(length: 255)]
    private string $auteurLibelle;

    #[ORM\Column(type: Types::TEXT)]
    private string $repere;

    #[ORM\Column(type: Types::TEXT)]
    private string $prochaineEtape;

    // Copie indépendante du plan : une modification/suppression de séquence ne réécrit pas l'historique.
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $sequenceSnapshot = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $etatSequence = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(ScolEnseignement $enseignement, StructureGroupe $groupe, StructureAnneeUniversitaire $annee, Personnel $auteur, string $repere, string $prochaineEtape, ?array $sequence = null, ?string $etatSequence = null)
    {
        $this->enseignement = $enseignement;
        $this->groupe = $groupe;
        $this->anneeUniversitaire = $annee;
        $this->auteur = $auteur;
        $this->auteurLibelle = $auteur->getDisplay();
        $this->repere = $repere;
        $this->prochaineEtape = $prochaineEtape;
        $this->sequenceSnapshot = $sequence;
        $this->etatSequence = $etatSequence;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getGroupe(): StructureGroupe { return $this->groupe; }
    public function getRepere(): string { return $this->repere; }
    public function getProchaineEtape(): string { return $this->prochaineEtape; }

    public function getSequenceSnapshot(): ?array { return $this->sequenceSnapshot; }
    public function getEtatSequence(): ?string { return $this->etatSequence; }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'repere' => $this->repere, 'prochaineEtape' => $this->prochaineEtape,
            'sequence' => $this->sequenceSnapshot, 'etatSequence' => $this->etatSequence,
            'auteur' => $this->auteurLibelle, 'date' => $this->createdAt->format(DATE_ATOM),
        ];
    }
}
