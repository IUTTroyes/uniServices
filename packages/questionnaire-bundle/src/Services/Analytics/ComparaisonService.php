<?php

namespace QuestionnaireBundle\Services\Analytics;

use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Security\QuestionnaireAccess;

/**
 * Comparaison des campagnes d'un même modèle (une par année universitaire, en général) : participation et, pour chaque
 * question d'origine commune, la moyenne (échelles) ou la répartition des réponses (choix). Les sections répétées sont
 * agrégées ; une campagne ou une section sous le seuil d'anonymat ne contribue pas.
 */
class ComparaisonService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuestionnaireAnalyticsService $analytics,
        private readonly QuestionnaireAccess $access,
    ) {
    }

    /** @return array{modele: ?array{uuid: string, title: ?string}, campagnes: list<array<string, mixed>>, questions: list<array<string, mixed>>} */
    public function comparer(Questionnaire $q, mixed $user): array
    {
        $modele = $q->isEstModele() ? $q : $q->getModeleOrigine();
        $campagnes = $modele === null ? [$q] : $this->em->getRepository(Questionnaire::class)->createQueryBuilder('c')
            ->leftJoin('c.anneeUniversitaire', 'a')
            ->where('c.modeleOrigine = :modele')->setParameter('modele', $modele)
            ->andWhere('c.estModele = false')
            ->andWhere('c.status != :brouillon')->setParameter('brouillon', QuestStatutEnum::DRAFT)
            ->orderBy('a.annee', 'ASC')->addOrderBy('c.publishedAt', 'ASC')
            ->getQuery()->getResult();
        $campagnes = array_values(array_filter($campagnes, fn (Questionnaire $c) => $this->access->peutVoir($user, $c)));

        $resumeCampagnes = [];
        /** @var array<string, string> $libelles */
        $libelles = [];
        /** @var array<string, string> $types */
        $types = [];
        /** @var array<string, array<int, int>> $n */
        $n = [];
        /** @var array<string, array<int, float>> $sommes */
        $sommes = [];
        /** @var array<string, array<int, array<string, int>>> $choixParCle */
        $choixParCle = [];
        foreach ($campagnes as $index => $c) {
            $stats = $this->analytics->getAnalytics($c);
            $resumeCampagnes[] = [
                'uuid' => $c->getUuidString(),
                'title' => $c->getTitle(),
                'annee' => $c->getAnneeUniversitaire()?->getLibelle(),
                'invites' => $stats->totalInvited,
                'reponses' => $stats->totalResponses,
                'taux' => round($stats->completionRate, 1),
                'seuilAtteint' => $stats->seuilAtteint,
            ];
            if (!$stats->seuilAtteint) {
                continue;
            }
            $cles = $this->clesDesQuestions($c);
            foreach ($stats->sections as $section) {
                if ($section->masquee) {
                    continue;
                }
                foreach ($section->questions as $qs) {
                    $cle = $cles[(int) $qs->questionId] ?? null;
                    if ($cle === null) {
                        continue;
                    }
                    $libelles[$cle] = $qs->questionLabel; // libellé le plus récent
                    $types[$cle] = $qs->questionType;
                    $n[$cle][$index] = ($n[$cle][$index] ?? 0) + $qs->totalResponses;
                    if (isset($qs->stats['average'])) {
                        $sommes[$cle][$index] = ($sommes[$cle][$index] ?? 0.0) + $qs->stats['average'] * $qs->totalResponses;
                    }
                    foreach ($qs->stats['choices'] ?? [] as $choix) {
                        $choixParCle[$cle][$index][$choix['text']] = ($choixParCle[$cle][$index][$choix['text']] ?? 0) + $choix['count'];
                    }
                }
            }
        }

        // Une valeur par campagne (null si la question n'y figure pas ou n'a pas de réponse affichable).
        $sortie = [];
        foreach ($libelles as $cle => $libelle) {
            $valeurs = [];
            foreach (array_keys($resumeCampagnes) as $index) {
                $nb = $n[$cle][$index] ?? 0;
                if ($nb === 0) {
                    $valeurs[] = null;
                    continue;
                }
                $choix = $choixParCle[$cle][$index] ?? [];
                $total = array_sum($choix);
                $valeurs[] = [
                    'n' => $nb,
                    'moyenne' => isset($sommes[$cle][$index]) ? round($sommes[$cle][$index] / $nb, 2) : null,
                    'choix' => $total > 0 ? array_map(static fn (int $c) => round($c / $total * 100, 1), $choix) : null,
                ];
            }
            $sortie[] = ['cle' => $cle, 'label' => $libelle, 'type' => $types[$cle], 'valeurs' => $valeurs];
        }

        return [
            'modele' => $modele ? ['uuid' => $modele->getUuidString(), 'title' => $modele->getTitle()] : null,
            'campagnes' => $resumeCampagnes,
            'questions' => $sortie,
        ];
    }

    /** @return array<int, string> identifiant de question → clé de comparaison */
    private function clesDesQuestions(Questionnaire $q): array
    {
        $cles = [];
        foreach ($q->getSections() as $section) {
            foreach ($section->getQuestions() as $question) {
                /** @var QuestionnaireQuestion $question */
                $cles[(int) $question->getId()] = $question->cleComparaison();
            }
        }

        return $cles;
    }
}
