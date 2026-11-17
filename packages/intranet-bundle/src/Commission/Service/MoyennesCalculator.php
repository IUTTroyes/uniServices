<?php

declare(strict_types=1);

namespace IntranetBundle\Commission\Service;

use App\Entity\Etudiant\EtudiantNote;

final class MoyennesCalculator
{
    public const REGLES = ['version' => '1', 'absent_injustifie' => 'zero', 'absent_justifie' => 'exclu', 'dispense' => 'exclu', 'manquante' => 'bloque_validation', 'arrondi' => 3];

    /** @return array{array, array, array} Moyennes UE, enseignements et erreurs bloquantes. */
    public function calculer(array $sources): array
    {
        $erreurs = $moyennesUe = $moyennesEnseignement = $notes = $evaluations = $enseignements = [];
        if ($sources['ues'] === []) $erreurs[] = 'Aucune UE active dans le semestre.';
        if ($sources['inscriptions'] === []) $erreurs[] = 'Aucune inscription dans le semestre.';
        foreach ($sources['notes'] as $note) {
            $key = $note['inscription'].':'.$note['evaluation'];
            if (isset($notes[$key])) $erreurs[] = 'Notes dupliquées pour '.$key.'.';
            $notes[$key] = $note;
        }
        foreach ($sources['evaluations'] as $evaluation) {
            $evaluations[$evaluation['enseignement']][] = $evaluation;
            if ($evaluation['anneeUniversitaire'] !== $sources['anneeUniversitaireId']) $erreurs[] = 'Année incohérente pour l’évaluation '.$evaluation['id'].'.';
        }
        foreach ($sources['ues'] as $ue) {
            foreach ($ue['liens'] as $lien) {
                if ($lien['enseignement'] !== null) $enseignements[$lien['enseignement']] = true;
                if ($lien['coefficient'] < 0) $erreurs[] = 'Coefficient négatif dans l’UE '.$ue['id'].'.';
            }
            if ($ue['coefficient'] < 0 || $ue['ects'] < 0) $erreurs[] = 'Poids négatif pour l’UE '.$ue['id'].'.';
        }
        foreach ($sources['inscriptions'] as $inscription) {
            $sid = $inscription['id'];
            if ($inscription['anneeUniversitaire'] !== $sources['anneeUniversitaireId']) $erreurs[] = 'Année incohérente pour l’inscription '.$sid.'.';
            foreach ($enseignements as $eid => $_) {
                $somme = $total = 0.0;
                $complet = true;
                if (!isset($evaluations[$eid])) $erreurs[] = 'Aucune évaluation pour l’enseignement '.$eid.'.';
                foreach ($evaluations[$eid] ?? [] as $evaluation) {
                    $note = $notes[$sid.':'.$evaluation['id']] ?? null;
                    if ($note === null && $evaluation['typeGroupe'] !== null && !in_array($evaluation['typeGroupe'], $inscription['typesGroupes'], true)) continue;
                    $poids = $evaluation['coeff'];
                    if ($poids === null || $poids < 0) {
                        $erreurs[] = 'Coefficient invalide pour l’évaluation '.$evaluation['id'].'.';
                        $complet = false;
                        continue;
                    }
                    if ((float) $poids === 0.0) continue;
                    if ($note === null || ($note['presenceStatut'] === EtudiantNote::STATUT_PRESENT && $note['note'] === null)) {
                        $erreurs[] = 'Note manquante : inscription '.$sid.', évaluation '.$evaluation['id'].'.';
                        $complet = false;
                        continue;
                    }
                    if (in_array($note['presenceStatut'], [EtudiantNote::STATUT_ABSENT_JUSTIFIE, EtudiantNote::STATUT_DISPENSE], true)) continue;
                    if (!in_array($note['presenceStatut'], [EtudiantNote::STATUT_PRESENT, EtudiantNote::STATUT_ABSENT_INJUSTIFIE], true)) {
                        $erreurs[] = 'Statut invalide pour la note '.$note['id'].'.';
                        $complet = false;
                        continue;
                    }
                    $valeur = $note['presenceStatut'] === EtudiantNote::STATUT_ABSENT_INJUSTIFIE ? 0.0 : $note['note'];
                    if ($valeur < 0 || $valeur > 20) {
                        $erreurs[] = 'Valeur invalide pour la note '.$note['id'].'.';
                        $complet = false;
                        continue;
                    }
                    $somme += $valeur * $poids;
                    $total += $poids;
                }
                $moyennesEnseignement[$sid][$eid] = $complet && $total > 0 ? round($somme / $total, 3) : null;
            }
            foreach ($sources['ues'] as $ue) {
                $somme = $total = 0.0;
                $complet = true;
                foreach ($ue['liens'] as $lien) {
                    if ($lien['coefficient'] <= 0) continue;
                    $moyenne = $moyennesEnseignement[$sid][$lien['enseignement']] ?? null;
                    if ($moyenne === null) { $complet = false; continue; }
                    $somme += $moyenne * $lien['coefficient'];
                    $total += $lien['coefficient'];
                }
                $moyennesUe[$sid][$ue['id']] = $complet && $total > 0 ? round($somme / $total, 3) : null;
            }
        }
        return [$moyennesUe, $moyennesEnseignement, array_values(array_unique($erreurs))];
    }
}
