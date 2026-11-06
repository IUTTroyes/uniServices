<?php

namespace UnifolioBundle\Enum;

use App\Enum\BadgeEnumInterface;

enum StatutTraceEnum: string implements BadgeEnumInterface
{
    case BROUILLON = 'brouillon';
    case DEPOSEE = 'deposee';
    case EN_EVALUATION = 'en_evaluation';
    case EVALUEE = 'evaluee';
    case A_REVISER = 'a_reviser';

    public function getLibelle(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::DEPOSEE => 'Déposée',
            self::EN_EVALUATION => 'En évaluation',
            self::EVALUEE => 'Évaluée',
            self::A_REVISER => 'À réviser',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::BROUILLON => 'secondary',
            self::DEPOSEE => 'info',
            self::EN_EVALUATION => 'warn',
            self::EVALUEE => 'success',
            self::A_REVISER => 'danger',
        };
    }

    /** L'étudiant peut modifier le contenu de la trace. */
    public function isModifiableParEtudiant(): bool
    {
        return \in_array($this, [self::BROUILLON, self::DEPOSEE, self::A_REVISER], true);
    }

    /** @return self[] */
    public function transitionsPossibles(): array
    {
        return match ($this) {
            self::BROUILLON => [self::DEPOSEE],
            self::DEPOSEE => [self::BROUILLON, self::EN_EVALUATION],
            self::EN_EVALUATION => [self::EVALUEE, self::A_REVISER],
            self::A_REVISER => [self::DEPOSEE],
            self::EVALUEE => [self::A_REVISER],
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return \in_array($cible, $this->transitionsPossibles(), true);
    }
}
