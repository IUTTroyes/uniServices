<?php

namespace UnifolioBundle\Service\Campagne;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureGroupe;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Campagne;

/** Étudiants (par leur scolarité) concernés par une campagne : même diplôme, même année universitaire. */
class CampagneEtudiants
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return array<int, EtudiantScolarite> indexées par identifiant d'étudiant, triées par nom */
    public function scolarites(Campagne $campagne): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('DISTINCT es', 'e')
            ->from(EtudiantScolarite::class, 'es')
            ->join('es.etudiant', 'e')
            ->join('es.scolariteSemestre', 'ess')
            ->join('ess.semestre', 's')
            ->join('s.annee', 'a')
            ->join('a.pn', 'pn')
            ->andWhere('pn.diplome = :diplome')->setParameter('diplome', $campagne->getDiplome())
            ->andWhere('es.anneeUniversitaire = :annee')->setParameter('annee', $campagne->getAnneeUniversitaire())
            ->orderBy('e.nom', 'ASC')->addOrderBy('e.prenom', 'ASC');
        if ($campagne->getAnneeFormation() !== null) {
            $qb->andWhere('a = :anneeFormation')->setParameter('anneeFormation', $campagne->getAnneeFormation());
        }

        $parEtudiant = [];
        foreach ($qb->getQuery()->getResult() as $scolarite) {
            $parEtudiant[$scolarite->getEtudiant()->getId()] ??= $scolarite;
        }

        return $parEtudiant;
    }

    /** @return array<int, EtudiantScolarite> étudiants d'un groupe, limités à la campagne */
    public function duGroupe(Campagne $campagne, StructureGroupe $groupe): array
    {
        $dansGroupe = [];
        foreach ($groupe->getScolariteSemestres() as $scolariteSemestre) {
            $etudiant = $scolariteSemestre->getScolarite()?->getEtudiant();
            if ($etudiant !== null) {
                $dansGroupe[$etudiant->getId()] = true;
            }
        }

        return array_intersect_key($this->scolarites($campagne), $dansGroupe);
    }
}
