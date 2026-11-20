<?php

declare(strict_types=1);

namespace ParcautoBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use ParcautoBundle\Entity\EcheanceEntretien;
use ParcautoBundle\Entity\Reservation;
use ParcautoBundle\Entity\Vehicule;

final readonly class PlanningParcauto
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return array<int, list<EcheanceEntretien>> */
    public function echeancesParVehicule(): array
    {
        $echeances = [];
        foreach ($this->entityManager->getRepository(EcheanceEntretien::class)->findAll() as $plan) {
            $echeances[$plan->vehicule->id][] = $plan;
        }

        return $echeances;
    }

    /** @param list<EcheanceEntretien>|null $echeances */
    public function blocage(Vehicule $vehicule, \DateTimeImmutable $fin, int $kilometrage, ?array $echeances = null): ?string
    {
        if (!$vehicule->actif) {
            return 'Véhicule retiré du planning.';
        }

        if ($vehicule->reformePrevueLe !== null && $fin >= $vehicule->reformePrevueLe) {
            return 'La date de réforme est atteinte.';
        }

        $pneus = false;
        $controleTechnique = false;
        $echeances ??= $this->entityManager->getRepository(EcheanceEntretien::class)->findBy(['vehicule' => $vehicule]);
        foreach ($echeances as $entretien) {
            if ($entretien->estDepassee($fin, $kilometrage)) {
                return 'Échéance atteinte : '.$entretien->libelle;
            }
            $pneus = $pneus || $entretien->type === 'pneus';
            $controleTechnique = $controleTechnique || $entretien->type === 'controle_technique';
        }

        if (!$pneus || !$controleTechnique) {
            return 'Renseignez le contrôle technique et le contrôle trimestriel des pneus.';
        }

        return null;
    }

    public function verifierDisponibilite(Reservation $reservation, ?int $kilometrage = null): void
    {
        $blocage = $this->blocage($reservation->vehicule, $reservation->fin, $kilometrage ?? $reservation->vehicule->kilometrage);
        if ($blocage !== null) {
            throw new \DomainException($blocage);
        }

        $conflits = $this->entityManager->createQueryBuilder()
            ->select('r.id')
            ->from(Reservation::class, 'r')
            ->where('r.vehicule = :vehicule')
            ->andWhere('(r.statut = :enCours AND (r.fin > :debut OR :debut <= :maintenant)) OR (r.statut = :validee AND r.debut < :fin AND r.fin > :debut)')
            ->setParameter('vehicule', $reservation->vehicule)
            ->setParameter('enCours', Reservation::EN_COURS)
            ->setParameter('maintenant', SaisieParcauto::maintenant())
            ->setParameter('validee', Reservation::VALIDEE)
            ->setParameter('debut', $reservation->debut)
            ->setParameter('fin', $reservation->fin);

        if ($reservation->id !== null) {
            $conflits->andWhere('r.id <> :id')->setParameter('id', $reservation->id);
        }

        if ($conflits->setMaxResults(1)->getQuery()->getOneOrNullResult() !== null) {
            throw new \DomainException('Le véhicule est déjà réservé sur ce créneau ou n’a pas encore été restitué.');
        }
    }
}
