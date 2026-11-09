<?php

namespace UnifolioBundle\Service\Publication;

use Symfony\Component\Filesystem\Filesystem;
use UnifolioBundle\Entity\PortfolioPublication;

/** Fichiers d'une publication, stockés hors webroot (un dossier par publication). Jamais de chemin fourni par l'utilisateur sans contrôle. */
class PublicationStorage
{
    /** Feuille de style commune, servie depuis le dossier de rendu (une seule copie pour toutes les publications). */
    public const FEUILLE = 'portfolio.css';

    private readonly string $racine;
    private readonly Filesystem $fs;

    public function __construct(string $unifolioPublicationsDir, private readonly string $unifolioRendererDir)
    {
        $this->racine = rtrim($unifolioPublicationsDir, '/');
        $this->fs = new Filesystem();
    }

    public function dossier(PortfolioPublication $publication): string
    {
        return $this->racine.'/'.$publication->getUuidString();
    }

    /** Nom de fichier de publication valide : pas de remontée de dossier, un seul niveau (media/…). */
    public static function nomValide(string $fichier): bool
    {
        return (bool) preg_match('#^(?:[a-z0-9][a-z0-9._-]{0,120}|media/[a-z0-9][a-z0-9._-]{0,120})$#i', $fichier) && !str_contains($fichier, '..');
    }

    public function ecrire(PortfolioPublication $publication, string $fichier, string $contenu): void
    {
        $this->verifier($fichier);
        $this->fs->dumpFile($this->dossier($publication).'/'.$fichier, $contenu);
    }

    public function copier(PortfolioPublication $publication, string $fichier, string $source): void
    {
        $this->verifier($fichier);
        $this->fs->copy($source, $this->dossier($publication).'/'.$fichier, true);
    }

    /** Chemin absolu d'un fichier, ou null s'il n'existe pas / n'est pas valide. */
    public function chemin(PortfolioPublication $publication, string $fichier): ?string
    {
        if ($fichier === self::FEUILLE) {
            $chemin = $this->unifolioRendererDir.'/dist/'.self::FEUILLE;

            return is_file($chemin) ? $chemin : null;
        }
        if (!self::nomValide($fichier)) {
            return null;
        }
        $chemin = $this->dossier($publication).'/'.$fichier;

        return is_file($chemin) ? $chemin : null;
    }

    public function lire(PortfolioPublication $publication, string $fichier): ?string
    {
        $chemin = $this->chemin($publication, $fichier);

        return $chemin !== null ? (string) file_get_contents($chemin) : null;
    }

    public function supprimer(PortfolioPublication $publication): void
    {
        $this->fs->remove($this->dossier($publication));
    }

    private function verifier(string $fichier): void
    {
        if (!self::nomValide($fichier)) {
            throw new \InvalidArgumentException(sprintf('Nom de fichier de publication invalide : %s', $fichier));
        }
    }
}
