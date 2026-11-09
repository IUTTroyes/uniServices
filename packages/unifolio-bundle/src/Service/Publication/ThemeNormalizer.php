<?php

namespace UnifolioBundle\Service\Publication;

/**
 * Apparence d'un portfolio publié : une poignée de réglages bornés (couleur, police, en-tête, largeur, coins).
 * Tout est validé par liste blanche : aucune valeur libre n'atteint le HTML ou le CSS généré (pas de CSS ni de police externes).
 */
final class ThemeNormalizer
{
    public const DEFAUT = ['couleur' => '#047857', 'police' => 'systeme', 'entete' => 'clair', 'largeur' => 'standard', 'coins' => 'arrondis'];

    /** Polices : piles système uniquement (aucun chargement externe, donc aucune fuite vers un tiers). */
    public const POLICES = ['systeme', 'classique', 'humaniste', 'arrondie', 'technique'];
    public const ENTETES = ['clair', 'couleur', 'sobre'];
    public const LARGEURS = ['etroite', 'standard', 'large'];
    public const COINS = ['arrondis', 'droits'];

    /** Contraste minimal de la couleur d'accent sur fond blanc (WCAG AA, texte normal). */
    private const CONTRASTE_MINI = 4.5;

    /**
     * @param array<string, mixed> $donnees réglages partiels ; les clés absentes prennent la valeur par défaut
     *
     * @return array{couleur: string, police: string, entete: string, largeur: string, coins: string}
     *
     * @throws \InvalidArgumentException
     */
    public function normaliser(array $donnees): array
    {
        $theme = self::DEFAUT;
        foreach (['police' => self::POLICES, 'entete' => self::ENTETES, 'largeur' => self::LARGEURS, 'coins' => self::COINS] as $cle => $autorises) {
            if (isset($donnees[$cle])) {
                $valeur = (string) $donnees[$cle];
                if (!\in_array($valeur, $autorises, true)) {
                    throw new \InvalidArgumentException(sprintf('Réglage « %s » inconnu : %s.', $cle, mb_substr($valeur, 0, 40)));
                }
                $theme[$cle] = $valeur;
            }
        }
        if (isset($donnees['couleur'])) {
            $couleur = strtolower(trim((string) $donnees['couleur']));
            if (!preg_match('/^#[0-9a-f]{6}$/', $couleur)) {
                throw new \InvalidArgumentException('La couleur doit être au format #rrggbb.');
            }
            if (self::contraste($couleur, '#ffffff') < self::CONTRASTE_MINI) {
                throw new \InvalidArgumentException('Cette couleur est trop claire : le texte et les liens ne seraient pas lisibles sur fond blanc. Choisissez une teinte plus foncée.');
            }
            $theme['couleur'] = $couleur;
        }

        return $theme;
    }

    /** Thème stocké (éventuellement ancien ou vide) ramené à un thème valide, sans jamais lever d'exception. */
    public function lire(array $stocke): array
    {
        try {
            return $this->normaliser($stocke);
        } catch (\InvalidArgumentException) {
            return self::DEFAUT;
        }
    }

    /** Rapport de contraste WCAG entre deux couleurs #rrggbb. */
    public static function contraste(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $canaux = array_map(static function (string $c): float {
            $v = hexdec($c) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $canaux[0] + 0.7152 * $canaux[1] + 0.0722 * $canaux[2];
    }
}
