<?php

declare(strict_types=1);

namespace IntranetBundle\Service\Enseignement;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\{StructureAnneeUniversitaire, StructureGroupe};
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Doctrine\DBAL\LockMode;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use IntranetBundle\Filter\Enseignement\AvancementEnseignementFilter;
use Doctrine\ORM\{EntityManagerInterface, QueryBuilder};
use IntranetBundle\Entity\Enseignement\AvancementEnseignement;
use IntranetBundle\Entity\Previsionnel\Previsionnel;
use IntranetBundle\Entity\PlanCours\PlanCoursSequence;
use Symfony\Component\HttpKernel\Exception\{AccessDeniedHttpException, BadRequestHttpException, ConflictHttpException, NotFoundHttpException};

final class AvancementEnseignementService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserEffectivePermissionService $permissions, private readonly AvancementEnseignementFilter $filter) {}

    /** Le rôle simulé côté navigateur ne remplace jamais les droits réels ni le prévisionnel. */
    public function contexte(Personnel $user, int $enseignementId, ?int $anneeId): array
    {
        $affectation = $this->permissions->getActiveDepartementPersonnel($user);
        if (!$this->permissions->isTeacher($user) || !in_array('intranet', $affectation?->getPackages() ?? [], true)) {
            throw new AccessDeniedHttpException('Ce suivi est réservé aux enseignants.');
        }
        $annee = $anneeId === null
            ? $this->em->getRepository(StructureAnneeUniversitaire::class)->findOneBy(['actif' => true])
            : $this->em->find(StructureAnneeUniversitaire::class, $anneeId);
        $enseignement = $this->em->find(ScolEnseignement::class, $enseignementId);
        if (!$annee || !$enseignement) throw new NotFoundHttpException('Enseignement ou année introuvable.');
        $previsionnel = $this->em->getRepository(Previsionnel::class)->findOneBy([
            'personnel' => $user, 'enseignement' => $enseignement, 'anneeUniversitaire' => $annee,
        ]);
        if (!$previsionnel) throw new AccessDeniedHttpException('Cet enseignement ne figure pas dans votre prévisionnel pour cette année.');

        // Une ressource peut être rattachée à plusieurs UE : union des groupes des semestres autorisés.
        $groupes = $this->em->createQueryBuilder()->select('DISTINCT g')->from(StructureGroupe::class, 'g')
            ->join('g.semestres', 's')->join('s.ues', 'ue')->join('ue.enseignementUes', 'eu')
            ->join('s.annee', 'a')->join('a.pn', 'pn')->join('pn.diplome', 'd')
            ->where('eu.enseignement = :enseignement')->andWhere('pn.anneeUniversitaire = :annee')
            ->andWhere('d.departement = :departement')
            ->setParameter('enseignement', $enseignement)->setParameter('annee', $annee)
            ->setParameter('departement', $affectation->getDepartement())
            ->orderBy('g.libelle', 'ASC')->addOrderBy('g.id', 'ASC')->getQuery()->getResult();
        // Refuser également un enseignement d'un autre département, même avec un prévisionnel incohérent.
        $semestreAutorise = false;
        foreach ($enseignement->getEnseignementUes() as $lien) {
            $semestre = $lien->getUe()?->getSemestre();
            if ($semestre?->getAnnee()?->getPn()?->getAnneeUniversitaire()?->getId() === $annee->getId()
                && $semestre?->getDiplome()?->getDepartement()?->getId() === $affectation->getDepartement()?->getId()) {
                $semestreAutorise = true;
                break;
            }
        }
        if (!$semestreAutorise) throw new AccessDeniedHttpException('Enseignement hors du département ou de l’année sélectionnée.');
        return [$enseignement, $annee, $groupes];
    }

    public function liste(array $contexte): array
    {
        [$enseignement, $annee, $groupes] = $contexte;
        $derniers = [];
        if ($groupes !== []) {
            // Une seule révision par groupe, sans charger l'historique complet.
            $latestIds = $this->query($enseignement, $annee)->select('MAX(v.id)')->andWhere('v.groupe IN (:groupes)')
                ->setParameter('groupes', $groupes)->groupBy('v.groupe')->getQuery()->getSingleColumnResult();
            if ($latestIds !== []) {
                $versions = $this->em->createQueryBuilder()->select('v')->from(AvancementEnseignement::class, 'v')
                    ->where('v.id IN (:ids)')->setParameter('ids', $latestIds)->getQuery()->getResult();
                foreach ($versions as $version) $derniers[$version->getGroupe()->getId()] = $version->toArray();
            }
        }
        return ['anneeUniversitaire' => $annee->getId(), 'anneeLibelle' => $annee->getLibelle(),
            'sequences' => $this->sequences($enseignement, $annee), 'groupes' => array_map(static fn (StructureGroupe $groupe) => [
            'id' => $groupe->getId(), 'libelle' => $groupe->getLibelle(), 'type' => $groupe->getType()->value,
            'avancement' => $derniers[$groupe->getId()] ?? null,
        ], $groupes)];
    }

    public function historique(array $contexte, int $groupeId, ?int $avant): array
    {
        [$enseignement, $annee] = $contexte;
        $groupe = $this->groupe($contexte, $groupeId);
        $query = $this->query($enseignement, $annee, $groupe)->orderBy('v.id', 'DESC')->setMaxResults(21);
        if ($avant !== null) $this->filter->apply($query, new QueryNameGenerator(), AvancementEnseignement::class, context: ['filters' => ['avant' => $avant]]);
        $versions = $query->getQuery()->getResult();
        $hasMore = count($versions) > 20;
        $items = array_map(static fn (AvancementEnseignement $v) => $v->toArray(), array_slice($versions, 0, 20));
        return ['items' => $items, 'avant' => $hasMore ? end($items)['id'] : null];
    }

    public function enregistrer(array $contexte, int $groupeId, Personnel $auteur, array $payload): array
    {
        [$enseignement, $annee] = $contexte;
        $groupe = $this->groupe($contexte, $groupeId);
        $repere = $payload['repere'] ?? '';
        $sequenceId = $payload['sequenceId'] ?? null;
        $etatSequence = $payload['etatSequence'] ?? null;
        $etape = $payload['prochaineEtape'] ?? '';
        $versionId = $payload['versionId'] ?? null;
        if (!is_string($repere) || mb_strlen($repere) > 2000
            || !is_string($etape) || mb_strlen($etape) > 2000 || !is_int($versionId) || $versionId < 0) {
            throw new BadRequestHttpException('Les textes sont limités à 2 000 caractères par champ et la version doit être renseignée.');
        }
        if ($sequenceId !== null && (!is_int($sequenceId) || $sequenceId < 1)) {
            throw new BadRequestHttpException('Séquence invalide.');
        }
        if ($sequenceId === null && (trim($repere) === '' || $etatSequence !== null)) {
            throw new BadRequestHttpException('Sans séquence, renseignez un repère libre et laissez l’état de séquence vide.');
        }
        if ($sequenceId !== null && !in_array($etatSequence, ['en_cours', 'terminee'], true)) {
            throw new BadRequestHttpException('Choisissez l’état En cours ou Terminée pour la séquence.');
        }
        return $this->em->wrapInTransaction(function () use ($enseignement, $annee, $groupe, $auteur, $repere, $etape, $versionId, $sequenceId, $etatSequence): array {
            // Verrou stable même avant la première révision, pour protéger les saisies simultanées.
            $this->em->lock($enseignement, LockMode::PESSIMISTIC_WRITE);
            $dernier = $this->query($enseignement, $annee, $groupe)->orderBy('v.id', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
            if (($dernier?->getId() ?? 0) !== $versionId) {
                throw new ConflictHttpException('Un collègue a modifié ce repère. Consultez sa version avant de réenregistrer votre saisie.');
            }
            $sequence = null;
            if ($sequenceId !== null) {
                $selection = $this->em->find(PlanCoursSequence::class, $sequenceId);
                if (!$selection || $selection->getPlanCours()?->getEnseignement()?->getId() !== $enseignement->getId()
                    || $selection->getPlanCours()?->getAnneeUniversitaire()?->getId() !== $annee->getId()) {
                    throw new BadRequestHttpException('Cette séquence n’appartient plus au plan de cet enseignement pour cette année. Choisissez une autre séquence ou un repère libre.');
                }
                $sequence = $this->snapshot($selection);
            }
            if ($dernier && $dernier->getRepere() === trim($repere) && $dernier->getProchaineEtape() === trim($etape)
                && $dernier->getSequenceSnapshot() === $sequence && $dernier->getEtatSequence() === $etatSequence) return $dernier->toArray();
            $version = new AvancementEnseignement($enseignement, $groupe, $annee, $auteur, trim($repere), trim($etape), $sequence, $etatSequence);
            $this->em->persist($version);
            $this->em->flush();
            return $version->toArray();
        });
    }

    private function sequences(ScolEnseignement $enseignement, StructureAnneeUniversitaire $annee): array
    {
        $sequences = $this->em->createQueryBuilder()->select('s')->from(PlanCoursSequence::class, 's')
            ->join('s.planCours', 'p')->where('p.enseignement = :enseignement')->andWhere('p.anneeUniversitaire = :annee')
            ->setParameter('enseignement', $enseignement)->setParameter('annee', $annee)
            ->orderBy('s.ordre', 'ASC')->addOrderBy('s.id', 'ASC')->getQuery()->getResult();
        return array_map($this->snapshot(...), $sequences);
    }

    private function snapshot(PlanCoursSequence $sequence): array
    {
        return ['id' => $sequence->getId(), 'ordre' => $sequence->getOrdre(), 'programme' => $sequence->getProgramme()];
    }

    private function groupe(array $contexte, int $id): StructureGroupe
    {
        foreach ($contexte[2] as $groupe) if ($groupe->getId() === $id) return $groupe;
        throw new AccessDeniedHttpException('Ce groupe n’appartient pas aux semestres de cet enseignement.');
    }

    private function query(ScolEnseignement $enseignement, StructureAnneeUniversitaire $annee, ?StructureGroupe $groupe = null): QueryBuilder
    {
        $query = $this->em->createQueryBuilder()->select('v')->from(AvancementEnseignement::class, 'v');
        $filters = ['enseignement' => $enseignement->getId(), 'anneeUniversitaire' => $annee->getId()];
        if ($groupe) $filters['groupe'] = $groupe->getId();
        // Les Providers personnalisés n'exécutent pas les extensions ORM du CollectionProvider.
        $this->filter->apply($query, new QueryNameGenerator(), AvancementEnseignement::class, context: ['filters' => $filters]);
        return $query;
    }
}
