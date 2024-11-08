<?php
/*
 * Copyright (c) 2023. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Components/Questionnaire/DTO/AbstractQuestionnaire.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 22/01/2023 13:30
 */

namespace App\Components\Questionnaire\DTO;

use App\Entity\Semestre;
use Carbon\CarbonInterface;

class AbstractQuestionnaire
{
    public const MODE_APERCU = 'mode.apercu';
    public const MODE_EDITION = 'mode.edition';
    public const MODE_ETUDIANT = 'mode.etudiant';
    public const MODE_LECTURE_REPONSES = 'mode.lecture_reponses';
    public const MODE_RESULTAT_TABLEAU = 'mode.resultat.tableau';
    public const MODE_RESULTAT_GRAPHIQUE = 'mode.resultat.graphique';
    public const MODE_RESULTAT_EXCEL = 'mode.resultat.excel';

    public ?Semestre $semestre;
    public string $libelle;
    public string $titre;
    public ?string $texteExplication;
    public ?string $texteDebut;
    public ?string $texteFin;
    public string $uuid;
    public ?CarbonInterface $dateOuverture;
    public ?CarbonInterface $dateFermeture;

    public array $sections = [];
    public string $mode;
    public int $id;
    public string $uuidString;

    public function addSection($section): void
    {
    }

    public function getSections(): void
    {
    }
}
