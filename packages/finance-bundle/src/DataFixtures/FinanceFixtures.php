<?php

namespace FinanceBundle\DataFixtures;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureService;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceFournisseur;
use FinanceBundle\Entity\FinanceReception;
use FinanceBundle\Enum\AvisDirecteurEnum;
use FinanceBundle\Enum\StatutCommandeEnum;
use FinanceBundle\Enum\TypePrestationEnum;
use FinanceBundle\Enum\TypeReceptionEnum;

class FinanceFixtures extends Fixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['finance', 'default'];
    }

    public function getOrder(): int
    {
        return 15;
    }

    public function load(ObjectManager $manager): void
    {
        $personnelRepo = $manager->getRepository(Personnel::class);
        $deptRepo = $manager->getRepository(StructureDepartement::class);
        $serviceRepo = $manager->getRepository(StructureService::class);

        $financeUser = $personnelRepo->findOneBy(['username' => 'finance']);
        $assistante = $personnelRepo->findOneBy(['username' => 'assistante']);
        $personnel = $personnelRepo->findOneBy(['username' => 'personnel']);
        $superadmin = $personnelRepo->findOneBy(['username' => 'superadmin']);

        $deptMmi = $deptRepo->findOneBy(['libelle' => 'MMI']);
        $serviceFinancier = $serviceRepo->findOneBy(['libelle' => 'Service Financier']);

        // Fournisseurs
        $f1 = new FinanceFournisseur();
        $f1->setNumeroFournisseur('1650')->setNomFournisseur('ELITE SECURITE');
        $manager->persist($f1);

        $f2 = new FinanceFournisseur();
        $f2->setNumeroFournisseur('1471')->setNomFournisseur('TOUSSAINT');
        $manager->persist($f2);

        $f3 = new FinanceFournisseur();
        $f3->setNumeroFournisseur('2042')->setNomFournisseur('DELL TECHNOLOGIES');
        $manager->persist($f3);

        // Commande 1 : Gardiennage JPO (Payé)
        $bc1 = new FinanceBonCommande();
        $bc1->setDemandeur($assistante ?? $personnel)
            ->setResponsable($personnel)
            ->setDepartement($deptMmi)
            ->setPrestation(TypePrestationEnum::SERVICES)
            ->setObjetDemande('GARDIENNAGE JPO + JANVIER 26')
            ->setMontantHt('889.20')
            ->setMontantTtc('1067.04')
            ->setCentreFinancier('ACFA9B5FGL')
            ->setGestionnaire($financeUser ?? $superadmin)
            ->setFournisseur($f1)
            ->setNumeroBonCommande('4500264424')
            ->setDateBonCommande(new \DateTime('2026-01-12'))
            ->setDateDemande(new \DateTime('2026-01-05'))
            ->setPjSurSifac(true)
            ->setBcTransmis(true)
            ->setAvisDirecteur(AvisDirecteurEnum::FAVORABLE)
            ->setDateAvisDirecteur(new \DateTime('2026-01-08'))
            ->setSignataireDirecteur($superadmin)
            ->setDateVerificationFinanciere(new \DateTime('2026-01-10'))
            ->setVerificateurFinancier($financeUser)
            ->setDateVisaEngagement(new \DateTime('2026-01-11'))
            ->setSignataireVisaEngagement($financeUser)
            ->setDatePaiement(new \DateTime('2026-04-03'))
            ->setFactureFinale(true)
            ->setStatut(StatutCommandeEnum::PAYE);
        $manager->persist($bc1);

        // Commande 2 : Produits d'hygiène (Réceptionné & Payé)
        $bc2 = new FinanceBonCommande();
        $bc2->setDemandeur($assistante ?? $personnel)
            ->setResponsable($personnel)
            ->setDepartement($deptMmi)
            ->setPrestation(TypePrestationEnum::FOURNITURES)
            ->setObjetDemande('PDTS HYGIENE')
            ->setMontantHt('2563.00')
            ->setMontantTtc('3075.60')
            ->setCentreFinancier('ACFA9B5FGL')
            ->setGestionnaire($financeUser ?? $superadmin)
            ->setFournisseur($f2)
            ->setNumeroBonCommande('4500264427')
            ->setDateBonCommande(new \DateTime('2026-01-12'))
            ->setDateDemande(new \DateTime('2026-01-06'))
            ->setDateLivraisonRenseignee(true)
            ->setPjSurSifac(true)
            ->setBcTransmis(true)
            ->setAvisDirecteur(AvisDirecteurEnum::FAVORABLE)
            ->setDateAvisDirecteur(new \DateTime('2026-01-09'))
            ->setSignataireDirecteur($superadmin)
            ->setDateVerificationFinanciere(new \DateTime('2026-01-10'))
            ->setVerificateurFinancier($financeUser)
            ->setDateVisaEngagement(new \DateTime('2026-01-11'))
            ->setSignataireVisaEngagement($financeUser)
            ->setDateArriveeFacture(new \DateTime('2026-11-21'))
            ->setDatePaiement(new \DateTime('2026-01-27'))
            ->setFactureFinale(true)
            ->setStatut(StatutCommandeEnum::PAYE);

        $rec2 = new FinanceReception();
        $rec2->setBonCommande($bc2)
            ->setDateEffectiveReception(new \DateTime('2026-01-26'))
            ->setTypeReception(TypeReceptionEnum::TOTALE)
            ->setMontantTtcConstate('3075.60')
            ->setPrestationsConformes(true)
            ->setDateMigo103(new \DateTime('2026-01-26'))
            ->setDateMigo105(new \DateTime('2026-01-26'))
            ->setBlRattacheSifac(true)
            ->setReceptionnaire($assistante ?? $personnel)
            ->setQualiteReceptionnaire('Assistante de Département')
            ->setDateSignatureServiceFait(new \DateTime('2026-01-26'));
        $bc2->addReception($rec2);
        $manager->persist($rec2);
        $manager->persist($bc2);

        // Commande 3 : Nouvelle demande en cours (Brouillon / Soumis par l'assistante)
        $bc3 = new FinanceBonCommande();
        $bc3->setDemandeur($assistante ?? $personnel)
            ->setResponsable($personnel)
            ->setDepartement($deptMmi)
            ->setPrestation(TypePrestationEnum::FOURNITURES)
            ->setObjetDemande('Renouvellement postes informatiques Salle 104')
            ->setMontantHt('4500.00')
            ->setMontantTtc('5400.00')
            ->setNoteExplicative('Remplacement des 5 PC obsolètes pour les TP de développement web et infographie.')
            ->setCentreFinancier('ACFA9B5FGL')
            ->setFournisseur($f3)
            ->setDateDemande(new \DateTime('2026-09-28'))
            ->setAvisDirecteur(AvisDirecteurEnum::FAVORABLE)
            ->setStatut(StatutCommandeEnum::SOUMIS);
        $manager->persist($bc3);

        $manager->flush();
    }
}
