<?php

namespace UnifolioBundle\Service\Portfolio;

use UnifolioBundle\Entity\Cv;
use UnifolioBundle\Entity\CvExperience;
use UnifolioBundle\Entity\CvFormation;

class CvNormalizer
{
    /** @return array<string, mixed> */
    public function document(Cv $cv): array
    {
        $date = static fn (?\DateTimeImmutable $d) => $d?->format('Y-m-d');

        return [
            'poste' => $cv->getPoste(),
            'description' => $cv->getDescription(),
            'langues' => $cv->getLangues(),
            'softSkills' => $cv->getSoftSkills(),
            'hardSkills' => $cv->getHardSkills(),
            'reseaux' => $cv->getReseaux(),
            'experiences' => array_map(static fn (CvExperience $e) => [
                'poste' => $e->getPoste(), 'entreprise' => $e->getEntreprise(), 'dateDebut' => $date($e->getDateDebut()), 'dateFin' => $date($e->getDateFin()), 'description' => $e->getDescription(),
            ], $cv->getExperiences()->toArray()),
            'formations' => array_map(static fn (CvFormation $f) => [
                'diplome' => $f->getDiplome(), 'etablissement' => $f->getEtablissement(), 'dateDebut' => $date($f->getDateDebut()), 'dateFin' => $date($f->getDateFin()), 'description' => $f->getDescription(),
            ], $cv->getFormations()->toArray()),
            'misAJourLe' => $cv->getUpdatedAt()?->format(\DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> CV vide, pour un étudiant qui n'en a pas encore */
    public function vide(): array
    {
        return ['poste' => null, 'description' => null, 'langues' => [], 'softSkills' => [], 'hardSkills' => [], 'reseaux' => [], 'experiences' => [], 'formations' => [], 'misAJourLe' => null];
    }
}
