<?php

namespace QuestionnaireBundle\Services;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Réglages des questionnaires d'un département (politique et valeurs par défaut), stockés dans les options du
 * département sous la clé « questionnaire » (StructureDepartement::opt).
 *
 * Ce qui vaut pour une enquête précise est recopié sur le questionnaire (opt, date de clôture) à sa création ou à sa
 * publication : changer un réglage ne modifie pas les questionnaires existants.
 * Un questionnaire sans département (établissement) utilise les valeurs par défaut.
 */
class QuestionnaireReglages
{
    public const DEFAUTS = [
        'anonymat' => true,               // anonymat des nouveaux questionnaires
        'modification_reponses' => true,  // les répondants peuvent modifier leurs réponses envoyées tant que c'est ouvert
        'duree_jours' => 14,              // à la publication sans date de clôture : clôture N jours après l'ouverture
        'cloture_auto' => true,           // clôture automatique (date atteinte ou tous répondus) et bilan
        'responsable_qualite' => null,    // identifiant du personnel référent qualité (consulte les résultats, reçoit les bilans)
        'seuil_min_reponses' => 5,        // questionnaire anonyme : résultats masqués sous ce nombre de réponses envoyées
        'publication_auto' => true,       // résultats publiés aux collègues à la fin de la relecture (si la diffusion est validée)
        'delai_relecture_jours' => 7,     // durée de relecture entre la clôture et la publication automatique
        'conservation_annees' => 5,       // après la clôture, les réponses sont purgées (résultats agrégés conservés)
    ];

    private const BORNES = ['duree_jours' => [1, 365], 'seuil_min_reponses' => [0, 50], 'delai_relecture_jours' => [0, 60], 'conservation_annees' => [1, 30]];

    /** @return array{anonymat: bool, modification_reponses: bool, duree_jours: int, cloture_auto: bool, responsable_qualite: ?int, seuil_min_reponses: int, publication_auto: bool, delai_relecture_jours: int, conservation_annees: int} */
    public function pour(?StructureDepartement $departement): array
    {
        $enregistres = $departement?->getOpt()['questionnaire'] ?? [];

        return self::normaliser(array_merge(self::DEFAUTS, is_array($enregistres) ? array_intersect_key($enregistres, self::DEFAUTS) : []));
    }

    /**
     * Met à jour les réglages (champs partiels) ; 422 si une valeur est hors bornes ou une clé inconnue.
     *
     * @param array<string, mixed> $modifications
     * @return array<string, mixed> réglages complets après mise à jour
     */
    public function enregistrer(StructureDepartement $departement, array $modifications): array
    {
        $inconnues = array_diff(array_keys($modifications), array_keys(self::DEFAUTS));
        if ($inconnues !== []) {
            throw new UnprocessableEntityHttpException('Réglage inconnu : '.implode(', ', $inconnues));
        }
        foreach (self::BORNES as $cle => [$min, $max]) {
            if (array_key_exists($cle, $modifications) && (!is_numeric($modifications[$cle]) || (int) $modifications[$cle] < $min || (int) $modifications[$cle] > $max)) {
                throw new UnprocessableEntityHttpException(sprintf('« %s » doit être compris entre %d et %d.', $cle, $min, $max));
            }
        }
        $reglages = self::normaliser(array_merge($this->pour($departement), $modifications));
        $departement->setOpt(['questionnaire' => $reglages]);

        return $reglages;
    }

    /** Valeurs recopiées sur un nouveau questionnaire (les valeurs envoyées par l'éditeur priment). */
    public function appliquerALaCreation(Questionnaire $questionnaire, bool $anonymatEnvoye): void
    {
        $reglages = $this->pour($questionnaire->getDepartement());
        $opt = [
            'modificationReponses' => $reglages['modification_reponses'],
            'seuilMinReponses' => $reglages['seuil_min_reponses'],
        ];
        if (!$anonymatEnvoye) {
            $opt['anonymous'] = $reglages['anonymat'];
        }
        $questionnaire->setOpt($opt);
    }

    /** À la publication : date de clôture par défaut (fin de journée, N jours après l'ouverture). */
    public function appliquerALaPublication(Questionnaire $questionnaire): void
    {
        if ($questionnaire->getClosingDate() !== null) {
            return;
        }
        $debut = $questionnaire->getOpeningDate() ?? new \DateTime();
        $jours = $this->pour($questionnaire->getDepartement())['duree_jours'];
        $questionnaire->setClosingDate(\DateTime::createFromInterface($debut)->modify(sprintf('+%d days', $jours))->setTime(23, 59, 59));
    }

    public function estResponsableQualite(Personnel $user, ?StructureDepartement $departement): bool
    {
        $id = $this->pour($departement)['responsable_qualite'];

        return $departement !== null && $id !== null && $id === $user->getId();
    }

    /**
     * @param array<string, mixed> $r
     * @return array{anonymat: bool, modification_reponses: bool, duree_jours: int, cloture_auto: bool, responsable_qualite: ?int, seuil_min_reponses: int, publication_auto: bool, delai_relecture_jours: int, conservation_annees: int}
     */
    private static function normaliser(array $r): array
    {
        $responsable = $r['responsable_qualite'] ?? null;

        return [
            'anonymat' => (bool) $r['anonymat'],
            'modification_reponses' => (bool) $r['modification_reponses'],
            'duree_jours' => (int) $r['duree_jours'],
            'cloture_auto' => (bool) $r['cloture_auto'],
            'responsable_qualite' => is_numeric($responsable) && (int) $responsable > 0 ? (int) $responsable : null,
            'seuil_min_reponses' => (int) $r['seuil_min_reponses'],
            'publication_auto' => (bool) $r['publication_auto'],
            'delai_relecture_jours' => (int) $r['delai_relecture_jours'],
            'conservation_annees' => (int) $r['conservation_annees'],
        ];
    }
}
