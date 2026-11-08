<?php

namespace StageBundle\Service;

use App\Repository\EtablissementRepository;
use StageBundle\Entity\Stages\StageEtudiant;

/**
 * Règles du Code de l'éducation vérifiées au dépôt et à la validation d'une demande de convention :
 *  - dates cohérentes, et comprises dans la période de stage (sauf période à dates flexibles) ;
 *  - durée hebdomadaire de présence entre 1 et 48 heures ;
 *  - durée totale au plus 6 mois, soit 924 heures (132 jours de 7 h) par année d'enseignement (art. L124-5) ;
 *  - au-delà de 2 mois, soit 308 heures (44 jours de 7 h), gratification obligatoire, au moins égale au minimum légal
 *    (taux horaire configuré dans l'établissement) (art. L124-6, D124-6).
 *
 * Limite : la durée maximale est vérifiée dossier par dossier, pas en cumul de plusieurs stages d'une même année.
 */
class StageReglesLegales
{
    public const HEURES_JOUR = 7;
    public const JOURS_GRATIFICATION = 44;
    public const JOURS_MAX = 132;
    public const HEURES_HEBDO_MAX = 48;

    public function __construct(private readonly EtablissementRepository $etablissements)
    {
    }

    /** @return list<array{champ: string, message: string}> */
    public function verifier(StageEtudiant $stage): array
    {
        $erreurs = [];
        $debut = $stage->getDateDebutStage();
        $fin = $stage->getDateFinStage();

        if ($debut === null || $fin === null) {
            $erreurs[] = ['champ' => 'dateDebutStage', 'message' => 'Les dates de début et de fin du stage sont obligatoires.'];
        } elseif ($fin < $debut) {
            $erreurs[] = ['champ' => 'dateFinStage', 'message' => 'La date de fin du stage précède sa date de début.'];
        } else {
            $periode = $stage->getStagePeriode();
            if ($periode !== null && !$periode->isDatesFlexibles() && $periode->getDateDebut() !== null && $periode->getDateFin() !== null
                && ($debut->format('Y-m-d') < $periode->getDateDebut()->format('Y-m-d') || $fin->format('Y-m-d') > $periode->getDateFin()->format('Y-m-d'))) {
                $erreurs[] = ['champ' => 'dateDebutStage', 'message' => sprintf(
                    'Le stage doit se dérouler entre le %s et le %s (dates de la période « %s »).',
                    $periode->getDateDebut()->format('d/m/Y'),
                    $periode->getDateFin()->format('d/m/Y'),
                    $periode->getLibelle()
                )];
            }
        }

        $hebdo = $stage->getDureeHebdomadaire();
        if ($hebdo <= 0 || $hebdo > self::HEURES_HEBDO_MAX) {
            $erreurs[] = ['champ' => 'dureeHebdomadaire', 'message' => sprintf('La durée hebdomadaire de présence doit être comprise entre 1 et %d heures.', self::HEURES_HEBDO_MAX)];
        }

        $jours = $this->joursDePresence($stage);
        if ($jours > self::JOURS_MAX) {
            $erreurs[] = ['champ' => 'dureeJoursStage', 'message' => sprintf(
                'Un stage ne peut pas dépasser 6 mois (%d jours, soit %d heures de présence) par année d\'enseignement : %d jours prévus.',
                self::JOURS_MAX, self::JOURS_MAX * self::HEURES_JOUR, $jours
            )];
        }

        if ($jours > self::JOURS_GRATIFICATION) {
            $minimum = $this->tauxHoraireMinimal();
            $montant = $stage->getGratificationMontant() ?? 0.0;
            $requis = match ($stage->getGratificationPeriode()) {
                'M' => $minimum * $hebdo * 52 / 12,
                'J' => $minimum * self::HEURES_JOUR,
                default => $minimum,
            };
            if (!$stage->isGratification() || $montant + 0.001 < $requis) {
                $erreurs[] = ['champ' => 'gratificationMontant', 'message' => sprintf(
                    'Au-delà de 2 mois de stage (%d jours de présence), la gratification est obligatoire : au moins %s € par %s (%d jours prévus).',
                    self::JOURS_GRATIFICATION,
                    number_format($requis, 2, ',', ' '),
                    match ($stage->getGratificationPeriode()) { 'M' => 'mois', 'J' => 'jour', default => 'heure' },
                    $jours
                )];
            }
        }

        return $erreurs;
    }

    /** Jours de présence déclarés, ou à défaut estimés d'après les dates (jours ouvrés). */
    public function joursDePresence(StageEtudiant $stage): int
    {
        if ($stage->getDureeJoursStage() > 0) {
            return $stage->getDureeJoursStage();
        }
        $debut = $stage->getDateDebutStage();
        $fin = $stage->getDateFinStage();
        if ($debut === null || $fin === null || $fin < $debut) {
            return 0;
        }
        $jours = 0;
        $courant = \DateTimeImmutable::createFromInterface($debut)->setTime(0, 0);
        $dernier = \DateTimeImmutable::createFromInterface($fin)->setTime(0, 0);
        while ($courant <= $dernier) {
            if ((int) $courant->format('N') <= 5) {
                ++$jours;
            }
            $courant = $courant->modify('+1 day');
        }

        return $jours;
    }

    private function tauxHoraireMinimal(): float
    {
        $etab = $this->etablissements->findPrincipal();

        return $etab?->getGratificationMinimale() ?? 4.35;
    }
}
