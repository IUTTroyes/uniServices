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
        // 1. Get AnneeUniversitaire and Semestre
        $anneeUniv = $manager->getRepository(StructureAnneeUniversitaire::class)->findOneBy(['libelle' => '2025-2026']);
        if (!$anneeUniv) {
            $anneeUniv = $manager->getRepository(StructureAnneeUniversitaire::class)->findOneBy([]);
        }
        
        $semestre = $manager->getRepository(StructureSemestre::class)->findOneBy(['libelle' => 'S5']);
        if (!$semestre) {
            $semestre = $manager->getRepository(StructureSemestre::class)->findOneBy([]);
        }

        // 2. Get some Personnel (teachers/tutors/responsibles)
        $personnels = $manager->getRepository(Personnel::class)->findBy([], null, 10);
        $resp = $personnels[0] ?? null;

        // 3. Create StagePeriode
        $periode = new StagePeriode();
        $periode->setLibelle('BUT 3 Informatique')
            ->setAnneeUniversitaire($anneeUniv)
            ->setSemestreProgramme($semestre)
            ->setNbSemaines(16)
            ->setNbJours(80)
            ->setDateDebut(new \DateTime('2026-03-02'))
            ->setDateFin(new \DateTime('2026-06-26'))
            ->setResponsablePrincipal($resp)
            ->setCommentaireLibre('Période de stage principale pour les BUT3 Informatique.')
        ;
        
        // Add co-responsables if available
        if (isset($personnels[1])) {
            $periode->getCoResponsables()->add($personnels[1]);
        }
        if (isset($personnels[2])) {
            $periode->getCoResponsables()->add($personnels[2]);
        }

        $manager->persist($periode);

        // 4. Create some StageEtudiant records with different convention statuses
        $students = $manager->getRepository(Etudiant::class)->findBy([], null, 12);
        
        $states = [
            EtatStageEnum::DEPOSE,
            EtatStageEnum::CONVENTION_RECUE,
            EtatStageEnum::CONVENTION_ENVOYEE,
            EtatStageEnum::VALIDE,
            EtatStageEnum::DEPOSE,
            EtatStageEnum::VALIDE,
            EtatStageEnum::CONVENTION_RECUE,
            EtatStageEnum::CONVENTION_RECUE,
            EtatStageEnum::CONVENTION_ENVOYEE
        ];

        $companies = [
            'Capgemini France',
            'Sopra Steria',
            'Avenir Digital',
            'Innovatech Corp',
            'EDF France',
            'StartUp Web',
            'IBM France',
            'Michelin SAS',
            'Renault Group'
        ];

        $sirets = [
            '12345678900012',
            '98765432100023',
            '55220011332244',
            '88877766600055',
            '77766655500099',
            '44433322200088',
            '11122233300044',
            '99988877700066',
            '33344455500077'
        ];

        $subjects = [
            'Développement Full-Stack Angular & NestJS pour le secteur bancaire.',
            'Mise en place de pipelines CI/CD sous GitLab CI et administration Docker.',
            'Migration micro-frontend et mise en place d\'un dashboard analytique sous Vue.js 3.',
            'Intégration d\'API tiers et développement de modules back-end.',
            'Création d\'un portail d\'administration de serveurs de fichiers.',
            'Création de maquettes et développement front-end React.',
            'Ingénierie DevOps, conteneurisation Kubernetes et automatisation Ansible.',
            'Refonte d\'un intranet industriel et migration AngularJS vers Vue 3.',
            'Modélisation de données et conception d\'applications de logistique.'
        ];

        foreach ($students as $index => $etudiant) {
            if ($index >= count($states)) {
                break;
            }

            // Create Contact / Tuteur in company
            $tuteur = new Contact();
            $tuteur->setCivilite('M')
                ->setPrenom('Jean-Marc')
                ->setNom('Lecerf')
                ->setEmail('jm.lecerf@company.com')
                ->setTelephone('06 99 88 77 66')
                ->setFonction('Chef de projet')
            ;
            $manager->persist($tuteur);

            // Create Entreprise
            $entreprise = new Entreprise();
            $entreprise->setRaisonSociale($companies[$index])
                ->setSiret($sirets[$index])
                ->setResponsable($tuteur)
            ;
            
            // Set Address using Adresse ValueObject
            $adresse = Adresse::fromArray([
                'adresse' => '15 Rue de la Paix',
                'complement1' => '',
                'complement2' => '',
                'ville' => 'Paris',
                'codePostal' => '75002',
                'pays' => 'France'
            ]);
            $entreprise->setAdresse($adresse);
            $manager->persist($entreprise);

            // Create StageEtudiant
            $stage = new StageEtudiant();
            $stage->setStagePeriode($periode)
                ->setEtudiant($etudiant)
                ->setEtatStage($states[$index])
                ->setEntreprise($entreprise)
                ->setTuteur($tuteur)
                ->setSujetStage($subjects[$index])
                ->setActivites('Conception de la base de données, développement d\'API REST, écriture des tests unitaires.')
                ->setDateDebutStage(new \DateTime('2026-03-02'))
                ->setDateFinStage(new \DateTime('2026-06-26'))
                ->setGratification(true)
                ->setGratificationMontant(4.80)
                ->setDureeHebdomadaire(35.0)
                ->setDureeJoursStage(80)
                ->setTuteurUniversitaire($personnels[$index % count($personnels)] ?? null)
                ->setAssuranceCompagnie('MAIF')
                ->setAssuranceNumero('9876543-A')
                ->setDateDepotFormulaire(new \DateTime())
            ;

            // Pre-populate mock follow-ups, reports and grades for testing
            $followups = [];
            if ($index % 2 === 0) {
                $followups[] = [
                    'id' => 1,
                    'date' => '15/03/2026',
                    'type' => 'Appel Téléphonique',
                    'summary' => 'Premier contact, l\'étudiant s\'intègre bien. Missions validées.'
                ];
                if ($index % 3 === 2) {
                    $followups[] = [
                        'id' => 2,
                        'date' => '20/04/2026',
                        'type' => 'Visite Entreprise',
                        'summary' => 'Rencontre avec le maître de stage. Le projet avance. L\'étudiant est autonome.'
                    ];
                }
            } else {
                $followups[] = [
                    'id' => 1,
                    'date' => '18/03/2026',
                    'type' => 'Visioconférence',
                    'summary' => 'Point sur l\'installation. Quelques soucis d\'accès au VPN résolus.'
                ];
            }
            $stage->setSuiviRencontres($followups);

            if ($index % 3 === 0) {
                $stage->setReportUploaded(true);
                $stage->setReportName('Rapport_Final_BUT3_' . $etudiant->getNom() . '.pdf');
            } elseif ($index % 3 === 2) {
                $stage->setReportUploaded(true);
                $stage->setReportName('Rapport_Final_BUT3_' . $etudiant->getNom() . '.pdf');
                $stage->setEvaluationNote(15.5);
                $stage->setEvaluationCommentaire('Très bon portfolio et travail sérieux durant ce stage.');
            } else {
                $stage->setReportUploaded(false);
            }
            
            $manager->persist($stage);
        }

        $manager->flush();
    }
}
