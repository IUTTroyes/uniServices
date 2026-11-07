<?php

namespace UnifolioBundle\Service\Publication;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Enum\TypeMediaEnum;
use UnifolioBundle\Service\Media\MediaStorageInterface;
use UnifolioBundle\Service\Media\MediaValidationException;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/**
 * Logo d'un portfolio. Formats raster uniquement (PNG, JPEG, WebP) : le SVG est exclu car il peut embarquer des scripts.
 * Le contenu est vérifié (image réelle, dimensions raisonnables), jamais le seul nom ou type déclaré.
 */
class LogoManager
{
    public const TAILLE_MAX = 2 * 1024 * 1024;
    public const COTE_MAX = 4000;
    public const EXTENSIONS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MediaStorageInterface $stockage,
    ) {
    }

    public function definir(Portfolio $portfolio, UploadedFile $fichier): Portfolio
    {
        if (!$fichier->isValid()) {
            throw UnifolioValidationException::un('Le téléversement du logo a échoué.');
        }
        if ((int) $fichier->getSize() > self::TAILLE_MAX) {
            throw UnifolioValidationException::un('Le logo dépasse 2 Mo.');
        }
        $mime = (string) $fichier->getMimeType();
        if (!isset(self::EXTENSIONS[$mime])) {
            throw UnifolioValidationException::un('Le logo doit être une image PNG, JPEG ou WebP.');
        }
        $taille = @getimagesize($fichier->getPathname());
        if ($taille === false || $taille[0] < 1 || $taille[1] < 1) {
            throw UnifolioValidationException::un('Ce fichier n\'est pas une image valide.');
        }
        if ($taille[0] > self::COTE_MAX || $taille[1] > self::COTE_MAX) {
            throw UnifolioValidationException::un(sprintf('Le logo ne doit pas dépasser %d pixels de côté.', self::COTE_MAX));
        }

        try {
            $stocke = $this->stockage->store($fichier, TypeMediaEnum::IMAGE);
        } catch (MediaValidationException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
        $ancien = $portfolio->getLogoChemin();
        $portfolio->definirLogo($stocke->chemin, $stocke->nomOriginal, $stocke->mimeType);
        $this->em->flush();
        if ($ancien !== null) {
            $this->stockage->delete($ancien);
        }

        return $portfolio;
    }

    public function retirer(Portfolio $portfolio): Portfolio
    {
        $ancien = $portfolio->getLogoChemin();
        $portfolio->retirerLogo();
        $this->em->flush();
        if ($ancien !== null) {
            $this->stockage->delete($ancien);
        }

        return $portfolio;
    }

    /** Chemin absolu du logo, ou null. */
    public function chemin(Portfolio $portfolio): ?string
    {
        $chemin = $portfolio->getLogoChemin();
        if ($chemin === null || !$this->stockage->exists($chemin)) {
            return null;
        }

        return $this->stockage->cheminAbsolu($chemin);
    }

    /** Nom du fichier du logo dans une publication (extension déduite du type contrôlé). */
    public static function nomPublie(Portfolio $portfolio): ?string
    {
        $ext = self::EXTENSIONS[(string) $portfolio->getLogoMime()] ?? null;

        return $ext !== null && $portfolio->aUnLogo() ? 'logo.'.$ext : null;
    }
}
