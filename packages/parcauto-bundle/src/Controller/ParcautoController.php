<?php

declare(strict_types=1);

namespace ParcautoBundle\Controller;

use App\AnneeUniversitaire\ContexteAnnuel;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use ParcautoBundle\Entity\EcheanceEntretien;
use ParcautoBundle\Entity\Equipement;
use ParcautoBundle\Entity\JournalVehicule;
use ParcautoBundle\Entity\Reservation;
use ParcautoBundle\Entity\Vehicule;
use ParcautoBundle\Security\ParcautoVoter;
use ParcautoBundle\Service\GestionParcauto;
use ParcautoBundle\Service\OrdreMissionOmer;
use ParcautoBundle\Service\PlanningParcauto;
use ParcautoBundle\Service\SaisieParcauto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/parcauto', name: 'parcauto_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[IsGranted(ParcautoVoter::ACCES)]
#[ContexteAnnuel(ContexteAnnuel::INDEPENDANT, 'Le suivi du parc est continu.')]
final class ParcautoController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PlanningParcauto $planning,
        private readonly GestionParcauto $gestion,
        private readonly OrdreMissionOmer $omer,
    ) {
    }

    #[Route('', name: 'parc', methods: ['GET'])]
    public function parc(): JsonResponse
    {
        $gestion = $this->isGranted(ParcautoVoter::GESTION);
        $maintenant = SaisieParcauto::maintenant();
        $vehicules = [];
        $plans = $this->planning->echeancesParVehicule();
        foreach ($this->em->getRepository(Vehicule::class)->findBy([], ['site' => 'ASC', 'libelle' => 'ASC']) as $vehicule) {
            $vehicules[] = [
                'id' => $vehicule->id,
                'libelle' => $vehicule->libelle,
                'immatriculation' => $vehicule->immatriculation,
                'site' => $vehicule->site,
                'energie' => $vehicule->energie,
                'kilometrage' => $vehicule->kilometrage,
                'actif' => $vehicule->actif,
                'blocage' => $this->planning->blocage($vehicule, $maintenant, $vehicule->kilometrage, $plans[$vehicule->id] ?? []),
            ] + ($gestion ? [
                'misEnServiceLe' => $this->jour($vehicule->misEnServiceLe),
                'reformePrevueLe' => $this->jour($vehicule->reformePrevueLe),
                'reformeKm' => $vehicule->reformeKm,
                'achatCentimes' => $vehicule->achatCentimes,
                'consommationReference' => $vehicule->consommationReference,
            ] : []);
        }

        $equipements = $this->em->getRepository(Equipement::class)->findBy(
            $gestion ? [] : ['type' => Equipement::BORNE, 'actif' => true],
            ['libelle' => 'ASC'],
        );

        return $this->json([
            'gestion' => $gestion,
            'vehicules' => $vehicules,
            'equipements' => array_map(static fn (Equipement $e) => [
                'id' => $e->id, 'type' => $e->type, 'reference' => $e->reference, 'libelle' => $e->libelle,
                'coordonnees' => $e->coordonnees, 'vehicule' => $e->vehicule?->id, 'actif' => $e->actif,
            ], $equipements),
        ]);
    }

    #[Route('/planning', name: 'planning', methods: ['GET'])]
    public function planning(Request $request): JsonResponse
    {
        return $this->executer(function () use ($request): array {
            $saisie = new SaisieParcauto($request->query->all());
            $debut = $saisie->date('debut');
            $fin = $saisie->date('fin');
            if ($fin <= $debut || $fin > $debut->modify('+45 days')) {
                throw new \InvalidArgumentException('Sélectionnez une période de 45 jours maximum.');
            }

            $conducteur = $this->personnel();
            $gestion = $this->isGranted(ParcautoVoter::GESTION);
            $aujourdhui = SaisieParcauto::maintenant()
                ->setTimezone(new \DateTimeZone('Europe/Paris'))->setTime(0, 0)
                ->setTimezone(new \DateTimeZone('UTC'));
            $qb = $this->em->createQueryBuilder()
                ->select('r', 'v')
                ->from(Reservation::class, 'r')
                ->join('r.vehicule', 'v')
                ->where('r.debut < :fin AND r.fin > :debut')
                ->setParameter('debut', $debut)
                ->setParameter('fin', $fin)
                ->orderBy('r.debut', 'ASC');

            if ($debut <= $aujourdhui && $aujourdhui < $fin) {
                $qb->orWhere('(r.statut = :enCours AND r.fin <= :aujourdhui)')
                    ->setParameter('enCours', Reservation::EN_COURS)
                    ->setParameter('aujourdhui', $aujourdhui);
            }

            if (!$gestion) {
                $qb->andWhere('r.conducteur = :conducteur OR r.statut IN (:occupants)')
                    ->setParameter('conducteur', $conducteur)
                    ->setParameter('occupants', [Reservation::VALIDEE, Reservation::EN_COURS]);
            }

            return array_map(fn (Reservation $reservation) => $this->reservationData($reservation, $gestion), $qb->getQuery()->getResult());
        });
    }

    #[Route('/accueil', name: 'accueil', methods: ['GET'])]
    public function accueil(): JsonResponse
    {
        $gestion = $this->isGranted(ParcautoVoter::GESTION);
        $base = $this->em->createQueryBuilder()
            ->from(Reservation::class, 'r')
            ->join('r.vehicule', 'v')
            ->where('r.statut = :statut')
            ->setParameter('maintenant', SaisieParcauto::maintenant());

        $groupes = [
            'mesDemandes' => [Reservation::DEMANDEE, true, 'r.fin > :maintenant'],
            'mesDeparts' => [Reservation::VALIDEE, true, 'r.fin > :maintenant'],
            'mesMissions' => [Reservation::EN_COURS, true, 'r.departLe <= :maintenant'],
        ];
        if ($gestion) {
            $groupes['aValider'] = [Reservation::DEMANDEE, false, 'r.fin > :maintenant'];
            $groupes['retoursEnRetard'] = [Reservation::EN_COURS, false, 'r.fin <= :maintenant'];
        }

        $resultat = [];
        foreach ($groupes as $cle => [$statut, $personnel, $condition]) {
            $qb = (clone $base)->andWhere($condition)->setParameter('statut', $statut);
            if ($personnel) {
                $qb->andWhere('r.conducteur = :conducteur')->setParameter('conducteur', $this->personnel());
            }

            $total = (int) (clone $qb)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();
            $reservations = $qb->select('r', 'v')->orderBy('r.debut', 'ASC')->addOrderBy('r.id', 'ASC')
                ->setMaxResults(5)->getQuery()->getResult();
            $resultat[$cle] = [
                'total' => $total,
                'reservations' => array_map(fn (Reservation $reservation) => $this->reservationData($reservation, $gestion), $reservations),
            ];
        }

        return $this->json($resultat);
    }

    private function reservationData(Reservation $reservation, bool $gestion): array
    {
        $proprietaire = $this->isGranted(ParcautoVoter::MISSION, $reservation);
        $visible = $gestion || $proprietaire;

        return [
            'id' => $visible ? $reservation->id : null,
            'vehicule' => $reservation->vehicule->id,
            'debut' => $reservation->debut->format(DATE_ATOM),
            'fin' => $reservation->fin->format(DATE_ATOM),
            'statut' => $visible ? $reservation->statut : 'occupee',
            'proprietaire' => $proprietaire,
            'enCours' => $reservation->statut === Reservation::EN_COURS,
        ] + ($visible ? [
            'nomConducteur' => $reservation->nomConducteur,
            'ordreMission' => $reservation->ordreMission,
            'ordreVerifie' => $reservation->ordreVerifie,
            'kilometrageDepart' => $reservation->kilometrageDepart,
            'kilometrageRetour' => $reservation->kilometrageRetour,
        ] : []);
    }

    #[Route('/reservations', name: 'demander', methods: ['POST'])]
    public function demander(Request $request): JsonResponse
    {
        return $this->ecrire($request, function (SaisieParcauto $saisie): Reservation {
            $vehicule = $this->vehiculeVerrouille($saisie->entier('vehicule', 1));
            $debut = $saisie->date('debut');
            $fin = $saisie->date('fin');
            if ($debut <= SaisieParcauto::maintenant() || $fin <= $debut || $fin > $debut->modify('+31 days')) {
                throw new \InvalidArgumentException('Saisissez un créneau à venir de 31 jours maximum.');
            }

            $conducteur = $this->personnel();
            $ordreMission = $saisie->texte('ordreMission', 120);
            $existante = $this->em->getRepository(Reservation::class)->findOneBy([
                'vehicule' => $vehicule, 'conducteur' => $conducteur, 'debut' => $debut, 'fin' => $fin,
                'ordreMission' => $ordreMission, 'statut' => Reservation::DEMANDEE,
            ]);
            if ($existante !== null) {
                return $existante;
            }

            $reservation = new Reservation($vehicule, $conducteur, $debut, $fin, $ordreMission);
            $this->planning->verifierDisponibilite($reservation);
            $this->em->persist($reservation);

            return $reservation;
        }, 201);
    }

    #[Route('/reservations/{id<\d+>}/{action<valider|annuler|depart|retour>}', name: 'transition', methods: ['POST'])]
    public function transition(int $id, string $action, Request $request): JsonResponse
    {
        $vehiculeId = $this->vehiculeMissionId($id);

        return $this->ecrire($request, function (SaisieParcauto $saisie) use ($id, $action, $vehiculeId): Reservation {
            $reservation = $this->missionVerrouillee($id, $vehiculeId);
            $attribut = $action === 'valider' ? ParcautoVoter::GESTION : ParcautoVoter::MISSION;
            if ($action !== 'annuler' || !$this->isGranted(ParcautoVoter::GESTION)) {
                $this->denyAccessUnlessGranted($attribut, $reservation);
            }

            $maintenant = SaisieParcauto::maintenant();
            switch ($action) {
                case 'valider':
                    if ($reservation->fin <= $maintenant || $reservation->statut !== Reservation::DEMANDEE) {
                        throw new \DomainException('Cette demande ne peut plus être validée.');
                    }
                    $this->planning->verifierDisponibilite($reservation);
                    $reservation->valider($this->omer->verifier($reservation));
                    break;
                case 'annuler':
                    $reservation->annuler();
                    break;
                case 'depart':
                    $kilometrage = $saisie->entier('kilometrage');
                    $this->planning->verifierDisponibilite($reservation, $kilometrage);
                    $reservation->partir($kilometrage, $maintenant);
                    break;
                case 'retour':
                    $reservation->retourner($saisie->entier('kilometrage'), $maintenant);
                    break;
            }

            return $reservation;
        });
    }

    #[Route('/vehicules', name: 'vehicule_creer', methods: ['POST'])]
    #[Route('/vehicules/{id<\d+>}', name: 'vehicule_modifier', methods: ['PUT'])]
    #[IsGranted(ParcautoVoter::GESTION)]
    public function vehicule(Request $request, ?int $id = null): JsonResponse
    {
        return $this->ecrire($request, fn (SaisieParcauto $saisie) =>
            $this->gestion->vehicule($saisie, $id === null ? null : $this->vehiculeVerrouille($id)), $id === null ? 201 : 200);
    }

    #[Route('/vehicules/{id<\d+>}/suivi', name: 'suivi', methods: ['GET'])]
    #[IsGranted(ParcautoVoter::GESTION)]
    public function suivi(int $id): JsonResponse
    {
        $vehicule = $this->em->find(Vehicule::class, $id) ?? throw $this->createNotFoundException();
        $echeances = $this->em->getRepository(EcheanceEntretien::class)->findBy(['vehicule' => $vehicule], ['echeance' => 'ASC']);
        $journal = $this->em->getRepository(JournalVehicule::class)->findBy(['vehicule' => $vehicule], ['date' => 'DESC', 'id' => 'DESC'], 100);

        return $this->json([
            'echeances' => array_map(fn (EcheanceEntretien $e) => [
                'id' => $e->id, 'type' => $e->type, 'libelle' => $e->libelle,
                'echeance' => $this->jour($e->echeance), 'echeanceKm' => $e->echeanceKm,
                'effectueLe' => $this->jour($e->effectueLe), 'intervalleMois' => $e->intervalleMois,
                'intervalleKm' => $e->intervalleKm,
            ], $echeances),
            'journal' => array_map(fn (JournalVehicule $j) => [
                'id' => $j->id, 'type' => $j->type, 'libelle' => $j->libelle, 'date' => $this->jour($j->date),
                'kilometrage' => $j->kilometrage, 'montantCentimes' => $j->montantCentimes,
                'volumeMillilitres' => $j->volumeMillilitres, 'usureDixiemesMm' => $j->usureDixiemesMm,
                'prestataire' => $j->prestataire?->libelle, 'carte' => $j->carte?->reference,
            ], $journal),
        ]);
    }

    #[Route('/vehicules/{id<\d+>}/echeances', name: 'echeance_creer', methods: ['POST'])]
    #[Route('/vehicules/{id<\d+>}/echeances/{planId<\d+>}', name: 'echeance_renouveler', methods: ['PUT'])]
    #[IsGranted(ParcautoVoter::GESTION)]
    public function echeance(int $id, Request $request, ?int $planId = null): JsonResponse
    {
        return $this->ecrire($request, function (SaisieParcauto $saisie) use ($id, $planId): EcheanceEntretien {
            $vehicule = $this->vehiculeVerrouille($id);
            $plan = $planId === null ? null : $this->em->getRepository(EcheanceEntretien::class)->findOneBy(['id' => $planId, 'vehicule' => $vehicule]);
            if ($planId !== null && $plan === null) {
                throw $this->createNotFoundException();
            }

            return $this->gestion->echeance($vehicule, $this->personnel(), $saisie, $plan);
        }, $planId === null ? 201 : 200);
    }

    #[Route('/vehicules/{id<\d+>}/journal', name: 'journal', methods: ['POST'])]
    #[IsGranted(ParcautoVoter::GESTION)]
    public function journal(int $id, Request $request): JsonResponse
    {
        return $this->ecrire($request, fn (SaisieParcauto $saisie) =>
            $this->gestion->journal($this->vehiculeVerrouille($id), $this->personnel(), $saisie), 201);
    }

    #[Route('/equipements', name: 'equipement_creer', methods: ['POST'])]
    #[Route('/equipements/{id<\d+>}', name: 'equipement_modifier', methods: ['PUT'])]
    #[IsGranted(ParcautoVoter::GESTION)]
    public function equipement(Request $request, ?int $id = null): JsonResponse
    {
        return $this->ecrire($request, function (SaisieParcauto $saisie) use ($id): Equipement {
            $equipement = $id === null ? null : ($this->em->find(Equipement::class, $id, LockMode::PESSIMISTIC_WRITE) ?? throw $this->createNotFoundException());
            $type = $equipement !== null ? $equipement->type : $saisie->choix('type', [Equipement::PRESTATAIRE, Equipement::CARTE, Equipement::BORNE]);
            $vehiculeId = $saisie->entier('vehicule', 1, true);
            if ($type !== Equipement::CARTE && $vehiculeId !== null) {
                throw new \InvalidArgumentException('Seule une carte peut être affectée à un véhicule.');
            }
            $vehicule = $vehiculeId === null ? null : ($this->em->find(Vehicule::class, $vehiculeId) ?? throw $this->createNotFoundException());
            $libelle = $saisie->texte('libelle', 150);
            $coordonnees = $saisie->texte('coordonnees', 255, true);
            $actif = $saisie->booleen('actif');

            if ($equipement === null) {
                $equipement = new Equipement($type, $saisie->texte('reference', 120), $libelle, $coordonnees, $vehicule, $actif);
                $this->em->persist($equipement);
            } else {
                $equipement->libelle = $libelle;
                $equipement->coordonnees = $coordonnees;
                $equipement->vehicule = $vehicule;
                $equipement->actif = $actif;
            }

            return $equipement;
        }, $id === null ? 201 : 200);
    }

    #[Route('/infractions', name: 'infractions', methods: ['POST'])]
    #[IsGranted(ParcautoVoter::GESTION)]
    public function infractions(Request $request): JsonResponse
    {
        return $this->executer(function () use ($request): array {
            $saisie = new SaisieParcauto($request->toArray());
            $date = $saisie->date('date');
            if ($date > SaisieParcauto::maintenant()) {
                throw new \InvalidArgumentException('La date de l’infraction doit être passée.');
            }

            $missions = $this->em->createQueryBuilder()
                ->select('r.id', 'r.nomConducteur', 'r.ordreMission', 'r.departLe', 'r.retourLe')
                ->from(Reservation::class, 'r')
                ->where('r.vehicule = :vehicule AND r.departLe <= :date AND (r.retourLe > :date OR r.retourLe IS NULL)')
                ->setParameter('vehicule', $saisie->entier('vehicule', 1))
                ->setParameter('date', $date)
                ->getQuery()->getArrayResult();

            return array_map(static fn (array $mission) => [
                ...$mission,
                'departLe' => $mission['departLe']->format(DATE_ATOM),
                'retourLe' => $mission['retourLe']?->format(DATE_ATOM),
            ], $missions);
        });
    }

    private function personnel(): Personnel
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Personnel) {
            throw $this->createAccessDeniedException();
        }

        return $utilisateur;
    }

    #[Route('/reservations/{id<\d+>}/journal', name: 'journal_mission', methods: ['POST'])]
    public function journalMission(int $id, Request $request): JsonResponse
    {
        $vehiculeId = $this->vehiculeMissionId($id);

        return $this->ecrire($request, function (SaisieParcauto $saisie) use ($id, $vehiculeId): JournalVehicule {
            $reservation = $this->missionVerrouillee($id, $vehiculeId);
            $this->denyAccessUnlessGranted(ParcautoVoter::MISSION, $reservation);

            return $this->gestion->journal($reservation->vehicule, $this->personnel(), $saisie, $reservation);
        }, 201);
    }

    private function vehiculeMissionId(int $id): int
    {
        $vehiculeId = $this->em->createQueryBuilder()
            ->select('IDENTITY(r.vehicule) AS vehiculeId')
            ->from(Reservation::class, 'r')
            ->where('r.id = :id')->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult();
        if ($vehiculeId === null) {
            throw $this->createNotFoundException();
        }

        return (int) $vehiculeId['vehiculeId'];
    }

    private function missionVerrouillee(int $id, int $vehiculeId): Reservation
    {
        // Conserver le même ordre de verrouillage pour le journal, le départ et le retour.
        $this->vehiculeVerrouille($vehiculeId);

        return $this->em->find(Reservation::class, $id, LockMode::PESSIMISTIC_WRITE) ?? throw $this->createNotFoundException();
    }

    private function vehiculeVerrouille(int $id): Vehicule
    {
        return $this->em->find(Vehicule::class, $id, LockMode::PESSIMISTIC_WRITE) ?? throw $this->createNotFoundException();
    }

    private function jour(?\DateTimeImmutable $date): ?string
    {
        return $date?->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y-m-d');
    }

    private function ecrire(Request $request, callable $operation, int $statut = 200): JsonResponse
    {
        return $this->executer(function () use ($request, $operation): array {
            $saisie = new SaisieParcauto($request->toArray());
            $entite = $this->em->wrapInTransaction(fn () => $operation($saisie));

            return ['id' => $entite->id];
        }, $statut);
    }

    private function executer(callable $operation, int $statut = 200): JsonResponse
    {
        try {
            return $this->json($operation(), $statut);
        } catch (\InvalidArgumentException | JsonException $erreur) {
            return $this->json(['message' => $erreur->getMessage()], 422);
        } catch (\DomainException $erreur) {
            return $this->json(['message' => $erreur->getMessage()], 409);
        } catch (UniqueConstraintViolationException) {
            return $this->json(['message' => 'Une fiche portant cette référence existe déjà.'], 409);
        }
    }
}
