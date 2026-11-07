<?php

namespace UnifolioBundle\Service\Migration\V1;

/** Rapport d'une migration : compteurs par type d'objet et écarts. Rien n'est ignoré sans y figurer. */
final class RapportMigration
{
    public const CREE = 'créé';
    public const DEJA = 'déjà migré';
    public const IGNORE = 'ignoré';

    /** @var array<string, array<string, int>> */
    private array $compteurs = [];

    /** @var list<array{gravite: string, type: string, v1Id: int|string|null, message: string}> */
    private array $ecarts = [];

    public function compter(string $type, string $etat): void
    {
        $this->compteurs[$type][$etat] = ($this->compteurs[$type][$etat] ?? 0) + 1;
    }

    /** @param 'erreur'|'avertissement'|'info' $gravite */
    public function ecart(string $gravite, string $type, int|string|null $v1Id, string $message): void
    {
        $this->ecarts[] = ['gravite' => $gravite, 'type' => $type, 'v1Id' => $v1Id, 'message' => $message];
    }

    /** @return array<string, array<string, int>> */
    public function compteurs(): array
    {
        ksort($this->compteurs);

        return $this->compteurs;
    }

    /** @return list<array{gravite: string, type: string, v1Id: int|string|null, message: string}> */
    public function ecarts(): array
    {
        return $this->ecarts;
    }

    public function nbEcarts(string $gravite): int
    {
        return \count(array_filter($this->ecarts, static fn (array $e) => $e['gravite'] === $gravite));
    }

    public function csv(): string
    {
        $flux = fopen('php://temp', 'r+');
        fwrite($flux, "\xEF\xBB\xBF");
        fputcsv($flux, ['gravité', 'type', 'identifiant v1', 'message'], ';', '"', '\\');
        foreach ($this->ecarts as $e) {
            // Neutralisation des formules : le message peut contenir du texte issu de la v1.
            $message = $e['message'] !== '' && str_contains("=+-@\t\r", $e['message'][0]) ? "'".$e['message'] : $e['message'];
            fputcsv($flux, [$e['gravite'], $e['type'], (string) $e['v1Id'], $message], ';', '"', '\\');
        }
        rewind($flux);
        $csv = (string) stream_get_contents($flux);
        fclose($flux);

        return $csv;
    }
}
