<?php

namespace UnifolioBundle\Service\Grille;

use App\Entity\Structure\StructureTypeDiplome;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\TypeCritereEnum;

/** Grilles nationales BUT proposées par défaut, une par granularité (publiées en version 1). */
final class GrillesParDefaut
{
    /** @var array<string, array{libelle: string, description: string, criteres: list<array{0: string, 1: TypeCritereEnum, 2: float, 3: string}>}> */
    public const DEFINITIONS = [
        'ac' => [
            'libelle' => 'BUT — évaluation par apprentissage critique',
            'description' => 'Grille nationale par défaut : chaque trace est évaluée sur les apprentissages critiques auxquels elle est rattachée.',
            'criteres' => [
                ['Niveau d\'acquisition de l\'apprentissage critique', TypeCritereEnum::ECHELLE, 2, 'La trace démontre-t-elle l\'apprentissage critique, avec quelle autonomie ?'],
                ['Pertinence de la trace', TypeCritereEnum::ECHELLE, 1, 'La production est-elle une preuve adaptée et authentique de l\'apprentissage ?'],
                ['Qualité de l\'argumentaire', TypeCritereEnum::ECHELLE, 1, 'L\'étudiant explique-t-il clairement en quoi la trace démontre l\'apprentissage ?'],
                ['Appréciation', TypeCritereEnum::TEXTE, 0, 'Points forts et axes de progrès.'],
            ],
        ],
        'niveau' => [
            'libelle' => 'BUT — évaluation par niveau de compétence',
            'description' => 'Grille nationale par défaut : chaque niveau de compétence de l\'année est évalué à partir des traces rattachées.',
            'criteres' => [
                ['Maîtrise du niveau de compétence', TypeCritereEnum::ECHELLE, 2, 'Les composantes essentielles du niveau sont-elles démontrées ?'],
                ['Couverture des apprentissages critiques', TypeCritereEnum::ECHELLE, 1, 'Les apprentissages critiques du niveau sont-ils couverts par des traces ?'],
                ['Réflexivité', TypeCritereEnum::ECHELLE, 1, 'L\'étudiant analyse-t-il sa démarche, ses réussites et ses difficultés ?'],
                ['Appréciation', TypeCritereEnum::TEXTE, 0, 'Points forts et axes de progrès.'],
            ],
        ],
        'competence' => [
            'libelle' => 'BUT — évaluation par compétence',
            'description' => 'Grille nationale par défaut : chaque compétence est évaluée au niveau attendu pour l\'année.',
            'criteres' => [
                ['Maîtrise de la compétence au niveau attendu', TypeCritereEnum::ECHELLE, 2, 'La compétence est-elle mobilisée en situation, au niveau visé cette année ?'],
                ['Diversité et pertinence des preuves', TypeCritereEnum::ECHELLE, 1, 'Les traces couvrent-elles des situations variées et significatives ?'],
                ['Réflexivité', TypeCritereEnum::ECHELLE, 1, 'L\'étudiant analyse-t-il sa progression dans la compétence ?'],
                ['Appréciation', TypeCritereEnum::TEXTE, 0, 'Points forts et axes de progrès.'],
            ],
        ],
    ];

    public static function construire(StructureTypeDiplome $type, GranulariteEnum $granularite): Grille
    {
        $definition = self::DEFINITIONS[$granularite->value];
        $grille = new Grille($definition['libelle'], $type, $granularite);
        $grille->setDescription($definition['description']);

        $version = $grille->creerBrouillon();
        foreach ($definition['criteres'] as [$libelle, $typeCritere, $poids, $aide]) {
            $version->ajouterCritere($libelle, $typeCritere)->setAide($aide)->setPoids($poids);
        }
        $version->publier();

        return $grille;
    }

    private function __construct()
    {
    }
}
