<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Components/Questionnaire/Section/ConfigurableSection.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 23/02/2024 21:35
 */

namespace App\Components\Questionnaire\Section;

use App\Components\Questionnaire\Exceptions\TypeQuestionNotFoundException;
use App\Entity\Semestre;

class ConfigurableSection extends AbstractSection
{
    final public const LABEL = 'configurable.section';
    final public const NB_QUESTIONS_PAR_SECTION = 1; //todo: extraire ce paramètre pour en faire une configuration par section
    public ?AbstractSectionAdapter $sectionAdapter = null;
    public ?array $config = [];
    public string $type_calcul = 'GROUPE'; //todo: devrait être paramétrable??
    public array $sections = []; // en mode configurable, on peut avoir la création de sections

    /**
     * @throws TypeQuestionNotFoundException
     */
    public function initConfigGlobale(?array $config = []): void
    {
        $this->sectionAdapter = $this->questionnaireRegistry->getSectionAdapter($config['configSection']);
    }

    public function initConfigSection(?array $config = []): void
    {
        $this->config = $config;
    }

    // todo: ajouter un libelle sur la section pour faciliter la gestion

    /**
     * @throws TypeQuestionNotFoundException
     */
    public function setSection(\App\Components\Questionnaire\DTO\Section $section, array $options = []): void // peut être passer par un dto car on dépend de la BDD...
    {
        $this->options = $options;

        $this->section = $section;
        $this->options['type_calcul'] = AbstractSection::AFFICHE_GROUPE;

        $this->initConfigGlobale($section->config);
        $this->initConfigSection($section->config);
    }

    public function genereSections(): array
    {
        //todo: ne pas créer de pagination si c'est le mode resultat qui est utilisé
        $valeursParSection = [];
        if (is_array($this->config) && array_key_exists('valeurs', $this->config) && is_array($this->config['valeurs'])) {
            $nbSections = ceil(count($this->config['valeurs']) / self::NB_QUESTIONS_PAR_SECTION);
            for ($i = 1; $i <= $nbSections; ++$i) {
                $valeursParSection[$i] = array_slice($this->config['valeurs'], ($i - 1) * self::NB_QUESTIONS_PAR_SECTION, self::NB_QUESTIONS_PAR_SECTION);
                $numSection = $this->section->ordre . '-' . $i;
                $this->sections[$numSection] = new Section($this->questionnaireRegistry, $this->graphRegistry);
                $newSection = clone $this->section; // clonage pour gérer indépendement les sections ?

                // Définir les éléments liés ) la configuration
                $this->sections[$numSection]->nbParties = $this->getQuestionsParPartie($i);
                $this->sections[$numSection]->params = ['valeurs' => $valeursParSection[$i]];
                $this->sections[$numSection]->configurable = true;
                $this->sections[$numSection]->abstractSectionAdapter = $this->sectionAdapter;

                $newSection->ordre = $numSection;
                $this->sections[$numSection]->setSection($newSection, $this->options);
            }

            return $this->sections;
        }

        return [];
    }

    private function getQuestionsParPartie(int $i): int
    {
        if ($i * self::NB_QUESTIONS_PAR_SECTION <= (is_countable($this->config['valeurs']) ? count($this->config['valeurs']) : 0)) {
            return self::NB_QUESTIONS_PAR_SECTION;
        }

        return (is_countable($this->config['valeurs']) ? count($this->config['valeurs']) : 0) % self::NB_QUESTIONS_PAR_SECTION;
    }

    public function getDataPourConfiguration(Semestre $semestre): array
    {
        if (null === $this->config || !array_key_exists('valeurs',
                $this->config) || $this->config['valeurs'] === null) {
            $this->config['valeurs'] = [];
        }

        return $this->sectionAdapter->getAllDataSemestre($semestre, $this->config['valeurs']);
    }
}
