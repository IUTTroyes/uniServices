<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PdfExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('tel_format', [$this, 'formatTel']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('settings', [$this, 'getSetting']),
        ];
    }

    /**
     * Formate un numéro de téléphone avec des espaces (format français ou international).
     */
    public function formatTel(?string $tel): string
    {
        if ($tel === null || trim($tel) === '') {
            return '';
        }

        $trimmed = trim($tel);
        $clean = preg_replace('/[^\d+]/', '', $trimmed);
        if ($clean === null || $clean === '') {
            return $trimmed;
        }

        // Format international français : +33 6 12 34 56 78
        if (str_starts_with($clean, '+33') && strlen($clean) === 12) {
            return '+33 ' . $clean[3] . ' ' . substr($clean, 4, 2) . ' ' . substr($clean, 6, 2) . ' ' . substr($clean, 8, 2) . ' ' . substr($clean, 10, 2);
        }

        // Format national 10 chiffres : 03 26 91 34 68
        if (strlen($clean) === 10) {
            return implode(' ', str_split($clean, 2));
        }

        return $trimmed;
    }

    /**
     * Retourne une valeur de configuration ou paramètre pour les templates.
     */
    public function getSetting(string $key): mixed
    {
        return match ($key) {
            'BASE_PATH' => '',
            default => null,
        };
    }
}
