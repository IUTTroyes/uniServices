<?php

namespace UnifolioBundle\Service\Publication;

use Symfony\Component\String\Slugger\AsciiSlugger;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Service\Portfolio\BlocValidator;
use UnifolioBundle\Service\Portfolio\PortfolioDonnees;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/**
 * Construit l'instantané d'un portfolio : tout ce qui sera rendu, et rien d'autre.
 * Minimisation des données : seules les traces effectivement montrées par un bloc sont retenues, avec une liste blanche
 * de champs ; CV, progression et parcours ne sont inclus que si un bloc les affiche. Les médias sont désignés par
 * adresses relatives à la publication (media/…), jamais par les adresses de l'API.
 */
class PublicationInstantane
{
    private const RESERVES = ['index', 'print', 'portfolio'];

    public function __construct(
        private readonly PortfolioDonnees $donnees,
        private readonly BlocValidator $validateur,
        private readonly ThemeNormalizer $themes,
    ) {
    }

    /**
     * @return array{portfolio: array<string, mixed>, pages: list<array<string, mixed>>, donnees: array<string, mixed>}
     *
     * @throws UnifolioValidationException si une page est incomplète ou si le portfolio est vide
     */
    public function construire(Portfolio $portfolio): array
    {
        $pages = [];
        $erreurs = [];
        $slugger = new AsciiSlugger('fr');
        $utilises = [];
        foreach ($portfolio->getPages() as $page) {
            foreach ($this->validateur->incomplets($page->getBlocs(), $page->getTitre()) as $anomalie) {
                $erreurs[] = $anomalie;
            }
            $base = strtolower($slugger->slug($page->getTitre())->toString()) ?: 'page';
            $fichier = $pages === [] ? 'index' : (\in_array($base, self::RESERVES, true) ? $base.'-page' : $base);
            for ($i = 2; isset($utilises[$fichier]); ++$i) {
                $fichier = $base.'-'.$i;
            }
            $utilises[$fichier] = true;
            $pages[] = ['fichier' => $fichier.'.html', 'titre' => $page->getTitre(), 'blocs' => $page->getBlocs()];
        }
        if ($pages === []) {
            throw UnifolioValidationException::un('Le portfolio ne contient aucune page.');
        }
        if ($erreurs !== []) {
            throw new UnifolioValidationException(array_merge(['Complétez les blocs suivants avant de publier :'], $erreurs));
        }

        $source = $this->donnees->pour($portfolio);
        $types = [];
        $uuidsTraces = [];
        foreach ($pages as $p) {
            $this->parcourir($p['blocs'], $types, $uuidsTraces, $source['traces']);
        }

        $etudiant = $portfolio->getEtudiant();

        return [
            'portfolio' => [
                'titre' => $portfolio->getTitre(),
                'description' => $portfolio->getDescription(),
                'auteur' => trim($etudiant->getPrenom().' '.$etudiant->getNom()),
                'indexable' => $portfolio->isIndexable(),
                'theme' => $this->themes->lire($portfolio->getTheme()),
                'logo' => LogoManager::nomPublie($portfolio) !== null
                    ? ['fichier' => LogoManager::nomPublie($portfolio), 'alt' => 'Logo de '.trim($etudiant->getPrenom().' '.$etudiant->getNom())]
                    : null,
            ],
            'pages' => $pages,
            'donnees' => [
                'traces' => $this->traces($source['traces'], $uuidsTraces),
                'progression' => isset($types['progression']) ? $source['progression'] : [],
                'cv' => isset($types['cv']) ? $this->cv($source['cv']) : $this->cvVide(),
                'parcours' => isset($types['parcours']) ? $this->parcours($source['parcours']) : [],
            ],
        ];
    }

    /** @return list<array{mediaId: int, destination: string}> médias à copier, dérivés de l'instantané */
    public static function medias(array $instantane): array
    {
        $liste = [];
        foreach ($instantane['donnees']['traces'] ?? [] as $trace) {
            foreach ($trace['medias'] ?? [] as $media) {
                if (isset($media['fichier']['mediaId'], $media['fichier']['url'])) {
                    $liste[] = ['mediaId' => (int) $media['fichier']['mediaId'], 'destination' => (string) $media['fichier']['url']];
                }
            }
        }

        return $liste;
    }

    /**
     * @param list<array<string, mixed>> $blocs
     * @param array<string, true>        $types
     * @param array<string, true>        $uuids
     * @param list<array<string, mixed>> $traces
     */
    private function parcourir(array $blocs, array &$types, array &$uuids, array $traces): void
    {
        foreach ($blocs as $bloc) {
            $type = $bloc['type'] ?? '';
            $attrs = $bloc['attrs'] ?? [];
            $types[$type] = true;
            switch ($type) {
                case 'trace':
                    if (!empty($attrs['trace'])) {
                        $uuids[strtolower((string) $attrs['trace'])] = true;
                    }
                    break;
                case 'traces':
                    $competence = $attrs['competence'] ?? null;
                    $retenues = array_values(array_filter(
                        $traces,
                        static fn (array $t) => !$competence || array_filter($t['competences'] ?? [], static fn (array $c) => (int) $c['id'] === (int) $competence) !== []
                    ));
                    foreach (\array_slice($retenues, 0, (int) ($attrs['maximum'] ?? 6)) as $t) {
                        $uuids[strtolower((string) $t['uuid'])] = true;
                    }
                    break;
                case 'image':
                case 'file':
                    if (!empty($attrs['source']['trace'])) {
                        $uuids[strtolower((string) $attrs['source']['trace'])] = true;
                    }
                    break;
                case 'gallery':
                    foreach ($attrs['images'] ?? [] as $image) {
                        if (!empty($image['trace'])) {
                            $uuids[strtolower((string) $image['trace'])] = true;
                        }
                    }
                    break;
            }
            if (!empty($bloc['children'])) {
                $this->parcourir($bloc['children'], $types, $uuids, $traces);
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $traces
     * @param array<string, true>        $retenues
     *
     * @return list<array<string, mixed>>
     */
    private function traces(array $traces, array $retenues): array
    {
        $resultat = [];
        foreach ($traces as $t) {
            if (!isset($retenues[strtolower((string) $t['uuid'])])) {
                continue;
            }
            $court = substr(str_replace('-', '', (string) $t['uuid']), 0, 8);
            $adresses = [];
            $medias = [];
            foreach ($t['medias'] ?? [] as $m) {
                $fichier = null;
                if (!empty($m['fichier']['url'])) {
                    $extension = $this->extension((string) ($m['fichier']['nom'] ?? ''), (string) ($m['fichier']['mimeType'] ?? ''));
                    $destination = sprintf('media/%s-%d.%s', $court, (int) $m['id'], $extension);
                    $adresses[$m['fichier']['url']] = $destination;
                    $fichier = ['url' => $destination, 'nom' => (string) ($m['fichier']['nom'] ?? 'fichier'), 'mediaId' => (int) $m['id']];
                }
                $medias[] = [
                    'id' => (int) $m['id'],
                    'type' => $m['type'],
                    'icone' => $m['icone'] ?? 'pi pi-file',
                    'legende' => $m['legende'] ?? null,
                    'fichier' => $fichier,
                    'url' => \is_string($m['url'] ?? null) && preg_match('#^https?://#i', $m['url']) ? $m['url'] : null,
                ];
            }
            $resultat[] = [
                'uuid' => $t['uuid'],
                'titre' => $t['titre'],
                'contexte' => $t['contexte'] ?? null,
                'dateRealisation' => $t['dateRealisation'] ?? null,
                'typeActivite' => isset($t['typeActivite']['libelle']) ? ['libelle' => $t['typeActivite']['libelle']] : null,
                'vignette' => $adresses[$t['vignette'] ?? ''] ?? null,
                'competences' => array_map(static fn (array $c) => ['id' => $c['id'], 'nomCourt' => $c['nomCourt'], 'couleur' => $c['couleur'] ?? null], $t['competences'] ?? []),
                'medias' => $medias,
            ];
        }

        return $resultat;
    }

    /** Extension sûre : liste blanche de formats d'image et de documents courants, sinon .bin (téléchargement). */
    private function extension(string $nom, string $mime): string
    {
        $parMime = [
            'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/avif' => 'avif',
            'application/pdf' => 'pdf', 'video/mp4' => 'mp4', 'video/webm' => 'webm', 'audio/mpeg' => 'mp3',
            'application/zip' => 'zip', 'text/plain' => 'txt',
        ];

        return $parMime[strtolower($mime)] ?? 'bin';
    }

    /** @param array<string, mixed> $cv */
    private function cv(array $cv): array
    {
        return array_intersect_key($cv, array_flip(['poste', 'description', 'langues', 'softSkills', 'hardSkills', 'reseaux', 'experiences', 'formations']));
    }

    private function cvVide(): array
    {
        return ['poste' => null, 'description' => null, 'langues' => [], 'softSkills' => [], 'hardSkills' => [], 'reseaux' => [], 'experiences' => [], 'formations' => []];
    }

    /**
     * @param list<array<string, mixed>> $parcours
     *
     * @return list<array<string, mixed>>
     */
    private function parcours(array $parcours): array
    {
        return array_map(static fn (array $p) => ['annee' => $p['annee'] ?? null, 'anneeUniversitaire' => $p['anneeUniversitaire'] ?? null, 'diplome' => $p['diplome'] ?? null, 'actif' => (bool) ($p['actif'] ?? false)], $parcours);
    }
}
