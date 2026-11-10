<?php

namespace StageBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;

/**
 * Stockage des rapports de stage, sur disque, hors webroot (paramètre stage.rapport_dir).
 * PDF uniquement, type vérifié sur le contenu du fichier (jamais sur son nom ni sur le type annoncé par le client).
 */
class StageRapportStorage
{
    public const TAILLE_MAX = 15 * 1024 * 1024;
    public const MIME = 'application/pdf';

    private readonly Filesystem $filesystem;
    private readonly string $racine;

    public function __construct(
        #[Autowire('%stage.rapport_dir%')]
        string $rapportDir,
        ?Filesystem $filesystem = null,
    ) {
        $this->racine = rtrim($rapportDir, '/');
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    /** @return string chemin relatif du fichier enregistré */
    public function enregistrer(UploadedFile $fichier): string
    {
        if (!$fichier->isValid()) {
            throw new \InvalidArgumentException('Le téléversement du fichier a échoué.');
        }
        if ((int) $fichier->getSize() > self::TAILLE_MAX) {
            throw new \InvalidArgumentException(sprintf('Le rapport dépasse la taille maximale autorisée (%d Mo).', intdiv(self::TAILLE_MAX, 1024 * 1024)));
        }
        if ($fichier->getMimeType() !== self::MIME) {
            throw new \InvalidArgumentException('Le rapport doit être un fichier PDF.');
        }

        $chemin = sprintf('%s/%s.pdf', date('Y'), Uuid::v7()->toRfc4122());
        $absolu = $this->cheminAbsolu($chemin);
        $this->filesystem->mkdir(\dirname($absolu), 0o750);
        $fichier->move(\dirname($absolu), basename($absolu));
        $this->filesystem->chmod($absolu, 0o640); // lisible par le seul serveur

        return $chemin;
    }

    public function supprimer(?string $chemin): void
    {
        if ($chemin !== null && $chemin !== '' && $this->existe($chemin)) {
            $this->filesystem->remove($this->cheminAbsolu($chemin));
        }
    }

    public function existe(string $chemin): bool
    {
        return is_file($this->cheminAbsolu($chemin));
    }

    public function cheminAbsolu(string $chemin): string
    {
        $chemin = ltrim($chemin, '/');
        if ($chemin === '' || str_contains($chemin, '..') || str_contains($chemin, "\0")) {
            throw new \InvalidArgumentException('Chemin de rapport invalide.');
        }

        return $this->racine.'/'.$chemin;
    }

    /**
     * Nom d'origine affichable : sans chemin ni caractères de contrôle, 150 caractères au plus, extension .pdf imposée
     * (le contenu est forcément un PDF).
     */
    public static function nettoyerNom(string $nom): string
    {
        $nom = basename(str_replace('\\', '/', $nom));
        $nom = trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', $nom) ?? '');
        $base = preg_replace('/\.[^.]*$/', '', $nom) ?? '';
        if ($base === '' || $base === '.') {
            $base = 'rapport';
        }

        return mb_substr($base, 0, 146).'.pdf';
    }

    /**
     * Nom imposé au téléchargement, identique pour tous et sans ambiguïté :
     * « Rapport-stage_DUPONT-Alice_Stage-BUT2.pdf » (ASCII uniquement).
     */
    public static function nomTelechargement(?string $nom, ?string $prenom, ?string $periode): string
    {
        $slugger = new AsciiSlugger('fr');
        $morceaux = ['Rapport-stage'];
        $personne = trim(sprintf('%s %s', mb_strtoupper((string) $nom), (string) $prenom));
        if ($personne !== '') {
            $morceaux[] = $slugger->slug($personne)->toString();
        }
        if ($periode !== null && trim($periode) !== '') {
            $morceaux[] = $slugger->slug($periode)->toString();
        }

        return mb_substr(implode('_', array_filter($morceaux)), 0, 150).'.pdf';
    }
}
