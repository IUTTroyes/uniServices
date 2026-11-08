<?php

namespace StageBundle\Enum;

/**
 * Cycle de vie d'un dossier de stage.
 *
 *   INCOMPLET ⇄ AUTORISE → DEPOSE → VALIDE → CONVENTION_IMPRIMEE → CONVENTION_ENVOYEE → CONVENTION_RECUE
 *                             ↘ REFUS ↗ (l'étudiant corrige et redépose)
 *
 * On peut revenir d'une étape en arrière (erreur de saisie) ; l'étudiant qui modifie sa demande la repasse « déposée »
 * (nouvelle validation) ; un responsable peut réinitialiser un dossier (AUTORISE / INCOMPLET) tant que la convention
 * n'est pas signée. Une convention signée ne se modifie plus que par avenant (ou en revenant à « envoyée »).
 */
enum EtatStageEnum: string
{
    case AUTORISE = 'ETAT_STAGE_AUTORISE';
    case DEPOSE = 'ETAT_STAGE_DEPOSE';
    case VALIDE = 'ETAT_STAGE_VALIDE';
    case REFUS = 'ETAT_STAGE_REFUS';
    case INCOMPLET = 'ETAT_STAGE_INCOMPLET';
    case CONVENTION_IMPRIMEE = 'ETAT_STAGE_CONVENTION_IMPRIMEE';
    case CONVENTION_ENVOYEE = 'ETAT_STAGE_CONVENTION_ENVOYEE';
    case CONVENTION_RECUE = 'ETAT_STAGE_CONVENTION_RECUE';

    public function getLibelle(): string
    {
        return match ($this) {
            self::AUTORISE => 'Saisie autorisée',
            self::INCOMPLET => 'Saisie fermée',
            self::DEPOSE => 'En attente de validation',
            self::VALIDE => 'Validée',
            self::REFUS => 'Refusée',
            self::CONVENTION_IMPRIMEE => 'Convention éditée',
            self::CONVENTION_ENVOYEE => 'En cours de signature',
            self::CONVENTION_RECUE => 'Signée',
        };
    }

    /** @return list<self> états atteignables depuis celui-ci (hors état courant) */
    public function transitionsPossibles(): array
    {
        $reinitialisation = [self::AUTORISE, self::INCOMPLET];

        return match ($this) {
            self::INCOMPLET => [self::AUTORISE],
            self::AUTORISE => [self::DEPOSE, self::INCOMPLET],
            self::DEPOSE => [self::VALIDE, self::REFUS, ...$reinitialisation],
            self::REFUS => [self::DEPOSE, ...$reinitialisation],
            self::VALIDE => [self::CONVENTION_IMPRIMEE, self::CONVENTION_ENVOYEE, self::DEPOSE, self::REFUS, ...$reinitialisation],
            self::CONVENTION_IMPRIMEE => [self::CONVENTION_ENVOYEE, self::VALIDE, self::DEPOSE, ...$reinitialisation],
            self::CONVENTION_ENVOYEE => [self::CONVENTION_RECUE, self::CONVENTION_IMPRIMEE, self::VALIDE, self::DEPOSE, ...$reinitialisation],
            self::CONVENTION_RECUE => [self::CONVENTION_ENVOYEE],
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return $cible === $this || \in_array($cible, $this->transitionsPossibles(), true);
    }
}
