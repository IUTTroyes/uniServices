<?php

namespace UnifolioBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\TypeCritereEnum;

/**
 * Réponse à un critère de grille. Partagée par l'auto-évaluation (lot 3) et
 * l'évaluation enseignante (lot 4) : la valeur renseignée dépend du type du critère.
 */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_reponse_critere')]
class ReponseCritere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?AutoEvaluation $autoEvaluation = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Evaluation $evaluation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private GrilleCritere $critere;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?GrilleEchelon $echelon = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $valeurNumerique = null;

    #[ORM\Column(nullable: true)]
    private ?bool $valeurBooleenne = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $texte = null;

    private function __construct(GrilleCritere $critere)
    {
        $this->critere = $critere;
    }

    /**
     * Construit une réponse validée selon le type du critère ; null si la réponse est vide.
     *
     * @param array{echelon?: mixed, valeur?: mixed, booleen?: mixed, texte?: mixed} $saisie
     */
    public static function depuisSaisie(GrilleCritere $critere, array $saisie): ?self
    {
        $reponse = new self($critere);
        switch ($critere->getType()) {
            case TypeCritereEnum::ECHELLE:
                if (($saisie['echelon'] ?? null) === null || $saisie['echelon'] === '') {
                    return null;
                }
                foreach ($critere->getVersion()->getEchelons() as $echelon) {
                    if ((string) $echelon->getId() === (string) $saisie['echelon']) {
                        $reponse->echelon = $echelon;

                        return $reponse;
                    }
                }
                throw new \InvalidArgumentException(sprintf('Niveau d\'échelle inconnu pour « %s ».', $critere->getLibelle()));

            case TypeCritereEnum::BOOLEEN:
                if (!isset($saisie['booleen']) || $saisie['booleen'] === '') {
                    return null;
                }
                $reponse->valeurBooleenne = (bool) $saisie['booleen'];

                return $reponse;

            case TypeCritereEnum::NOTE:
                if (!isset($saisie['valeur']) || $saisie['valeur'] === '') {
                    return null;
                }
                $valeur = (float) $saisie['valeur'];
                if ($valeur < 0 || $valeur > (float) $critere->getNoteMax()) {
                    throw new \InvalidArgumentException(sprintf('« %s » : la note doit être comprise entre 0 et %s.', $critere->getLibelle(), $critere->getNoteMax()));
                }
                $reponse->valeurNumerique = $valeur;

                return $reponse;

            case TypeCritereEnum::TEXTE:
                $texte = isset($saisie['texte']) ? trim((string) $saisie['texte']) : '';
                if ($texte === '') {
                    return null;
                }
                $reponse->texte = $texte;

                return $reponse;
        }

        return null;
    }

    public function attacherA(AutoEvaluation|Evaluation $parent): void
    {
        if ($parent instanceof AutoEvaluation) {
            $this->autoEvaluation = $parent;
            $this->evaluation = null;
        } else {
            $this->evaluation = $parent;
            $this->autoEvaluation = null;
        }
    }

    /**
     * Construit les réponses d'une saisie pour un ensemble de critères (indexés ou non).
     *
     * @param GrilleCritere[]                        $criteres critères acceptés
     * @param array<int|string, array<string, mixed>> $saisies  indexées par identifiant de critère
     *
     * @return ReponseCritere[]
     *
     * @throws \InvalidArgumentException critère inconnu ou valeur invalide
     */
    public static function depuisSaisies(array $criteres, array $saisies): array
    {
        $parId = [];
        foreach ($criteres as $critere) {
            $parId[(string) $critere->getId()] = $critere;
        }
        foreach (array_keys($saisies) as $id) {
            if (!isset($parId[(string) $id])) {
                throw new \InvalidArgumentException('Réponse à un critère qui ne fait pas partie de la grille.');
            }
        }
        $reponses = [];
        foreach ($saisies as $id => $saisie) {
            $reponse = self::depuisSaisie($parId[(string) $id], $saisie);
            if ($reponse !== null) {
                $reponses[] = $reponse;
            }
        }

        return $reponses;
    }

    /**
     * Moyenne pondérée des réponses quantifiables, entre 0 et 1 ; null si aucune.
     *
     * @param ReponseCritere[] $reponses
     */
    public static function calculerScore(array $reponses): ?float
    {
        $total = 0.0;
        $poids = 0.0;
        foreach ($reponses as $reponse) {
            $valeur = $reponse->getValeurNormalisee();
            $p = $reponse->getCritere()->getPoids();
            if ($valeur !== null && $p > 0) {
                $total += $valeur * $p;
                $poids += $p;
            }
        }

        return $poids > 0 ? round($total / $poids, 4) : null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCritere(): GrilleCritere
    {
        return $this->critere;
    }

    public function getEchelon(): ?GrilleEchelon
    {
        return $this->echelon;
    }

    public function getValeurNumerique(): ?float
    {
        return $this->valeurNumerique;
    }

    public function getValeurBooleenne(): ?bool
    {
        return $this->valeurBooleenne;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    /** Valeur ramenée entre 0 et 1 pour le calcul du score ; null pour une appréciation libre. */
    public function getValeurNormalisee(): ?float
    {
        return match ($this->critere->getType()) {
            TypeCritereEnum::ECHELLE => $this->normaliserEchelon(),
            TypeCritereEnum::BOOLEEN => $this->valeurBooleenne === null ? null : ($this->valeurBooleenne ? 1.0 : 0.0),
            TypeCritereEnum::NOTE => $this->valeurNumerique !== null && $this->critere->getNoteMax() > 0
                ? $this->valeurNumerique / $this->critere->getNoteMax()
                : null,
            TypeCritereEnum::TEXTE => null,
        };
    }

    private function normaliserEchelon(): ?float
    {
        if ($this->echelon === null) {
            return null;
        }
        $valeurs = array_map(static fn (GrilleEchelon $e) => $e->getValeur(), $this->critere->getVersion()->getEchelons()->toArray());
        $min = min($valeurs);
        $max = max($valeurs);

        return $max > $min ? ($this->echelon->getValeur() - $min) / ($max - $min) : 1.0;
    }
}
