<?php

function regenerate_tools_registry($projectRoot) {
    $toolsOut = $projectRoot . '/shared/global-data/tools.generated.json';
    $packagesDir = $projectRoot . '/packages';
    $externalDir = $projectRoot . '/shared/global-data/external-tools';
    $entries = [];

    // Helper to scan a directory for meta files
    $scanMeta = function($dir) use (&$entries) {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $item) {
                if ($item === '.' || $item === '..') continue;

                $path = $dir . '/' . $item;
                $metaPath = is_dir($path) ? $path . '/bundle.meta.json' : $path;

                if (is_file($metaPath) && str_ends_with($metaPath, '.meta.json')) {
                    $data = json_decode(file_get_contents($metaPath), true);
                    if (isset($data['name'], $data['description'], $data['urlSlug'])) {
                        $entries[] = [
                            'name' => $data['name'],
                            'description' => $data['description'],
                            'urlSlug' => $data['urlSlug'],
                            'url' => $data['url'] ?? "/{$data['urlSlug']}",
                        ];
                    }
                }
            }
        }
    };

    $scanMeta($packagesDir);
    $scanMeta($externalDir);

    file_put_contents($toolsOut, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "- Regenerated tools registry\n";
}
