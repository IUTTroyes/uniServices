<?php

declare(strict_types=1);

namespace App\Extension;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Personnel;

/** Source des affectations « personnel ↔ enseignement » (le prévisionnel, quand le bundle intranet est installé). */
interface AffectationsEnseignementInterface
{
    /** @return list<Personnel> personnels affectés, référents en tête */
    public function personnels(ScolEnseignement $enseignement, ?StructureAnneeUniversitaire $annee): array;

    /** Sous-requête DQL des identifiants d'enseignements du personnel désigné par le paramètre. */
    public function dqlEnseignements(string $alias, string $parametrePersonnel, ?string $parametreAnnee = null): string;

    /** Sous-requête DQL des identifiants de personnels de l'enseignement désigné par le paramètre. */
    public function dqlPersonnels(string $alias, string $parametreEnseignement): string;

    /** Requête DQL « existe une affectation », corrélée aux expressions de la requête englobante. */
    public function dqlAffectation(string $alias, string $parametrePersonnel, string $enseignement, string $annee): string;
}
