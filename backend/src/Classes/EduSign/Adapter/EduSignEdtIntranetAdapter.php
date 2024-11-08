<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Classes/EduSign/Adapter/EduSignEdtIntranetAdapter.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 19/02/2024 17:28
 */

namespace App\Classes\EduSign\Adapter;

use App\DTO\EvenementEdt;
use App\Entity\Personnel;
use Carbon\Carbon;
use DateTimeZone;

class EduSignEdtIntranetAdapter
{
    private EvenementEdt $cours;

    public function __construct(array $course, Personnel $enseignant)
    {
        $startRaw = Carbon::parse($course['START'], 'UTC');
        $startRaw->setTimezone(new DateTimeZone('Europe/Paris'));
        $startFormat = $startRaw->format('Y-m-d H:i:s');
        $start = Carbon::createFromFormat("Y-m-d H:i:s", $startFormat);
        $endRaw = Carbon::parse($course['END'], 'UTC');
        $endRaw->setTimezone(new DateTimeZone('Europe/Paris'));
        $endFormat = $endRaw->format('Y-m-d H:i:s');
        $end = Carbon::createFromFormat("Y-m-d H:i:s", $endFormat);
        // changer le contenu de la date -> H:i:s par 00:00:00 pour la comparer avec 'date' dans edtPlanning
        $date = $start->copy()->startOfDay()->format('Y-m-d H:i:s');
        $date = Carbon::createFromFormat("Y-m-d H:i:s", $date);

        $this->cours = new EvenementEdt();
        $this->cours->source = 'intranet';
        $this->cours->matiere = $course['NAME'];
        $this->cours->date = $date;
        $this->cours->heureDebut = $start;
        $this->cours->heureFin = $end;
        $this->cours->personnelObjet = $enseignant;
        $this->cours->idEduSign = $course['ID'];
        $this->cours->salle = $course['CLASSROOM'];
    }

    public function getCourse(): ?EvenementEdt
    {
        return $this->cours;
    }
}
