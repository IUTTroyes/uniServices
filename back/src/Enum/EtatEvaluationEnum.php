<?php

namespace App\Enum;

enum EtatEvaluationEnum: string
{
    case ETAT_NON_INITIALISEE = 'non_initialisee';
    case ETAT_INITIALISEE = 'initialisee';
    case ETAT_PLANIFIEE = 'planifiee';
    case ETAT_ANNULEE = 'annulee';
    case ETAT_COMPLETEE = 'completee';
    case ETAT_PUBLIEE = 'publiee';


    public static function getChoices(): array
    {
        return [
            'choice.' . self::ETAT_NON_INITIALISEE->value => self::ETAT_NON_INITIALISEE->value,
            'choice.' . self::ETAT_INITIALISEE->value => self::ETAT_INITIALISEE->value,
            'choice.' . self::ETAT_PLANIFIEE->value => self::ETAT_PLANIFIEE->value,
            'choice.' . self::ETAT_ANNULEE->value => self::ETAT_ANNULEE->value,
            'choice.' . self::ETAT_COMPLETEE->value => self::ETAT_COMPLETEE->value,
            'choice.' . self::ETAT_PUBLIEE->value => self::ETAT_PUBLIEE->value,
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::ETAT_NON_INITIALISEE,
            self::ETAT_INITIALISEE,
            self::ETAT_PLANIFIEE,
            self::ETAT_ANNULEE,
            self::ETAT_COMPLETEE,
            self::ETAT_PUBLIEE,
        ];
    }

    public static function getLibelle(): array
    {
        return [
            self::ETAT_NON_INITIALISEE->value => 'Non initialisée',
            self::ETAT_INITIALISEE->value => 'Initialisée',
            self::ETAT_PLANIFIEE->value => 'Planifiée',
            self::ETAT_ANNULEE->value => 'Annulée',
            self::ETAT_COMPLETEE->value => 'Complétée',
            self::ETAT_PUBLIEE->value => 'Publiée',
        ];
    }

    public static function getSeverity(): array
    {
        return [
            self::ETAT_NON_INITIALISEE->value => 'error',
            self::ETAT_INITIALISEE->value => 'info',
            self::ETAT_PLANIFIEE->value => 'warn',
            self::ETAT_ANNULEE->value => 'error',
            self::ETAT_COMPLETEE->value => 'success',
            self::ETAT_PUBLIEE->value => 'success',
        ];
    }
    public static function getIcon(): array
    {
        return [
            self::ETAT_NON_INITIALISEE->value => 'pi pi-exclamation-triangle',
            self::ETAT_INITIALISEE->value => 'pi pi-hourglass',
            self::ETAT_PLANIFIEE->value => 'pi pi-hourglass',
            self::ETAT_ANNULEE->value => 'pi pi-times-circle',
            self::ETAT_COMPLETEE->value => 'pi pi-check-circle',
            self::ETAT_PUBLIEE->value => 'pi pi-check-circle',
        ];
    }

    /*Retourne les statuts accessibles depuis le statut actuel*/
    public static function getTransitionsAutorisees(self $etat): array
    {
        return match ($etat) {
            self::ETAT_NON_INITIALISEE  => [self::ETAT_INITIALISEE],
            self::ETAT_INITIALISEE   => [self::ETAT_PLANIFIEE, self::ETAT_ANNULEE],
            self::ETAT_PLANIFIEE => [self::ETAT_COMPLETEE, self::ETAT_ANNULEE],
            self::ETAT_COMPLETEE => [self::ETAT_PUBLIEE, self::ETAT_ANNULEE],
            self::ETAT_ANNULEE => [self::ETAT_INITIALISEE, self::ETAT_COMPLETEE, self::ETAT_PLANIFIEE],
            self::ETAT_PUBLIEE => [],
        };
    }

    public function isEtatManuel(): bool
    {
        return in_array($this, [self::ETAT_ANNULEE, self::ETAT_PUBLIEE], true);
    }
}
