<?php

namespace StageBundle\DataFixtures;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use StageBundle\Entity\Stages\Contact;
use StageBundle\Entity\Stages\Entreprise;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Enum\EtatStageEnum;
use App\ValueObject\Adresse;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class StageFixtures extends Fixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['stage'];
    }
    
    public function getOrder(): int
    {
        return 10;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Get or create StructureAnneeUniversitaire records
        $anneeUnivRepo = $manager->getRepository(StructureAnneeUniversitaire::class);
        
        $anneeUniv2324 = $anneeUnivRepo->findOneBy(['libelle' => '2023-2024']);
        if (!$anneeUniv2324) {
            $anneeUniv2324 = new StructureAnneeUniversitaire();
            $anneeUniv2324->setLibelle('2023-2024')->setAnnee(2023);
            $manager->persist($anneeUniv2324);
        }

        $anneeUniv2425 = $anneeUnivRepo->findOneBy(['libelle' => '2024-2025']);
        if (!$anneeUniv2425) {
            $anneeUniv2425 = new StructureAnneeUniversitaire();
            $anneeUniv2425->setLibelle('2024-2025')->setAnnee(2024);
            $manager->persist($anneeUniv2425);
        }

        $anneeUniv2526 = $anneeUnivRepo->findOneBy(['libelle' => '2025-2026']);
        if (!$anneeUniv2526) {
            $anneeUniv2526 = new StructureAnneeUniversitaire();
            $anneeUniv2526->setLibelle('2025-2026')->setAnnee(2025);
            $manager->persist($anneeUniv2526);
        }

        // 2. Get or create StructureSemestre records (S1..S6)
        $semestreRepo = $manager->getRepository(StructureSemestre::class);
        $s1 = $semestreRepo->findOneBy(['libelle' => 'S1']);
        $s2 = $semestreRepo->findOneBy(['libelle' => 'S2']);
        $s3 = $semestreRepo->findOneBy(['libelle' => 'S3']);
        $s4 = $semestreRepo->findOneBy(['libelle' => 'S4']);
        $s5 = $semestreRepo->findOneBy(['libelle' => 'S5']);
        $s6 = $semestreRepo->findOneBy(['libelle' => 'S6']);

        // 3. Get Personnel (responsibles)
        $personnels = $manager->getRepository(Personnel::class)->findBy([], null, 10);
        $resp1 = $personnels[0] ?? null;
        $resp2 = $personnels[1] ?? $resp1;

        // 4. Get Etudiant records & assign test student to S5 (BUT 3)
        $students = $manager->getRepository(Etudiant::class)->findBy([], null, 10);
        $mainStudent = $students[0] ?? null;
        if ($mainStudent && $s5) {
            $mainStudent->setSemestreActuel($s5);
            $manager->persist($mainStudent);
        }

        // --- BUT 1 : PAS DE PÉRIODE DE STAGE (0 période) ---
        // (On n'instancie aucune StagePeriode rattachée à 2023-2024 ou S1/S2)

        // --- BUT 2 : 1 PÉRIODE TERMINÉE AVEC UN STAGE VALIDÉ ---
        $periodeBUT2 = new StagePeriode();
        $periodeBUT2->setLibelle('BUT 2 - Stage technique & applicatif (8 semaines)')
            ->setAnneeUniversitaire($anneeUniv2425)
            ->setSemestreProgramme($s4)
            ->setNbSemaines(8)
            ->setNbJours(40)
            ->setDateDebut(new \DateTime('2025-05-02'))
            ->setDateFin(new \DateTime('2025-06-27'))
            ->setResponsablePrincipal($resp2)
            ->setDescription('Ce stage technique vise à valider des compétences de développement logiciel, d\'intégration ou d\'administration systèmes.')
            ->setCommentaireLibre('Ce stage technique vise à valider des compétences de développement logiciel, d\'intégration ou d\'administration systèmes dans un contexte professionnel. Il donne lieu à un rapport écrit technique approfondi.')
            ->setCompetencesVisees('Développement d\'applications Web/Mobile, modélisation de base de données, rédaction de tests unitaires et d\'APIs.')
            ->setModalitesEvaluationPedagogique('Mémoire écrit approfondi (15-25 pages) et soutenance orale de 20 min devant jury.')
            ->setModalitesEvaluationEntreprise('Évaluation détaillée du niveau technique, de l\'autonomie et des livrables produits.')
            ->setModalitesEncadrement('Visite sur site ou visioconférence de suivi technique avec le tuteur académique.')
            ->setDocumentsRendre('Rapport technique (PDF), attestation de fin de stage et fiche d\'évaluation.')
            ->setConsignesFichiers([
                ['name' => 'Fiche de liaison - BUT 2 (Document Type)', 'format' => 'PDF', 'size' => '150 Ko', 'date' => '15/03/2025'],
                ['name' => 'Consignes administratives BUT 2', 'format' => 'PDF', 'size' => '480 Ko', 'date' => '01/03/2025']
            ])
        ;
        if (isset($personnels[2])) {
            $periodeBUT2->getCoResponsables()->add($personnels[2]);
        }
        $manager->persist($periodeBUT2);

        // Stage terminé pour l'étudiant principal en BUT2
        if ($mainStudent) {
            $tuteur2 = new Contact();
            $tuteur2->setCivilite('M')
                ->setPrenom('Marc')
                ->setNom('Vasseur')
                ->setEmail('m.vasseur@techsolutions.fr')
                ->setTelephone('03 25 88 99 00')
                ->setFonction('Chef de projet Backend')
            ;
            $manager->persist($tuteur2);

            $entreprise2 = new Entreprise();
            $entreprise2->setRaisonSociale('TechSolutions SAS')
                ->setSiret('98765432100023')
                ->setResponsable($tuteur2)
            ;
            $entreprise2->setAdresse(Adresse::fromArray([
                'adresse' => '45 Avenue de la Gare',
                'complement1' => '',
                'complement2' => '',
                'ville' => 'Nogent-sur-Seine',
                'codePostal' => '10400',
                'pays' => 'France'
            ]));
            $manager->persist($entreprise2);

            $stageBUT2 = new StageEtudiant();
            $stageBUT2->setStagePeriode($periodeBUT2)
                ->setEtudiant($mainStudent)
                ->setEtatStage(EtatStageEnum::VALIDE)
                ->setEntreprise($entreprise2)
                ->setTuteur($tuteur2)
                ->setSujetStage('Développement d\'une API REST sous Symfony et refonte du panel administrateur.')
                ->setActivites('Création d\'endpoints API Platform, migration d\'interfaces et rédaction des tests unitaires.')
                ->setDateDebutStage(new \DateTime('2025-05-02'))
                ->setDateFinStage(new \DateTime('2025-06-27'))
                ->setGratification(true)
                ->setGratificationMontant(4.35)
                ->setDureeHebdomadaire(35.0)
                ->setDureeJoursStage(40)
                ->setTuteurUniversitaire($resp2)
                ->setReportUploaded(true)
                ->setReportName('Rapport_BUT2_Symfony_Martin.pdf')
                ->setEvaluationNote(17.0)
                ->setEvaluationCommentaire('Excellent travail d\'intégration et très bon rapport technique.')
            ;
            $manager->persist($stageBUT2);
        }

        // --- BUT 3 : 1 PÉRIODE EN ATTENTE (SAISIE AUTORISÉE, DEMANDE NON ENCORE COMPLÉTÉE) ---
        $periodeBUT3 = new StagePeriode();
        $periodeBUT3->setLibelle('BUT 3 - Stage de fin d\'études principal (16 semaines)')
            ->setAnneeUniversitaire($anneeUniv2526)
            ->setSemestreProgramme($s6)
            ->setNbSemaines(16)
            ->setNbJours(80)
            ->setDateDebut(new \DateTime('2026-03-02'))
            ->setDateFin(new \DateTime('2026-06-26'))
            ->setResponsablePrincipal($resp1)
            ->setDescription('Stage de fin d\'études (BUT3) visant à mettre en œuvre l\'ensemble de vos compétences en situation professionnelle complexe et d\'assurer votre transition vers le marché du travail.')
            ->setCommentaireLibre('Stage de fin d\'études (BUT3). Ce stage doit vous permettre de mettre en œuvre l\'ensemble de vos compétences en situation professionnelle complexe et d\'assurer votre transition vers le marché de l\'emploi ou les études supérieures.')
            ->setCompetencesVisees('Architecture logicielle avancée, gestion globale de projets IT, démarche qualité et méthodologies Agiles.')
            ->setModalitesEvaluationPedagogique('Mémoire de fin d\'études professionnel (30-40 pages) et soutenance orale finale devant jury d\'experts.')
            ->setModalitesEvaluationEntreprise('Bilan complet des réalisations professionnelles et de l\'intégration dans les équipes.')
            ->setModalitesEncadrement('Suivi régulier mensuel et visite physique du tuteur IUT dans l\'entreprise.')
            ->setDocumentsRendre('Mémoire de fin d\'études (PDF), poster de synthèse et fiche d\'évaluation finale.')
            ->setConsignesFichiers([
                ['name' => 'Fiche de liaison & Pré-formulaire - BUT 3 (Document Type)', 'format' => 'PDF', 'size' => '185 Ko', 'date' => '05/01/2026'],
                ['name' => 'Consignes de stage & Calendrier BUT 3', 'format' => 'PDF', 'size' => '1.2 Mo', 'date' => '05/01/2026'],
                ['name' => 'Guide de rédaction du mémoire de stage', 'format' => 'PDF', 'size' => '920 Ko', 'date' => '10/01/2026']
            ])
        ;
        // Saisie autorisée pour S5 et S6 !
        if ($s5) {
            $periodeBUT3->addSemestresSaisie($s5);
        }
        if ($s6) {
            $periodeBUT3->addSemestresSaisie($s6);
        }
        if (isset($personnels[1])) {
            $periodeBUT3->getCoResponsables()->add($personnels[1]);
        }
        $manager->persist($periodeBUT3);

        // L'étudiant principal n'a PAS ENCORE créé sa demande de convention pour BUT3 (ou demande en attente),
        // ce qui produira l'état "Demande non complétée - Saisie autorisée".

        $manager->flush();
    }
}
