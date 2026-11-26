<?php

/**
 * Génère tout ce qui découle des « bundle.meta.json » (source unique de l'identité des applications) :
 * registre des outils, palettes CSS, liste des couleurs et favicon du portail.
 * Voir docs/IDENTITE_APPLICATIONS.md.
 */
function regenerate_tools_registry(string $projectRoot): void
{
    $entries = [];
    $keys = [];

    foreach ([$projectRoot . '/packages', $projectRoot . '/shared/global-data/external-tools'] as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            $metaPath = is_dir($path) ? $path . '/bundle.meta.json' : $path;
            if (!is_file($metaPath) || !str_ends_with($metaPath, '.meta.json')) {
                continue;
            }
            $data = json_decode(file_get_contents($metaPath), true, flags: JSON_THROW_ON_ERROR);
            if (!isset($data['name'], $data['description'], $data['urlSlug'])) {
                continue;
            }
            $brand = isset($data['brand']) ? uni_brand_complete($data['brand'], $metaPath) : null;
            if ($brand !== null) {
                if (isset($keys[$brand['key']])) {
                    throw new RuntimeException("Clé d'identité « {$brand['key']} » utilisée deux fois : {$keys[$brand['key']]} et $metaPath");
                }
                $keys[$brand['key']] = $metaPath;
            }
            $entries[] = [
                'name' => $data['name'],
                'description' => $data['description'],
                'urlSlug' => $data['urlSlug'],
                'url' => $data['url'] ?? "/{$data['urlSlug']}",
                'shortLabel' => $data['shortLabel'] ?? $data['name'],
                'brand' => $brand,
                'primaryColor' => $brand !== null ? 'uni-' . $brand['key'] : ($data['primaryColor'] ?? null),
                'icon' => $data['icon'] ?? 'pi pi-box',
                'aliases' => $data['aliases'] ?? [],
                'isDefault' => $data['isDefault'] ?? false,
                'showInPortal' => $data['showInPortal'] ?? true,
            ];
        }
    }

    $brands = array_values(array_filter(array_column($entries, 'brand')));

    uni_write_json($projectRoot . '/shared/global-data/tools.generated.json', $entries);
    uni_write_brand_css($projectRoot . '/shared/styles/uni-brand.css', $brands);
    uni_write_palette_list($projectRoot . '/shared/global-data/application-colors.json', $brands);
    uni_write_portal_favicon($projectRoot . '/packages/shell/assets/assets/favicon.svg', $brands);

    echo "- Regenerated tools registry, brand colors and favicon\n";
}

/** Valide le bloc « brand » et calcule les couleurs facultatives. */
function uni_brand_complete(array $brand, string $source): array
{
    if (!preg_match('/^[a-z0-9]+$/D', $brand['key'] ?? '')) {
        throw new RuntimeException("brand.key manquant ou invalide (minuscules et chiffres) dans $source");
    }
    foreach (['color', 'colorOnDark', 'accent'] as $field) {
        if (isset($brand[$field]) && !preg_match('/^#[0-9A-Fa-f]{6}$/D', $brand[$field])) {
            throw new RuntimeException("brand.$field doit s'écrire #RRGGBB dans $source");
        }
    }
    if (!isset($brand['color'])) {
        throw new RuntimeException("brand.color manquant dans $source");
    }
    $color = strtoupper($brand['color']);
    $onDark = strtoupper($brand['colorOnDark'] ?? uni_mix($color, '#FFFFFF', 0.55));

    return [
        'key' => $brand['key'],
        'tagline' => $brand['tagline'] ?? '',
        'color' => $color,
        'accent' => strtoupper($brand['accent'] ?? uni_mix($color, '#FFFFFF', 0.42)),
        'colorOnDark' => $onDark,
        'accentOnDark' => strtoupper($brand['accent'] ?? uni_mix($onDark, '#1C1530', 0.38)),
        'pictogram' => $brand['pictogram'] ?? null,
    ];
}

/** Mélange deux couleurs « #RRGGBB » ($ratio = part de la seconde). */
function uni_mix(string $a, string $b, float $ratio): string
{
    $channels = array_map(
        static fn (int $i): int => (int) round(hexdec(substr($a, $i, 2)) * (1 - $ratio) + hexdec(substr($b, $i, 2)) * $ratio),
        [1, 3, 5],
    );

    return sprintf('#%02X%02X%02X', ...$channels);
}

/** Nuances 50 à 950. La 500 est la couleur dominante, la 400 la couleur primaire du mode sombre. */
function uni_shades(array $brand): array
{
    $p = $brand['color'];
    $light = $brand['colorOnDark'] === '#FFFFFF' ? uni_mix($p, '#FFFFFF', 0.55) : $brand['colorOnDark'];
    $soft = uni_mix($p, '#FFFFFF', 0.86);

    return [
        50 => uni_mix($p, '#FFFFFF', 0.93),
        100 => $soft,
        200 => uni_mix($light, $soft, 0.5),
        300 => $light,
        400 => uni_mix($light, $p, 0.2),
        500 => $p,
        600 => uni_mix($p, '#000000', 0.12),
        700 => uni_mix($p, '#000000', 0.26),
        800 => uni_mix($p, '#000000', 0.40),
        900 => uni_mix($p, '#000000', 0.52),
        950 => uni_mix($p, '#000000', 0.68),
    ];
}

function uni_write_json(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
}

function uni_write_brand_css(string $path, array $brands): void
{
    $css = [
        '/*',
        ' * FICHIER GÉNÉRÉ par scripts/regenerate-registry.php : ne pas modifier.',
        " * Source : le bloc « brand » de chaque bundle.meta.json.",
        ' */',
        '@theme static {',
    ];
    foreach ($brands as $brand) {
        foreach (uni_shades($brand) as $shade => $value) {
            $css[] = "  --color-uni-{$brand['key']}-$shade: " . strtolower($value) . ';';
        }
    }
    $css[] = '}';

    if ($brands !== []) {
        // Les classes sont composées à l'exécution : Tailwind doit les connaître à l'avance.
        $u = 'uni-{' . implode(',', array_column($brands, 'key')) . '}';
        if (count($brands) === 1) {
            $u = 'uni-' . $brands[0]['key'];
        }
        $css[] = '';
        $css[] = "@source inline(\"bg-$u-{50,100,200,300,400,500,600,700,800,900,950}\");";
        $css[] = "@source inline(\"{bg-$u-{50,100},text-$u-{600,800},border-$u-200}!\");";
        $css[] = "@source inline(\"dark:{bg-$u-950/{40,60},text-$u-300,border-$u-800}!\");";
        $css[] = "@source inline(\"{bg-$u-100,text-$u-600,border-$u-100,border-$u-400}\");";
        $css[] = "@source inline(\"dark:{bg-$u-950/20,text-$u-400,border-$u-900/30}\");";
        $css[] = "@source inline(\"hover:bg-$u-400/10\");";
    }

    file_put_contents($path, implode("\n", $css) . "\n");
}

/** Conserve les couleurs Tailwind et remplace les couleurs « uni-* ». */
function uni_write_palette_list(string $path, array $brands): void
{
    $colors = is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
    $colors = array_values(array_filter($colors, static fn (string $c): bool => !str_starts_with($c, 'uni-')));
    foreach ($brands as $brand) {
        $colors[] = 'uni-' . $brand['key'];
    }
    uni_write_json($path, $colors);
}

/** Favicon affiché au premier chargement ; les favicons par application sont dessinés côté Vue. */
function uni_write_portal_favicon(string $path, array $brands): void
{
    foreach ($brands as $brand) {
        if ($brand['key'] !== 'service' || !is_dir(dirname($path))) {
            continue;
        }
        $left = 'M11 18A11 11 0 0 1 33 18V56A17 17 0 0 0 42.275 71.143A15 15 0 0 0 38.11 93.143A39 39 0 0 1 11 56Z';
        $right = 'M50 73A17 17 0 0 0 67 56V18A11 11 0 0 1 89 18V56A39 39 0 0 1 50 95A11 11 0 0 1 50 73Z';
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 92 92">'
            . "<rect width=\"92\" height=\"92\" rx=\"22\" fill=\"{$brand['color']}\"/>"
            . '<g transform="translate(13 12.5) scale(0.66)">'
            . "<path fill=\"#FFFFFF\" d=\"$left\"/><path fill=\"{$brand['accent']}\" d=\"$right\"/></g></svg>\n");
    }
}
