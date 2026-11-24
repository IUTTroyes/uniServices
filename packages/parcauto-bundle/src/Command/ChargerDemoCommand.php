<?php

declare(strict_types=1);

namespace ParcautoBundle\Command;

use App\Entity\Users\Personnel;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use ParcautoBundle\Entity\EcheanceEntretien;
use ParcautoBundle\Entity\Equipement;
use ParcautoBundle\Entity\JournalVehicule;
use ParcautoBundle\Entity\Reservation;
use ParcautoBundle\Entity\Vehicule;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:parcauto:charger-demo', description: 'Ajoute le jeu de démonstration ParcAuto.')]
final class ChargerDemoCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = json_decode(file_get_contents(__DIR__.'/../../resources/demo/parc-auto.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->em->wrapInTransaction(function () use ($data): void {
            $personnels = [];
            foreach ($data['contexte']['referencesPersonnel'] as $username => $nomComplet) {
                $existant = $this->em->getRepository(Personnel::class)->findOneBy(['username' => $username]);
                if ($existant !== null) {
                    if ($existant->getMailUniv() !== $username.'@example.invalid') {
                        throw new \RuntimeException("Le personnel $username existe déjà. Import interrompu.");
                    }
                    $personnels[$username] = $existant;
                    continue;
                }
                [$prenom, $nom] = explode(' ', $nomComplet, 2);
                $personnels[$username] = (new Personnel())->setUsername($username)->setPrenom($prenom)
                    ->setNom($nom)->setMailUniv($username.'@example.invalid');
                $this->em->persist($personnels[$username]);
            }

            $vehicules = [];
            foreach ($data['vehicules'] as $v) {
                $sorties = array_values(array_filter($data['reservations'], static fn (array $s): bool => $s['vehicule'] === $v['id']));
                usort($sorties, static fn ($a, $b) => strcmp($a['debut'], $b['debut']));
                $vehicule = $this->em->getRepository(Vehicule::class)->findOneBy(['immatriculation' => $v['immatriculation']]);
                if ($vehicule === null) {
                    $vehicule = new Vehicule($v['immatriculation'], $v['libelle'], $v['site'], $v['energie'], $sorties[0]['kilometrageDepart']);
                    $this->em->persist($vehicule);
                    foreach ($sorties as $s) {
                        $sortie = new Reservation($vehicule, $personnels[$s['conducteur']], self::date($s['debut']), self::date($s['fin']), $s['ordreMission']);
                        $sortie->valider($s['ordreVerifie']);
                        if ($s['statut'] === Reservation::TERMINEE) {
                            $sortie->partir($s['kilometrageDepart'], self::date($s['departLe']));
                            $sortie->retourner($s['kilometrageRetour'], self::date($s['retourLe']));
                        }
                        $this->em->persist($sortie);
                    }
                    if ($vehicule->kilometrage !== $v['kilometrage']) {
                        throw new \RuntimeException('Historique incohérent pour '.$v['immatriculation']);
                    }
                } else {
                    foreach ($sorties as $s) {
                        if (!$this->em->getRepository(Reservation::class)->findOneBy(['vehicule' => $vehicule, 'ordreMission' => $s['ordreMission']])) {
                            throw new \RuntimeException('Import existant incomplet ou immatriculation déjà utilisée : '.$v['immatriculation']);
                        }
                    }
                }
                $vehicules[$v['id']] = $vehicule;
                $vehicule->misEnServiceLe ??= self::date($v['misEnServiceLe']);
                $vehicule->reformePrevueLe ??= self::date($v['reformePrevueLe']);
                $vehicule->reformeKm ??= $v['reformeKm'];
                $vehicule->consommationReference ??= $v['consommationReference'];
                if ($vehicule->achatCentimes === 0) {
                    $vehicule->achatCentimes = $v['achatCentimes'];
                }
            }
            $this->em->flush();
            $equipements = [];
            foreach ($data['equipements'] as $e) {
                $cle = ['type' => $e['type'], 'reference' => $e['reference']];
                $equipement = $this->em->getRepository(Equipement::class)->findOneBy($cle)
                    ?? new Equipement($e['type'], $e['reference'], $e['libelle'], $e['coordonnees'], $vehicules[$e['vehicule']] ?? null, $e['actif']);
                $this->em->persist($equipement);
                $equipements[$e['reference']] = $equipement;
            }
            $this->em->flush();
            foreach ($data['echeancesEntretien'] as $e) {
                $vehicule = $vehicules[$e['vehicule']];
                $cle = ['vehicule' => $vehicule, 'type' => $e['type'], 'libelle' => $e['libelle']];
                if ($this->em->getRepository(EcheanceEntretien::class)->findOneBy($cle)) {
                    continue;
                }
                $echeance = self::date($e['echeance']);
                $plan = new EcheanceEntretien($vehicule, $e['type'], $e['libelle'], $echeance, $e['echeanceKm']);
                $plan->intervalleMois = $e['intervalleMois'];
                $plan->intervalleKm = $e['intervalleKm'];
                $plan->renouveler(self::date($e['effectueLe']), $echeance, $e['echeanceKm'], $e['kilometrageControle']);
                $this->em->persist($plan);
            }
            foreach ($data['journal'] as $j) {
                $vehicule = $vehicules[$j['vehicule']];
                $date = self::date($j['date']);
                $cle = ['vehicule' => $vehicule, 'type' => $j['type'], 'date' => $date, 'libelle' => $j['libelle']];
                $ligne = $this->em->getRepository(JournalVehicule::class)->findOneBy($cle);
                $prestataire = $equipements[$j['prestataire']];
                $carte = isset($j['carte']) ? $equipements[$j['carte']] : null;
                if ($ligne !== null) {
                    $ligne->completerEquipements($prestataire, $carte);
                    continue;
                }
                $this->em->persist(new JournalVehicule($vehicule, $personnels[$j['auteur']], $j['type'],
                    $date, $j['libelle'], $j['kilometrage'], $j['montantCentimes'] ?? null, $j['volumeMillilitres'] ?? null,
                    $prestataire, $carte, $j['usureDixiemesMm'] ?? null));
            }
        });
        $output->writeln('Jeu ParcAuto à jour : 3 véhicules, 15 réservations, 7 équipements, 18 entrées de journal et 9 échéances.');
        return Command::SUCCESS;
    }

    private static function date(string $valeur): DateTimeImmutable
    {
        return (new DateTimeImmutable($valeur, new DateTimeZone('Europe/Paris')))->setTimezone(new DateTimeZone('UTC'));
    }
}
