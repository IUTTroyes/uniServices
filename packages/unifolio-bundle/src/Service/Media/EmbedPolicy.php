<?php

namespace UnifolioBundle\Service\Media;

/** Sites dont le contenu peut être intégré (iframe) dans une trace ou un portfolio. */
final class EmbedPolicy
{
    public const HOTES = [
        'youtube.com',
        'youtu.be',
        'youtube-nocookie.com',
        'vimeo.com',
        'player.vimeo.com',
        'figma.com',
        'codepen.io',
        'canva.com',
        'sketchfab.com',
        'soundcloud.com',
        'genial.ly',
        'view.genial.ly',
        'docs.google.com',
    ];

    public static function isAutorise(string $url): bool
    {
        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }
        $hote = strtolower((string) parse_url($url, PHP_URL_HOST));
        foreach (self::HOTES as $autorise) {
            if ($hote === $autorise || str_ends_with($hote, '.' . $autorise)) {
                return true;
            }
        }

        return false;
    }

    private function __construct()
    {
    }
}
