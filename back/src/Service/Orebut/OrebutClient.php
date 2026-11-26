<?php

declare(strict_types=1);

namespace App\Service\Orebut;

use App\Repository\EtablissementRepository;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Lecture de l'API publique d'ORéBUT, outil national des programmes de BUT. Seule classe à connaître ses adresses
 * et ses formats : le reste de l'application manipule les tableaux qu'elle retourne.
 *
 * @phpstan-type Parcours array{code: string, libelle: string, couleur: ?string, niveaux: list<int>}
 * @phpstan-type Specialite array{sigle: string, libelle: string, parcours: list<Parcours>}
 */
class OrebutClient
{
    public const URL_PAR_DEFAUT = 'https://orebut.iut.fr';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EtablissementRepository $etablissements,
    ) {
    }

    /** @return list<Specialite> triées par sigle */
    public function specialites(): array
    {
        $specialites = [];
        foreach ($this->collection('/api/departements') as $departement) {
            if (empty($departement['sigle'])) {
                continue;
            }
            $parcours = [];
            foreach ($departement['apcParcours'] ?? [] as $p) {
                if (empty($p['code'])) {
                    continue;
                }
                $parcours[] = [
                    'code' => (string) $p['code'],
                    'libelle' => (string) ($p['libelle'] ?? $p['code']),
                    'couleur' => isset($p['couleur']) ? (string) $p['couleur'] : null,
                    'niveaux' => array_values(array_filter(array_map(
                        static fn (array $lien): int => (int) ($lien['niveau']['id'] ?? 0),
                        $p['apcParcoursNiveaux'] ?? [],
                    ))),
                ];
            }
            $specialites[] = [
                'sigle' => (string) $departement['sigle'],
                'libelle' => (string) ($departement['libelle'] ?? $departement['sigle']),
                'parcours' => $parcours,
            ];
        }
        usort($specialites, static fn (array $a, array $b) => strnatcasecmp($a['sigle'], $b['sigle']));

        return $specialites;
    }

    /** @return list<array<string, mixed>> compétences avec composantes, niveaux et apprentissages critiques */
    public function competences(string $sigle): array
    {
        return $this->collection('/api/specialite/'.rawurlencode($sigle).'/competences');
    }

    /** @return list<array<string, mixed>> */
    private function collection(string $chemin): array
    {
        $base = $this->url();
        $elements = [];
        $vues = [];
        while ($chemin !== null && !isset($vues[$chemin])) {
            $vues[$chemin] = true;
            try {
                $donnees = $this->httpClient->request('GET', $base.$chemin, [
                    'timeout' => 60,
                    'headers' => ['Accept' => 'application/ld+json'],
                ])->toArray();
            } catch (ExceptionInterface $e) {
                throw new OrebutException('ORéBUT est injoignable ou a répondu une erreur : '.$e->getMessage(), 0, $e);
            }
            $membres = $donnees['hydra:member'] ?? $donnees['member'] ?? null;
            if (!\is_array($membres)) {
                throw new OrebutException('Réponse d’ORéBUT non reconnue pour '.$chemin.'.');
            }
            array_push($elements, ...array_values($membres));
            $suite = $donnees['hydra:view']['hydra:next'] ?? $donnees['view']['next'] ?? null;
            $chemin = \is_string($suite) && str_starts_with($suite, '/') ? $suite : null;
        }

        return $elements;
    }

    private function url(): string
    {
        $reglage = $this->etablissements->findOneBy(['isMain' => true])?->getSettings()['integrations']['orebut']['apiUrl'] ?? null;

        return rtrim(\is_string($reglage) && trim($reglage) !== '' ? trim($reglage) : self::URL_PAR_DEFAUT, '/');
    }
}
