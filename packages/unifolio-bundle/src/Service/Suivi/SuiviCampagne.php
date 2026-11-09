<?php

namespace UnifolioBundle\Service\Suivi;

use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Service\Campagne\CampagneEtudiants;
use UnifolioBundle\Service\Campagne\CampagneNormalizer;

/**
 * Tableau de bord d'une campagne : matrice étudiants × compétences, indicateurs de promo, alertes par étudiant.
 * Le périmètre (tous les étudiants ou seulement ceux affectés à l'enseignant) est décidé par l'appelant.
 */
class SuiviCampagne
{
    public function __construct(
        private readonly CampagneEtudiants $etudiants,
        private readonly SuiviCalculator $calculateur,
        private readonly CampagneNormalizer $normalizer,
    ) {
    }

    /**
     * @param list<int>|null $etudiantIds null = toute la campagne
     *
     * @return array<string, mixed>
     */
    public function construire(Campagne $campagne, ?array $etudiantIds = null): array
    {
        $maintenant = new \DateTimeImmutable();
        $enRetardGlobal = $campagne->isEvaluationEnRetard($maintenant);
        $debutee = $campagne->getOuvertureLe() <= $maintenant;
        $colonnes = [];
        $lignes = [];
        $totaux = SuiviCalculator::resumeVide() + ['avecDepot' => 0, 'ecartsNotables' => 0, 'sansDepot' => 0];
        $indisponible = null;

        foreach ($this->etudiants->scolarites($campagne) as $id => $scolarite) {
            if ($etudiantIds !== null && !\in_array($id, $etudiantIds, true)) {
                continue;
            }
            $suivi = $this->calculateur->pourScolarite($scolarite, true);
            $etudiant = $scolarite->getEtudiant();
            if (!$suivi['disponible']) {
                $indisponible = $suivi['message'];
                $lignes[] = ['etudiant' => ['id' => $id, 'nom' => $etudiant->getNom(), 'prenom' => $etudiant->getPrenom()], 'cellules' => [], 'resume' => $suivi['resume'], 'alertes' => []];
                continue;
            }

            $cellules = [];
            foreach ($suivi['competences'] as $c) {
                $colonnes[(int) $c['id']] ??= ['id' => $c['id'], 'nomCourt' => $c['nomCourt'], 'libelle' => $c['libelle'], 'couleur' => $c['couleur'], 'total' => $c['total']];
                $cellules[(int) $c['id']] = array_intersect_key($c, array_flip(['total', 'couverts', 'nbTraces', 'evalScore', 'autoScore']));
            }
            $r = $suivi['resume'];
            $alertes = [];
            if ($debutee && $r['nbTraces'] === 0) {
                $alertes[] = 'sans_depot';
                ++$totaux['sansDepot'];
            }
            if ($r['aEvaluer'] > 0) {
                $alertes[] = $enRetardGlobal ? 'evaluation_en_retard' : 'a_evaluer';
            }
            if ($r['ecartMax'] !== null && $r['ecartMax'] >= SuiviCalculator::ECART_NOTABLE) {
                $alertes[] = 'ecart_notable';
                ++$totaux['ecartsNotables'];
            }
            $totaux['avecDepot'] += $r['nbTraces'] > 0 ? 1 : 0;
            foreach (['total', 'couverts', 'nbTraces', 'aEvaluer'] as $k) {
                $totaux[$k] += $r[$k];
            }

            $lignes[] = [
                'etudiant' => ['id' => $id, 'nom' => $etudiant->getNom(), 'prenom' => $etudiant->getPrenom()],
                'cellules' => $cellules,
                'resume' => $r,
                'alertes' => $alertes,
            ];
        }

        $n = \count($lignes);

        return [
            'campagne' => $this->normalizer->resume($campagne),
            'disponible' => $indisponible === null || $colonnes !== [],
            'message' => $indisponible,
            'competences' => array_values($colonnes),
            'lignes' => $lignes,
            'indicateurs' => [
                'nbEtudiants' => $n,
                'tauxDepot' => $n > 0 ? round($totaux['avecDepot'] / $n, 4) : null,
                'tauxCouverture' => $totaux['total'] > 0 ? round($totaux['couverts'] / $totaux['total'], 4) : null,
                'sansDepot' => $totaux['sansDepot'],
                'aEvaluer' => $totaux['aEvaluer'],
                'enRetard' => $enRetardGlobal ? $totaux['aEvaluer'] : 0,
                'ecartsNotables' => $totaux['ecartsNotables'],
            ],
        ];
    }

    /** CSV (séparateur « ; », UTF-8 avec BOM pour Excel). Les cellules texte sont neutralisées contre l'injection de formules. */
    public function csv(array $suivi): string
    {
        $pct = static fn (?float $v) => $v === null ? '' : (string) round($v * 100);
        $entete = ['Nom', 'Prénom'];
        foreach ($suivi['competences'] as $c) {
            array_push($entete, $c['nomCourt'].' — couverts', $c['nomCourt'].' — évaluation (%)', $c['nomCourt'].' — auto-évaluation (%)');
        }
        array_push($entete, 'Traces déposées', 'À évaluer', 'Alertes');
        $sortie = [$entete];
        foreach ($suivi['lignes'] as $l) {
            $ligne = [$l['etudiant']['nom'], $l['etudiant']['prenom']];
            foreach ($suivi['competences'] as $c) {
                $cell = $l['cellules'][(int) $c['id']] ?? null;
                array_push($ligne, $cell !== null ? $cell['couverts'].'/'.$cell['total'] : '', $cell !== null ? $pct($cell['evalScore']) : '', $cell !== null ? $pct($cell['autoScore']) : '');
            }
            array_push($ligne, $l['resume']['nbTraces'], $l['resume']['aEvaluer'], implode(', ', $l['alertes']));
            $sortie[] = $ligne;
        }

        $flux = fopen('php://temp', 'r+');
        fwrite($flux, "\xEF\xBB\xBF");
        foreach ($sortie as $ligne) {
            fputcsv($flux, array_map(self::neutraliser(...), $ligne), ';', '"', '\\');
        }
        rewind($flux);
        $contenu = (string) stream_get_contents($flux);
        fclose($flux);

        return $contenu;
    }

    private static function neutraliser(mixed $valeur): string
    {
        $texte = (string) $valeur;

        return $texte !== '' && str_contains("=+-@\t\r", $texte[0]) ? "'".$texte : $texte;
    }
}
