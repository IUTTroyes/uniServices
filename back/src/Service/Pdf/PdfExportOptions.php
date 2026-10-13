<?php

declare(strict_types=1);

namespace App\Service\Pdf;

use DateTimeInterface;
use Sensiolabs\GotenbergBundle\Enumeration\PaperSize;

/**
 * DTO de configuration pour la génération centralisée de PDF via Gotenberg Chromium.
 */
class PdfExportOptions
{
    public function __construct(
        public bool $landscape = false,
        public PaperSize $paperSize = PaperSize::A4,
        public bool $withHeader = true,
        public bool $withFooter = true,
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $departement = null,
        public ?string $anneeUniversitaire = null,
        public ?string $etablissement = 'IUT de Troyes',
        public ?DateTimeInterface $generationDate = null,
        public string $generationMention = "Document généré automatiquement depuis l'intranet uniServices",
        public ?string $generatedBy = null,
        public float $marginTop = 1.4,
        public float $marginBottom = 1.2,
        public float $marginLeft = 1.0,
        public float $marginRight = 1.0,
        public string $headerTemplate = 'pdf/_header.html.twig',
        public string $footerTemplate = 'pdf/_footer.html.twig',
        public array $extraContext = [],
    ) {
    }

    public static function create(): self
    {
        return new self();
    }

    public function setLandscape(bool $landscape = true): self
    {
        $this->landscape = $landscape;
        return $this;
    }

    public function setWithHeader(bool $withHeader): self
    {
        $this->withHeader = $withHeader;
        return $this;
    }

    public function setWithFooter(bool $withFooter): self
    {
        $this->withFooter = $withFooter;
        return $this;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setSubtitle(?string $subtitle): self
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function setDepartement(?string $departement): self
    {
        $this->departement = $departement;
        return $this;
    }

    public function setAnneeUniversitaire(?string $anneeUniv): self
    {
        $this->anneeUniversitaire = $anneeUniv;
        return $this;
    }

    public function setEtablissement(?string $etablissement): self
    {
        $this->etablissement = $etablissement;
        return $this;
    }

    public function setGenerationDate(?DateTimeInterface $date): self
    {
        $this->generationDate = $date;
        return $this;
    }

    public function setGenerationMention(string $mention): self
    {
        $this->generationMention = $mention;
        return $this;
    }

    public function setGeneratedBy(?string $name): self
    {
        $this->generatedBy = $name;
        return $this;
    }

    public function setMargins(float $top, float $bottom, float $left, float $right): self
    {
        $this->marginTop = $top;
        $this->marginBottom = $bottom;
        $this->marginLeft = $left;
        $this->marginRight = $right;
        return $this;
    }

    public function setHeaderTemplate(string $template): self
    {
        $this->headerTemplate = $template;
        return $this;
    }

    public function setFooterTemplate(string $template): self
    {
        $this->footerTemplate = $template;
        return $this;
    }

    public function setExtraContext(array $context): self
    {
        $this->extraContext = $context;
        return $this;
    }
}
