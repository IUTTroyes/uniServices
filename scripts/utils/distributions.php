<?php

/**
 * Distributions (hors « intranet ») qui listent explicitement un package.
 *
 * @return list<string> fichiers qui le citent
 */
function distributions_utilisant(string $projectRoot, string $bundleKebab, string $bundlePascal): array
{
    $citations = [];
    foreach (glob($projectRoot . '/back/config/distributions/*.php') ?: [] as $fichier) {
        if (preg_match('/[\'"]' . preg_quote($bundlePascal, '/') . '[\'"]/', (string) file_get_contents($fichier))) {
            $citations[] = substr($fichier, strlen($projectRoot) + 1);
        }
    }
    foreach (glob($projectRoot . '/packages/shell/assets/distributions/*.js') ?: [] as $fichier) {
        if (str_contains((string) file_get_contents($fichier), "/$bundleKebab/")) {
            $citations[] = substr($fichier, strlen($projectRoot) + 1);
        }
    }

    return $citations;
}
