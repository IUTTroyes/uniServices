<?php

declare(strict_types=1);

namespace IntranetBundle\Commission\Service;

use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use App\Repository\EtudiantScolariteSemestreRepository;
use Doctrine\ORM\EntityManagerInterface;
use IntranetBundle\Commission\Contract\CommissionDataLoaderInterface;
use IntranetBundle\Commission\Dto\CommissionDataset;

/** Chargement par lots et calcul courant : aucune moyenne persistée n’est relue. */
final readonly class CommissionDataLoader implements CommissionDataLoaderInterface
{
    public function __construct(private EtudiantScolariteSemestreRepository $inscriptions, private EntityManagerInterface $em, private MoyennesCalculator $calculator) {}

    public function loadDataset(StructureSemestre $semestre): CommissionDataset
    {
        $inscriptions = $this->inscriptions->createQueryBuilder('ess')
            ->leftJoin('ess.scolarite', 'scol')->leftJoin('scol.etudiant', 'e')->leftJoin('e.bac', 'b')
            ->leftJoin('ess.groupes', 'g')->addSelect('scol', 'e', 'b', 'g')
            ->where('ess.semestre = :semestre')->setParameter('semestre', $semestre)->orderBy('ess.id', 'ASC')->getQuery()->getResult();
        $ues = $this->em->getRepository(StructureUe::class)->createQueryBuilder('u')
            ->leftJoin('u.enseignementUes', 'l')->leftJoin('l.enseignement', 'ens')->addSelect('l', 'ens')
            ->where('u.semestre = :semestre')->setParameter('semestre', $semestre)->orderBy('u.id', 'ASC')->getQuery()->getResult();
        $evaluations = $this->em->getRepository(ScolEvaluation::class)->createQueryBuilder('e')
            ->select('e.id, IDENTITY(e.enseignement) AS enseignement, IDENTITY(e.anneeUniversitaire) AS anneeUniversitaire, e.coeff, e.typeGroupe')
            ->where('e.semestre = :semestre')->setParameter('semestre', $semestre)->orderBy('e.id', 'ASC')->getQuery()->getArrayResult();
        $notes = $this->em->getRepository(EtudiantNote::class)->createQueryBuilder('n')
            ->join('n.evaluation', 'e')->select('n.id, IDENTITY(n.evaluation) AS evaluation, IDENTITY(n.scolariteSemestre) AS inscription, n.note, n.presenceStatut, n.historique')
            ->where('e.semestre = :semestre')->setParameter('semestre', $semestre)->orderBy('n.id', 'ASC')->getQuery()->getArrayResult();
        $sources = ['regles' => MoyennesCalculator::REGLES, 'semestreId' => $semestre->getId(), 'version' => $semestre->getResultatsVersion(),
            'anneeUniversitaireId' => $semestre->getAnnee()?->getPn()?->getAnneeUniversitaire()?->getId(),
            'apc' => $semestre->getAnnee()?->getTypeDiplome()?->isApc(), 'inscriptions' => [], 'ues' => [], 'evaluations' => $evaluations, 'notes' => $notes];
        foreach ($inscriptions as $inscription) {
            $types = array_values(array_unique(array_map(static fn ($g) => $g->getType()->value, $inscription->getGroupes()->toArray())));
            sort($types);
            $sources['inscriptions'][] = ['id' => $inscription->getId(), 'anneeUniversitaire' => $inscription->getScolarite()?->getAnneeUniversitaire()?->getId(), 'typesGroupes' => $types];
        }
        foreach ($ues as $ue) {
            if (!$ue->isActif()) continue;
            $liens = [];
            foreach ($ue->getEnseignementUes() as $lien) {
                $liens[] = ['id' => $lien->getId(), 'enseignement' => $lien->getEnseignement()?->getId(), 'coefficient' => $lien->getCoefficient(), 'ects' => $lien->getEcts()];
            }
            usort($liens, static fn ($a, $b) => $a['id'] <=> $b['id']);
            $sources['ues'][] = ['id' => $ue->getId(), 'coefficient' => $ue->getCoefficient(), 'ects' => $ue->getNbEcts(), 'liens' => $liens];
        }
        foreach ($sources['evaluations'] as &$evaluation) {
            $evaluation['typeGroupe'] = $evaluation['typeGroupe']?->value;
        }
        unset($evaluation);
        [$moyennesUe, $moyennesEnseignement, $erreurs] = $this->calculator->calculer($sources);
        return new CommissionDataset($semestre, $inscriptions, $moyennesUe, $moyennesEnseignement, $sources, $erreurs);
    }
}
