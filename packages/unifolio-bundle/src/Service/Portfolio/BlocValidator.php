<?php

namespace UnifolioBundle\Service\Portfolio;

use Symfony\Component\Uid\Uuid;
use UnifolioBundle\Service\Media\EmbedPolicy;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/**
 * Valide et normalise un document de blocs. C'est la frontière de sécurité du portfolio :
 *  - un bloc ne contient jamais de HTML (le texte riche est du Markdown simplifié, échappé au rendu) ;
 *  - les adresses sont en http(s) et les contenus intégrés limités à une liste blanche ;
 *  - les références à des traces et des médias n'ont de sens que pour leur propriétaire.
 * Les attributs inconnus sont écartés, les manquants remplacés par leur valeur par défaut.
 */
final class BlocValidator
{
    public function __construct(
        private readonly BlocRegistry $registre,
    ) {
    }

    /**
     * @param mixed $blocs document soumis (liste de blocs)
     *
     * @return list<array<string, mixed>> blocs normalisés
     *
     * @throws UnifolioValidationException avec la liste de toutes les anomalies
     */
    public function valider(mixed $blocs, ContexteBlocs $contexte): array
    {
        if (!\is_array($blocs) || !array_is_list($blocs)) {
            throw UnifolioValidationException::un('Le contenu de la page doit être une liste de blocs.');
        }
        if (mb_strlen((string) json_encode($blocs)) > $this->registre->limite('taille')) {
            throw UnifolioValidationException::un('Cette page est trop volumineuse : allégez-la ou répartissez-la sur plusieurs pages.');
        }

        $erreurs = [];
        $ids = [];
        $total = 0;
        $normalises = $this->liste($blocs, null, 1, 'Page', $contexte, $erreurs, $ids, $total);

        if ($total > $this->registre->limite('blocs')) {
            $erreurs[] = sprintf('Une page est limitée à %d blocs (%d présents).', $this->registre->limite('blocs'), $total);
        }
        if ($erreurs !== []) {
            throw new UnifolioValidationException(\array_slice($erreurs, 0, 20));
        }

        return $normalises;
    }

    /**
     * Anomalies de complétude d'un document déjà normalisé (champ obligatoire vide) : elles n'empêchent
     * pas l'enregistrement, mais empêcheront la publication.
     *
     * @param list<array<string, mixed>> $blocs
     *
     * @return list<string>
     */
    public function incomplets(array $blocs, string $chemin = 'Page'): array
    {
        $manques = [];
        foreach ($blocs as $i => $bloc) {
            $definition = $this->registre->type($bloc['type']);
            if ($definition === null) {
                continue;
            }
            $ou = sprintf('%s › %s %d', $chemin, mb_strtolower($definition['libelle']), $i + 1);
            foreach ($definition['attrs'] as $nom => $def) {
                $v = $bloc['attrs'][$nom] ?? null;
                if (($def['requis'] ?? false) && ($v === null || $v === '' || $v === [])) {
                    $manques[] = sprintf('%s : « %s » est à renseigner.', $ou, $def['libelle']);
                }
            }
            array_push($manques, ...$this->incomplets($bloc['children'] ?? [], $ou));
        }

        return $manques;
    }

    /**
     * @param list<mixed>           $blocs
     * @param list<string>          $erreurs
     * @param array<string, true>   $ids
     *
     * @return list<array<string, mixed>>
     */
    private function liste(array $blocs, ?string $parent, int $profondeur, string $chemin, ContexteBlocs $contexte, array &$erreurs, array &$ids, int &$total): array
    {
        $resultat = [];
        foreach ($blocs as $i => $bloc) {
            $ou = sprintf('%s › bloc %d', $chemin, $i + 1);
            if (!\is_array($bloc)) {
                $erreurs[] = sprintf('%s : format invalide.', $ou);
                continue;
            }
            ++$total;
            $type = (string) ($bloc['type'] ?? '');
            $definition = $this->registre->type($type);
            if ($definition === null) {
                $erreurs[] = sprintf('%s : type de bloc inconnu « %s ».', $ou, $type);
                continue;
            }
            $ou = sprintf('%s › %s %d', $chemin, mb_strtolower($definition['libelle']), $i + 1);

            if ($profondeur > $this->registre->limite('profondeur')) {
                $erreurs[] = sprintf('%s : imbrication trop profonde (%d niveaux maximum).', $ou, $this->registre->limite('profondeur'));
                continue;
            }
            if (isset($definition['seulement_dans']) && !\in_array($parent, $definition['seulement_dans'], true)) {
                $erreurs[] = sprintf('%s : ce bloc ne peut figurer que dans %s.', $ou, implode(' / ', $definition['seulement_dans']));
                continue;
            }
            if ($parent !== null) {
                $attendus = $this->registre->type($parent)['enfants'] ?? null;
                if ($attendus !== '*' && !\in_array($type, (array) $attendus, true)) {
                    $erreurs[] = sprintf('%s : ce bloc n\'est pas autorisé ici.', $ou);
                    continue;
                }
            }

            $id = (string) ($bloc['id'] ?? '');
            if ($id === '') {
                $id = Uuid::v4()->toRfc4122();
            }
            if (!preg_match('/^[A-Za-z0-9_-]{8,40}$/', $id)) {
                $erreurs[] = sprintf('%s : identifiant de bloc invalide.', $ou);
                continue;
            }
            if (isset($ids[$id])) {
                $erreurs[] = sprintf('%s : identifiant de bloc en double.', $ou);
                continue;
            }
            $ids[$id] = true;

            $attrs = $this->attributs($definition['attrs'], \is_array($bloc['attrs'] ?? null) ? $bloc['attrs'] : [], $ou, $contexte, $erreurs);

            $enfants = $bloc['children'] ?? [];
            if (!\is_array($enfants) || !array_is_list($enfants)) {
                $erreurs[] = sprintf('%s : contenu imbriqué invalide.', $ou);
                $enfants = [];
            }
            if ($enfants !== [] && !isset($definition['enfants'])) {
                $erreurs[] = sprintf('%s : ce bloc ne peut pas contenir d\'autres blocs.', $ou);
                $enfants = [];
            }

            $normalise = ['id' => $id, 'type' => $type, 'attrs' => $attrs];
            if (isset($definition['enfants'])) {
                $normalise['children'] = $this->liste($enfants, $type, $profondeur + 1, $ou, $contexte, $erreurs, $ids, $total);
            }
            $resultat[] = $normalise;
        }

        return $resultat;
    }

    /**
     * @param array<string, array<string, mixed>> $schema
     * @param array<string, mixed>                $valeurs
     * @param list<string>                        $erreurs
     *
     * @return array<string, mixed>
     */
    private function attributs(array $schema, array $valeurs, string $ou, ContexteBlocs $contexte, array &$erreurs): array
    {
        $attrs = [];
        foreach ($schema as $nom => $def) {
            $present = \array_key_exists($nom, $valeurs) && $valeurs[$nom] !== null && $valeurs[$nom] !== '';
            if (!$present) {
                // Un bloc en cours de composition peut être enregistré incomplet : voir incomplets().
                $attrs[$nom] = $def['defaut'] ?? null;
                continue;
            }
            $valide = $this->valeur($def, $valeurs[$nom], $contexte, sprintf('%s › %s', $ou, $def['libelle']), $erreurs);
            $attrs[$nom] = $valide ?? ($def['defaut'] ?? null);
        }

        return $attrs;
    }

    /**
     * @param array<string, mixed> $def
     * @param list<string>         $erreurs
     */
    private function valeur(array $def, mixed $v, ContexteBlocs $contexte, string $ou, array &$erreurs): mixed
    {
        switch ($def['type']) {
            case 'texte':
            case 'markdown':
                if (!\is_string($v)) {
                    $erreurs[] = sprintf('%s : texte attendu.', $ou);

                    return null;
                }
                $v = $def['type'] === 'texte' ? trim(preg_replace('/\s+/u', ' ', $v) ?? '') : trim(str_replace("\r\n", "\n", $v));
                if (mb_strlen($v) > (int) $def['max']) {
                    $erreurs[] = sprintf('%s : %d caractères maximum.', $ou, $def['max']);

                    return null;
                }

                return $v;

            case 'entier':
                if (!is_numeric($v) || (int) $v != $v || $v < $def['min'] || $v > $def['max']) {
                    $erreurs[] = sprintf('%s : nombre entier entre %d et %d attendu.', $ou, $def['min'], $def['max']);

                    return null;
                }

                return (int) $v;

            case 'competence':
                return is_numeric($v) && (int) $v > 0 ? (int) $v : null;

            case 'bool':
                return (bool) $v;

            case 'enum':
                if (!\is_scalar($v) || !\array_key_exists((string) $v, $def['valeurs'])) {
                    $erreurs[] = sprintf('%s : valeur « %s » non autorisée.', $ou, \is_scalar($v) ? (string) $v : '?');

                    return null;
                }

                return (string) $v;

            case 'url':
                return $this->url($v, ($def['embed'] ?? false), $ou, $erreurs);

            case 'liste_texte':
                if (!\is_array($v) || !array_is_list($v) || \count($v) > $def['max_items']) {
                    $erreurs[] = sprintf('%s : liste de %d éléments maximum attendue.', $ou, $def['max_items']);

                    return null;
                }
                $liste = [];
                foreach ($v as $element) {
                    if (!\is_string($element) || mb_strlen($element) > (int) $def['max']) {
                        $erreurs[] = sprintf('%s : élément invalide (%d caractères maximum).', $ou, $def['max']);

                        return null;
                    }
                    $liste[] = trim($element);
                }

                return $liste;

            case 'trace':
                if (!\is_string($v) || !Uuid::isValid($v) || !$contexte->traceAppartient(strtolower($v))) {
                    $erreurs[] = sprintf('%s : cette trace n\'existe pas ou ne vous appartient pas.', $ou);

                    return null;
                }

                return strtolower($v);

            case 'media':
                return $this->media($v, $contexte, $ou, $erreurs);

            case 'liste_media':
                if (!\is_array($v) || !array_is_list($v) || \count($v) > $def['max_items']) {
                    $erreurs[] = sprintf('%s : %d médias maximum.', $ou, $def['max_items']);

                    return null;
                }
                $liste = [];
                foreach ($v as $i => $media) {
                    $m = $this->media($media, $contexte, sprintf('%s n°%d', $ou, $i + 1), $erreurs);
                    if ($m !== null) {
                        $liste[] = $m;
                    }
                }

                return $liste;
        }

        $erreurs[] = sprintf('%s : type d\'attribut inconnu.', $ou);

        return null;
    }

    /** @param list<string> $erreurs */
    private function url(mixed $v, bool $embed, string $ou, array &$erreurs): ?string
    {
        $v = \is_string($v) ? trim($v) : '';
        if ($v === '' || mb_strlen($v) > 2048 || filter_var($v, \FILTER_VALIDATE_URL) === false
            || !\in_array(strtolower((string) parse_url($v, \PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $erreurs[] = sprintf('%s : adresse http(s) valide attendue.', $ou);

            return null;
        }
        if ($embed && !EmbedPolicy::isAutorise($v)) {
            $erreurs[] = sprintf('%s : ce site ne fait pas partie des contenus intégrables autorisés.', $ou);

            return null;
        }

        return $v;
    }

    /**
     * Un média est soit un média d'une trace du propriétaire, soit une image externe en http(s).
     *
     * @param list<string> $erreurs
     *
     * @return array{trace: string, media: int}|array{url: string}|null
     */
    private function media(mixed $v, ContexteBlocs $contexte, string $ou, array &$erreurs): ?array
    {
        if (!\is_array($v)) {
            $erreurs[] = sprintf('%s : média invalide.', $ou);

            return null;
        }
        if (isset($v['trace'], $v['media'])) {
            if (!\is_string($v['trace']) || !Uuid::isValid($v['trace']) || !is_numeric($v['media']) || !$contexte->mediaAppartient(strtolower($v['trace']), (int) $v['media'])) {
                $erreurs[] = sprintf('%s : ce média n\'existe pas ou ne vous appartient pas.', $ou);

                return null;
            }

            return ['trace' => strtolower($v['trace']), 'media' => (int) $v['media']];
        }
        if (isset($v['url'])) {
            $url = $this->url($v['url'], false, $ou, $erreurs);

            return $url !== null ? ['url' => $url] : null;
        }
        $erreurs[] = sprintf('%s : choisissez un média.', $ou);

        return null;
    }
}
