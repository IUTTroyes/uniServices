<?php

namespace UnifolioBundle\Service\Campagne;

use App\Entity\Structure\StructureGroupe;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\CampagneAffectation;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

class AffectationManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CampagneAffectationRepository $affectations,
        private readonly CampagneEtudiants $etudiants,
    ) {
    }

    /** L'évaluateur doit appartenir au département de la formation. */
    public function verifierEvaluateur(Campagne $campagne, Personnel $evaluateur): void
    {
        $departement = $campagne->getDiplome()->getDepartement();
        foreach ($evaluateur->getDepartementsPersonnel() as $affectation) {
            if ($affectation->getDepartement()?->getId() === $departement?->getId()) {
                return;
            }
        }
        throw UnifolioValidationException::un(sprintf('%s ne fait pas partie du département de cette formation.', $evaluateur->getDisplay()));
    }

    /**
     * Affecte un évaluateur à des étudiants de la campagne (ou à un groupe, ou à tous).
     *
     * @param int[]|null $idsEtudiants null avec $groupe null = tous les étudiants de la campagne
     *
     * @return int nombre de nouvelles affectations
     */
    public function affecter(Campagne $campagne, Personnel $evaluateur, ?array $idsEtudiants, ?StructureGroupe $groupe = null): int
    {
        $this->verifierEvaluateur($campagne, $evaluateur);
        $perimetre = $this->etudiants->scolarites($campagne);

        if ($groupe !== null) {
            $cibles = array_keys($this->etudiants->duGroupe($campagne, $groupe));
        } elseif ($idsEtudiants === null) {
            $cibles = array_keys($perimetre);
        } else {
            $cibles = array_map('intval', $idsEtudiants);
            foreach ($cibles as $id) {
                if (!isset($perimetre[$id])) {
                    throw UnifolioValidationException::un('Un des étudiants ne fait pas partie de cette campagne.');
                }
            }
        }

        $existants = [];
        foreach ($this->affectations->findParEvaluateur($evaluateur, $campagne) as $a) {
            $existants[$a->getEtudiant()->getId()] = true;
        }
        $crees = 0;
        foreach ($cibles as $id) {
            if (isset($existants[$id])) {
                continue;
            }
            $etudiant = $perimetre[$id]->getEtudiant();
            \assert($etudiant instanceof Etudiant);
            $this->em->persist(new CampagneAffectation($campagne, $evaluateur, $etudiant));
            ++$crees;
        }
        $this->em->flush();

        return $crees;
    }

    /**
     * Retire des affectations ; les évaluations déjà commencées sont conservées.
     *
     * @param int[]|null $idsEtudiants null = tous les étudiants de l'évaluateur dans cette campagne
     *
     * @return int nombre d'affectations retirées
     */
    public function retirer(Campagne $campagne, Personnel $evaluateur, ?array $idsEtudiants): int
    {
        $retirees = 0;
        foreach ($this->affectations->findParEvaluateur($evaluateur, $campagne) as $a) {
            if ($idsEtudiants === null || \in_array($a->getEtudiant()->getId(), array_map('intval', $idsEtudiants), true)) {
                $this->em->remove($a);
                ++$retirees;
            }
        }
        $this->em->flush();

        return $retirees;
    }
}
