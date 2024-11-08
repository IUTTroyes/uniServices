<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Components/Questionnaire/QuestionnaireRenderer.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 23/02/2024 21:35
 */

namespace App\Components\Questionnaire;

use App\Components\Questionnaire\Section\AbstractSection;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class QuestionnaireRenderer
{
    protected ?int $idQuestionnaire = null;

    public function __construct(protected Environment $twig)
    {
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function questionnaireRender(Questionnaire $questionnaire): string
    {
        return $this->twig->render($questionnaire->getOption('template'), $this->view($questionnaire));
    }

    protected function view(Questionnaire $questionnaire): array
    {
        $options = $questionnaire->getOptions();
        $this->idQuestionnaire = $questionnaire->getQuestionnaire()->id;
        $vars = [];
        $vars['sections'] = $questionnaire->getSections();
        $vars['questionnaire'] = $questionnaire;
        $vars['choixUserUuid'] = $questionnaire->getChoixUser();
        $vars['options'] = $options;
        $vars['questionnaireUuid'] = $questionnaire->getQuestionnaire()->uuidString;
        $vars['url'] = $questionnaire->getUrl();
        $vars['urlEnd'] = $questionnaire->getUrlEnd();

        return $vars;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function sectionRender(AbstractSection $section): string
    {
        return $this->twig->render($section->getTemplate(), $this->viewSection($section));
    }

    private function viewSection(AbstractSection $section): array
    {
        $options = $section->getOptions();

        $vars = $section->getVars();
        $vars['options'] = $options;
        $vars['questionnaireUuid'] = $section->questionnaireUuid;
        $vars['choixUserUuid'] = $section->choixUserUuid;
        return $vars;
    }
}
