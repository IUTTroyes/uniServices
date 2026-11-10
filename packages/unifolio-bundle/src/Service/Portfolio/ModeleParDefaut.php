<?php

namespace UnifolioBundle\Service\Portfolio;

use Symfony\Component\Uid\Uuid;

/**
 * Structure d'un portfolio académique quand la campagne n'a pas de modèle :
 * une page de présentation, une page par compétence de l'année (ses traces + une réflexion imposée), un CV.
 */
final class ModeleParDefaut
{
    /**
     * @param list<array{id: int|null, libelle: string|null, nomCourt: string|null}> $competences compétences de l'année
     *
     * @return list<array{titre: string, verrouillee: bool, blocs: list<array<string, mixed>>, verrouilles: list<string>}>
     */
    public static function pages(array $competences, string $titrePortfolio): array
    {
        $pages = [[
            'titre' => 'Présentation',
            'verrouillee' => true,
            'blocs' => [
                self::bloc('heading', ['niveau' => 1, 'texte' => $titrePortfolio]),
                self::bloc('paragraph', ['texte' => 'Présentez-vous et situez votre année : projets marquants, ce que vous avez appris, vos objectifs.']),
                self::bloc('progression', ['affichage' => 'barres']),
            ],
            'verrouilles' => [],
        ]];

        foreach ($competences as $competence) {
            $reflexion = self::bloc('reflexion', ['titre' => 'Ma réflexion sur cette compétence', 'texte' => '']);
            $pages[] = [
                'titre' => mb_substr($competence['nomCourt'] ?? $competence['libelle'] ?? 'Compétence', 0, 120),
                'verrouillee' => true,
                'blocs' => [
                    self::bloc('heading', ['niveau' => 2, 'texte' => mb_substr($competence['libelle'] ?? 'Compétence', 0, 200)]),
                    self::bloc('traces', ['competence' => $competence['id'], 'maximum' => 6, 'affichage' => 'cartes']),
                    $reflexion,
                ],
                'verrouilles' => [$reflexion['id']],
            ];
        }

        $pages[] = [
            'titre' => 'Mon CV',
            'verrouillee' => false,
            'blocs' => [self::bloc('cv', [])],
            'verrouilles' => [],
        ];

        return $pages;
    }

    /**
     * @param array<string, mixed>       $attrs
     * @param list<array<string, mixed>> $enfants
     *
     * @return array<string, mixed>
     */
    public static function bloc(string $type, array $attrs, array $enfants = []): array
    {
        $bloc = ['id' => Uuid::v4()->toRfc4122(), 'type' => $type, 'attrs' => $attrs];
        if ($enfants !== []) {
            $bloc['children'] = $enfants;
        }

        return $bloc;
    }

    private function __construct()
    {
    }
}
