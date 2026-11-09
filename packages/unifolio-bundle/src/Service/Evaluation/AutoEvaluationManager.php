<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\AutoEvaluation;
use UnifolioBundle\Entity\GrilleVersion;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Repository\AutoEvaluationRepository;
use UnifolioBundle\Repository\EvaluationRepository;
use UnifolioBundle\Service\Grille\GrilleResolver;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

class AutoEvaluationManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AutoEvaluationRepository $autoEvaluations,
        private readonly GrilleResolver $grilles,
        private readonly ReferentielAnnee $referentiel,
        private readonly EvaluationRepository $evaluations,
    ) {
    }

    /** Grille publiée de la formation pour l'année, ou erreur explicite. */
    public function versionPour(EtudiantScolarite $scolarite): GrilleVersion
    {
        return $this->grilles->grillePourScolarite($scolarite)?->getVersionPubliee()
            ?? throw UnifolioValidationException::un('Votre formation n\'a pas encore de grille d\'évaluation publiée : l\'auto-évaluation n\'est pas disponible.');
    }

    /** Granularité du bilan : celle imposée par la formation, sinon celle de sa grille. */
    public function granularitePour(EtudiantScolarite $scolarite): GranulariteEnum
    {
        return $this->grilles->granularitePourScolarite($scolarite)
            ?? $this->versionPour($scolarite)->getGrille()->getGranularite();
    }

    /** Ouvre (ou retrouve) l'auto-évaluation d'une trace sur un de ses rattachements. */
    public function ouvrirPourRattachement(TraceRattachement $rattachement): AutoEvaluation
    {
        if (($existante = $this->autoEvaluations->findOneByRattachement($rattachement)) !== null) {
            return $existante;
        }
        $scolarite = $rattachement->getTrace()->getScolarite()
            ?? throw UnifolioValidationException::un('Choisissez l\'année de la trace avant de vous auto-évaluer.');

        $auto = $this->garder(fn () => AutoEvaluation::pourRattachement($rattachement, $scolarite, $this->versionPour($scolarite)));
        $this->em->persist($auto);
        $this->em->flush();

        return $auto;
    }

    /** Ouvre (ou retrouve) le bilan de l'étudiant sur un élément du référentiel de son année. */
    public function ouvrirBilan(Etudiant $etudiant, EtudiantScolarite $scolarite, string $type, int $id): AutoEvaluation
    {
        $granularite = $this->granularitePour($scolarite);
        if (GranulariteEnum::tryFrom($type) !== $granularite) {
            throw UnifolioValidationException::un(sprintf('Votre formation fait le bilan par « %s ».', $granularite->getLibelle()));
        }
        if (($existante = $this->autoEvaluations->findBilan($scolarite, $granularite, $id)) !== null) {
            return $existante;
        }

        $cible = match ($granularite) {
            GranulariteEnum::COMPETENCE => $this->em->find(ApcCompetence::class, $id),
            GranulariteEnum::NIVEAU => $this->em->find(ApcNiveau::class, $id),
            GranulariteEnum::AC => $this->em->find(ApcApprentissageCritique::class, $id),
        };
        if ($cible === null || !$this->referentiel->contient($scolarite, $cible)) {
            throw UnifolioValidationException::un('Cet élément ne fait pas partie du référentiel de votre année.');
        }

        $auto = $this->garder(fn () => AutoEvaluation::bilan($etudiant, $scolarite, $this->versionPour($scolarite), $cible));
        $this->em->persist($auto);
        $this->em->flush();

        return $auto;
    }

    /**
     * @param array{reflexion?: ?string, reponses?: mixed} $d reponses : [{ critere, echelon?, valeur?, booleen?, texte? }]
     */
    public function enregistrer(AutoEvaluation $auto, array $d): AutoEvaluation
    {
        $this->garder(function () use ($auto, $d): void {
            if (\array_key_exists('reflexion', $d)) {
                $auto->setReflexion($d['reflexion'] !== null ? (string) $d['reflexion'] : null);
            }
            if (\array_key_exists('reponses', $d)) {
                $saisies = [];
                foreach (\is_array($d['reponses']) ? $d['reponses'] : [] as $r) {
                    if (\is_array($r) && isset($r['critere'])) {
                        $saisies[(string) $r['critere']] = $r;
                    }
                }
                $auto->repondre($saisies);
            }
        });
        $this->em->flush();

        return $auto;
    }

    public function soumettre(AutoEvaluation $auto): void
    {
        $erreurs = $auto->getErreursSoumission();
        if ($erreurs !== []) {
            throw new UnifolioValidationException($erreurs);
        }
        $this->garder(fn () => $auto->soumettre());
        $this->em->flush();
    }

    public function reprendre(AutoEvaluation $auto): void
    {
        $evaluee = $auto->isBilan()
            ? $this->evaluations->existeRendueSurBilan($auto->getScolarite(), $auto->getGranularite(), (int) $auto->getCible()->getId())
            : $this->evaluations->existeRendueSurRattachement($auto->getRattachement());
        if ($evaluee) {
            throw UnifolioValidationException::un('Un enseignant a déjà rendu son évaluation : cette auto-évaluation ne peut plus être modifiée.');
        }
        $auto->reprendre();
        $this->em->flush();
    }

    public function supprimer(AutoEvaluation $auto): void
    {
        $this->em->remove($auto);
        $this->em->flush();
    }

    /**
     * @template T
     *
     * @param callable(): T $fn
     *
     * @return T
     */
    private function garder(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\InvalidArgumentException|\DomainException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
    }
}
