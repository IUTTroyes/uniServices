<?php

namespace UnifolioBundle\Enum;

/**
 * Événements notifiables. Chaque type a son public, son mode par défaut et les modes qu'on peut lui donner.
 * Par défaut, ce qui appelle une réaction rapide part tout de suite ; le reste est regroupé dans un résumé.
 */
enum TypeNotificationEnum: string
{
    case EVALUATION_RENDUE = 'evaluation_rendue';
    case REVISION_DEMANDEE = 'revision_demandee';
    case COMMENTAIRE_ENSEIGNANT = 'commentaire_enseignant';
    case PUBLICATION_MODEREE = 'publication_moderee';
    case TRACE_DEPOSEE = 'trace_deposee';
    case COMMENTAIRE_ETUDIANT = 'commentaire_etudiant';
    case RELANCE_EVALUATEUR = 'relance_evaluateur';

    public const AUDIENCE_ETUDIANT = 'etudiant';
    public const AUDIENCE_PERSONNEL = 'personnel';

    public function getLibelle(): string
    {
        return match ($this) {
            self::EVALUATION_RENDUE => 'Une évaluation m\'est communiquée',
            self::REVISION_DEMANDEE => 'On me demande de réviser une trace',
            self::COMMENTAIRE_ENSEIGNANT => 'Un enseignant commente une de mes traces',
            self::PUBLICATION_MODEREE => 'Un de mes portfolios est retiré par la modération',
            self::TRACE_DEPOSEE => 'Un étudiant dont j\'ai la charge dépose une trace',
            self::COMMENTAIRE_ETUDIANT => 'Un étudiant répond à un commentaire ou en ajoute un',
            self::RELANCE_EVALUATEUR => 'Rappel des évaluations en attente',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::EVALUATION_RENDUE => 'Une évaluation de l\'un de vos travaux ou de votre bilan vient de vous être communiquée.',
            self::REVISION_DEMANDEE => 'Un enseignant demande des modifications avant de pouvoir évaluer une trace.',
            self::COMMENTAIRE_ENSEIGNANT => 'Nouveau commentaire d\'un enseignant dans la discussion d\'une trace.',
            self::PUBLICATION_MODEREE => 'Un référent a retiré un portfolio publié ; le motif vous est indiqué.',
            self::TRACE_DEPOSEE => 'Une trace d\'un étudiant qui vous est affecté est prête à être évaluée.',
            self::COMMENTAIRE_ETUDIANT => 'Nouveau commentaire d\'un étudiant sur une trace qu\'il vous revient d\'évaluer.',
            self::RELANCE_EVALUATEUR => 'Rappel périodique, à l\'approche (ou après) la limite d\'évaluation d\'une campagne.',
        };
    }

    /** Intitulé de la rubrique dans le résumé groupé. */
    public function getRubrique(): string
    {
        return match ($this) {
            self::EVALUATION_RENDUE => 'Évaluations communiquées',
            self::REVISION_DEMANDEE => 'Révisions demandées',
            self::COMMENTAIRE_ENSEIGNANT, self::COMMENTAIRE_ETUDIANT => 'Nouveaux commentaires',
            self::PUBLICATION_MODEREE => 'Modération',
            self::TRACE_DEPOSEE => 'Traces à évaluer',
            self::RELANCE_EVALUATEUR => 'Rappels',
        };
    }

    public function getAudience(): string
    {
        return match ($this) {
            self::EVALUATION_RENDUE, self::REVISION_DEMANDEE, self::COMMENTAIRE_ENSEIGNANT, self::PUBLICATION_MODEREE => self::AUDIENCE_ETUDIANT,
            self::TRACE_DEPOSEE, self::COMMENTAIRE_ETUDIANT, self::RELANCE_EVALUATEUR => self::AUDIENCE_PERSONNEL,
        };
    }

    public function getModeParDefaut(): ModeNotificationEnum
    {
        return match ($this) {
            self::COMMENTAIRE_ENSEIGNANT, self::TRACE_DEPOSEE, self::COMMENTAIRE_ETUDIANT => ModeNotificationEnum::RESUME,
            default => ModeNotificationEnum::IMMEDIAT,
        };
    }

    /**
     * Un rappel périodique n'a pas de sens « dans un résumé » ; un retrait de modération ne peut pas être ignoré
     * (il reste visible dans l'application, mais l'étudiant doit au moins le recevoir en résumé).
     *
     * @return list<ModeNotificationEnum>
     */
    public function getModesAutorises(): array
    {
        return match ($this) {
            self::RELANCE_EVALUATEUR => [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::AUCUN],
            self::PUBLICATION_MODEREE => [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::RESUME],
            default => ModeNotificationEnum::cases(),
        };
    }

    /** @return list<self> types destinés à ce public */
    public static function pourAudience(string $audience): array
    {
        return array_values(array_filter(self::cases(), static fn (self $t) => $t->getAudience() === $audience));
    }
}
