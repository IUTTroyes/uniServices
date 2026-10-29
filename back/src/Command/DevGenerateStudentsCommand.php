<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Etudiant;
use App\Enum\TypeGroupeEnum;
use App\Repository\EtudiantRepository;
use App\Repository\EtudiantScolariteSemestreRepository;
use App\Repository\Scolarite\ScolBacRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureGroupeRepository;
use App\Repository\Structure\StructureSemestreRepository;
use App\ValueObject\Adresse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:dev:generate-students',
    description: 'Génère des étudiants fictifs dans un semestre donné et les place dans les groupes (1 CM, 1 TD, 1 TP).',
    aliases: ['app:dev:generate-etudiants', 'app:dev:etudiants']
)]
final class DevGenerateStudentsCommand extends Command
{
    private const FIRST_NAMES = [
        'Alexandre', 'Alexis', 'Antoine', 'Arthur', 'Baptiste', 'Clément', 'Corentin', 'Dorian',
        'Enzo', 'Florian', 'Gabriel', 'Guillaume', 'Hugo', 'Jules', 'Julien', 'Léo', 'Louis',
        'Lucas', 'Maxime', 'Nathan', 'Nicolas', 'Paul', 'Quentin', 'Romain', 'Théo', 'Thomas',
        'Valentin', 'Victor', 'Yanis', 'Adèle', 'Alice', 'Amandine', 'Anaïs', 'Camille', 'Clara',
        'Chloé', 'Emma', 'Eva', 'Inès', 'Jade', 'Juliette', 'Justine', 'Laura', 'Léa', 'Lucie',
        'Manon', 'Marine', 'Mathilde', 'Morgane', 'Noémie', 'Océane', 'Pauline', 'Romane', 'Sarah', 'Zoé'
    ];

    private const LAST_NAMES = [
        'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Dubois', 'Moreau',
        'Laurent', 'Simon', 'Michel', 'Lefebvre', 'Leroy', 'Roux', 'David', 'Bertrand', 'Morel',
        'Fournier', 'Girard', 'Bonnet', 'Dupont', 'Lambert', 'Fontaine', 'Rousseau', 'Vincent',
        'Muller', 'Lefevre', 'Faure', 'André', 'Mercier', 'Blanc', 'Guérin', 'Boyer', 'Garnier',
        'Chevalier', 'François', 'Legrand', 'Gauthier', 'Garcia', 'Perrin', 'Robin', 'Clément',
        'Morin', 'Nicolas', 'Henry', 'Roussel', 'Mathieu', 'Gautier', 'Masson', 'Marchand', 'Dupond', 'Caron'
    ];

    private const STREETS = [
        '12 rue de la Paix',
        '45 avenue Victor Hugo',
        '8 rue Gambetta',
        '17 boulevard Danton',
        '3 rue des Lilas',
        '24 rue de la République',
        '15 avenue Anatole France',
        '7 impasse des Acacias',
        '88 rue Nationale',
        '14 allée des Cerisiers',
        '56 rue Voltaire',
        '2 bis boulevard Pasteur',
        '19 rue Émile Zola',
        '31 rue Saint-Denis',
        '4 place de l\'Hôtel de Ville',
        '6 rue Carnot',
        '22 rue de la Gare',
        '9 rue des Écoles',
        '35 avenue Jean Jaurès',
        '11 rue pasteur'
    ];

    private const CITIES = [
        ['ville' => 'Troyes', 'codePostal' => '10000'],
        ['ville' => 'Sainte-Savine', 'codePostal' => '10300'],
        ['ville' => 'Saint-André-les-Vergers', 'codePostal' => '10120'],
        ['ville' => 'La Chapelle-Saint-Luc', 'codePostal' => '10600'],
        ['ville' => 'Romilly-sur-Seine', 'codePostal' => '10100'],
        ['ville' => 'Reims', 'codePostal' => '51100'],
        ['ville' => 'Châlons-en-Champagne', 'codePostal' => '51000'],
        ['ville' => 'Nogent-sur-Seine', 'codePostal' => '10400'],
        ['ville' => 'Bar-sur-Aube', 'codePostal' => '10200'],
        ['ville' => 'Paris', 'codePostal' => '75013'],
        ['ville' => 'Dijon', 'codePostal' => '21000'],
        ['ville' => 'Nancy', 'codePostal' => '54000'],
    ];

    private const EMAIL_DOMAINS = [
        'gmail.com',
        'yahoo.fr',
        'hotmail.fr',
        'outlook.com',
        'laposte.net',
        'orange.fr'
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StructureSemestreRepository $structureSemestreRepository,
        private readonly StructureGroupeRepository $structureGroupeRepository,
        private readonly StructureAnneeUniversitaireRepository $structureAnneeUniversitaireRepository,
        private readonly EtudiantRepository $etudiantRepository,
        private readonly EtudiantScolariteSemestreRepository $etudiantScolariteSemestreRepository,
        private readonly ScolBacRepository $scolBacRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('semestre', InputArgument::REQUIRED, 'ID du semestre')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Nombre d\'étudiants à générer', 30)
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Mot de passe initial pour tous les comptes créés', 'test')
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Nettoyer les étudiants / inscriptions existants pour ce semestre avant génération')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $semestreId = (int) $input->getArgument('semestre');
        $count = (int) $input->getOption('count');
        $plainPassword = (string) $input->getOption('password');
        $clean = (bool) $input->getOption('clean');

        if ($count <= 0) {
            $io->error('Le nombre d\'étudiants à générer doit être supérieur à 0.');
            return Command::FAILURE;
        }

        $semestre = $this->structureSemestreRepository->find($semestreId);
        if (!$semestre instanceof StructureSemestre) {
            $io->error(sprintf('Le semestre avec l\'ID %d n\'existe pas.', $semestreId));
            return Command::FAILURE;
        }

        $io->title(sprintf('Génération d\'étudiants pour le semestre : %s (ID: %d)', $semestre->getLibelle(), $semestre->getId()));

        // Résolution de l'année universitaire et du département
        $anneeUniv = $semestre->getAnnee()?->getPn()?->getAnneeUniversitaire();
        if (!$anneeUniv instanceof StructureAnneeUniversitaire) {
            $anneeUniv = $this->structureAnneeUniversitaireRepository->findOneBy(['actif' => true])
                ?? $this->structureAnneeUniversitaireRepository->findOneBy([], ['annee' => 'DESC']);
        }

        if (!$anneeUniv instanceof StructureAnneeUniversitaire) {
            $io->error('Impossible de déterminer une année universitaire active pour ce semestre.');
            return Command::FAILURE;
        }

        $departement = $semestre->getAnnee()?->getDepartement()
            ?? $semestre->getDiplome()?->getDepartement();

        // Récupération des groupes du semestre
        $allGroupes = $this->structureGroupeRepository->findAllBySemestreId($semestre->getId());
        if (empty($allGroupes)) {
            $allGroupes = $semestre->getGroupes()->toArray();
        }

        if (empty($allGroupes)) {
            $io->error(sprintf(
                'Le semestre "%s" (ID: %d) ne possède aucun groupe. Veuillez d\'abord associer des groupes (CM, TD, TP) à ce semestre.',
                $semestre->getLibelle(),
                $semestre->getId()
            ));
            return Command::FAILURE;
        }

        /** @var StructureGroupe[] $cmGroups */
        $cmGroups = [];
        /** @var StructureGroupe[] $tdGroups */
        $tdGroups = [];
        /** @var StructureGroupe[] $tpGroups */
        $tpGroups = [];
        /** @var StructureGroupe[] $otherGroups */
        $otherGroups = [];

        foreach ($allGroupes as $groupe) {
            match ($groupe->getType()) {
                TypeGroupeEnum::TYPE_GROUPE_CM => $cmGroups[] = $groupe,
                TypeGroupeEnum::TYPE_GROUPE_TD => $tdGroups[] = $groupe,
                TypeGroupeEnum::TYPE_GROUPE_TP => $tpGroups[] = $groupe,
                default => $otherGroups[] = $groupe,
            };
        }

        $sortFn = static fn(StructureGroupe $a, StructureGroupe $b) => ($a->getOrdre() ?? $a->getId()) <=> ($b->getOrdre() ?? $b->getId());
        usort($cmGroups, $sortFn);
        usort($tdGroups, $sortFn);
        usort($tpGroups, $sortFn);

        if (empty($cmGroups) && empty($tdGroups) && empty($tpGroups)) {
            $io->error(sprintf(
                'Aucun groupe de type CM, TD ou TP n\'a été trouvé pour le semestre "%s".',
                $semestre->getLibelle()
            ));
            return Command::FAILURE;
        }

        if (empty($cmGroups)) {
            $io->warning('Aucun groupe CM n\'est configuré pour ce semestre.');
        }
        if (empty($tdGroups)) {
            $io->warning('Aucun groupe TD n\'est configuré pour ce semestre.');
        }
        if (empty($tpGroups)) {
            $io->warning('Aucun groupe TP n\'est configuré pour ce semestre.');
        }

        // Nettoyage préalable si demandé
        if ($clean) {
            $existingScolSemestres = $this->etudiantScolariteSemestreRepository->findBy(['semestre' => $semestre]);
            $nbExisting = count($existingScolSemestres);
            if ($nbExisting > 0) {
                $io->note(sprintf('Nettoyage de %d inscription(s) existante(s) dans ce semestre...', $nbExisting));
                foreach ($existingScolSemestres as $scolSem) {
                    $scol = $scolSem->getScolarite();
                    $etu = $scol?->getEtudiant();

                    foreach ($scolSem->getGroupes() as $grp) {
                        $grp->removeScolariteSemestre($scolSem);
                        if ($etu) {
                            $grp->removeEtudiant($etu);
                        }
                    }

                    $this->entityManager->remove($scolSem);

                    if ($scol !== null && $scol->getScolariteSemestre()->count() <= 1) {
                        if ($etu !== null && $etu->getScolarites()->count() <= 1) {
                            $this->entityManager->remove($etu);
                        }
                        $this->entityManager->remove($scol);
                    }
                }
                $this->entityManager->flush();
                $io->success('Nettoyage terminé.');
            }
        }

        // Pré-chargement des bacs éventuels pour enrichir les profils
        $bacs = $this->scolBacRepository->findAll();

        $anneePromotion = $anneeUniv->getAnnee() ?? (int) date('Y');
        $anneeBac = $anneePromotion - 1;

        $io->section(sprintf('Génération de %d étudiant(s)...', $count));
        $progressBar = $io->createProgressBar($count);
        $progressBar->start();

        $groupCounters = [];
        $sampleStudent = null;

        $usedUsernames = [];
        $usedNumEtudiants = [];
        $usedNumInes = [];

        for ($i = 0; $i < $count; $i++) {
            $prenom = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
            $nom = self::LAST_NAMES[array_rand(self::LAST_NAMES)];

            $etudiant = new Etudiant();
            $etudiant->setPrenom($prenom);
            $etudiant->setNom($nom);

            // Génération d'un username unique
            $baseUsername = $this->slugify($prenom) . '.' . $this->slugify($nom);
            $username = $baseUsername;
            $suffix = 1;
            while (isset($usedUsernames[$username]) || $this->etudiantRepository->findOneBy(['username' => $username]) !== null) {
                $username = $baseUsername . $suffix;
                $suffix++;
            }
            $usedUsernames[$username] = true;
            $etudiant->setUsername($username);

            // Mails
            $mailUniv = $username . '@etudiant.univ-reims.fr';
            $etudiant->setMailUniv($mailUniv);
            $personalDomain = self::EMAIL_DOMAINS[array_rand(self::EMAIL_DOMAINS)];
            $etudiant->setMailPerso($username . '@' . $personalDomain);

            // Mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($etudiant, $plainPassword);
            $etudiant->setPassword($hashedPassword);

            // Rôles et état
            $etudiant->setRoles(['ROLE_ETUDIANT']);
            $etudiant->setPhotoName('noimage.png');
            $etudiant->setApplications(['Intranet']);
            $etudiant->setBoursier(random_int(1, 10) <= 2);
            $etudiant->setAnneeSortie(0);
            $etudiant->setPromotion($anneePromotion);
            $etudiant->setAnneeBac($anneeBac);

            // Numéro étudiant unique (8 chiffres)
            $prefixYear = $anneePromotion % 100;
            do {
                $numEtu = sprintf('%02d%06d', $prefixYear, random_int(100000, 999999));
            } while (isset($usedNumEtudiants[$numEtu]) || $this->etudiantRepository->findOneBy(['num_etudiant' => $numEtu]) !== null);
            $usedNumEtudiants[$numEtu] = true;
            $etudiant->setNumEtudiant($numEtu);

            // Numéro INE unique (10 chiffres + 1 lettre)
            do {
                $numIne = sprintf('%010d%s', random_int(1000000000, 9999999999), chr(random_int(65, 90)));
            } while (isset($usedNumInes[$numIne]) || $this->etudiantRepository->findOneBy(['num_ine' => $numIne]) !== null);
            $usedNumInes[$numIne] = true;
            $etudiant->setNumIne($numIne);

            // Date de naissance (entre 18 et 23 ans)
            $birthYear = $anneePromotion - random_int(18, 23);
            $birthMonth = random_int(1, 12);
            $birthDay = random_int(1, 28);
            $etudiant->setDateNaissance(new \DateTime(sprintf('%04d-%02d-%02d', $birthYear, $birthMonth, $birthDay)));

            // Téléphone
            $tel = sprintf('0%d%08d', random_int(6, 7), random_int(10000000, 99999999));
            $etudiant->setTel1($tel);

            // Adresse
            $cityData = self::CITIES[array_rand(self::CITIES)];
            $street = self::STREETS[array_rand(self::STREETS)];
            $adresse = new Adresse(
                adresse: $street,
                complement1: null,
                complement2: null,
                ville: $cityData['ville'],
                codePostal: $cityData['codePostal'],
                pays: 'France'
            );
            $etudiant->setAdresseEtudiante($adresse);
            $etudiant->setLieuNaissance($cityData['ville']);

            // Baccalauréat
            if (!empty($bacs)) {
                $etudiant->setBac($bacs[array_rand($bacs)]);
            }

            // Scolarité
            $scolarite = new EtudiantScolarite();
            $scolarite->setEtudiant($etudiant);
            $scolarite->setAnneeUniversitaire($anneeUniv);
            if ($departement !== null) {
                $scolarite->setDepartement($departement);
            }
            $scolarite->setOrdre(1);
            $scolarite->setActif(true);
            $scolarite->setPublic(true);
            $scolarite->setNbAbsences(0);
            $scolarite->setPackages(['intranet']);

            // Scolarité Semestre
            $scolSemestre = new EtudiantScolariteSemestre();
            $scolSemestre->setScolarite($scolarite);
            $scolSemestre->setSemestre($semestre);
            $scolSemestre->setNbAbsences(0);

            // Affectation des groupes (1 TP, 1 TD, 1 CM)
            $assignedTp = null;
            $assignedTd = null;
            $assignedCm = null;

            if (!empty($tpGroups)) {
                $assignedTp = $tpGroups[$i % count($tpGroups)];

                // Si le TP a un parent TD, on privilégie ce parent
                $parent = $assignedTp->getParent();
                if ($parent !== null && $parent->getType() === TypeGroupeEnum::TYPE_GROUPE_TD) {
                    $assignedTd = $parent;
                } elseif (!empty($tdGroups)) {
                    $assignedTd = $tdGroups[$i % count($tdGroups)];
                }

                // Si le TD ou le TP a un parent CM, on privilégie ce parent
                $tdParent = $assignedTd?->getParent();
                if ($tdParent !== null && $tdParent->getType() === TypeGroupeEnum::TYPE_GROUPE_CM) {
                    $assignedCm = $tdParent;
                } elseif ($parent !== null && $parent->getType() === TypeGroupeEnum::TYPE_GROUPE_CM) {
                    $assignedCm = $parent;
                } elseif (!empty($cmGroups)) {
                    $assignedCm = $cmGroups[$i % count($cmGroups)];
                }
            } elseif (!empty($tdGroups)) {
                $assignedTd = $tdGroups[$i % count($tdGroups)];

                $tdParent = $assignedTd->getParent();
                if ($tdParent !== null && $tdParent->getType() === TypeGroupeEnum::TYPE_GROUPE_CM) {
                    $assignedCm = $tdParent;
                } elseif (!empty($cmGroups)) {
                    $assignedCm = $cmGroups[$i % count($cmGroups)];
                }
            } elseif (!empty($cmGroups)) {
                $assignedCm = $cmGroups[$i % count($cmGroups)];
            }

            /** @var StructureGroupe[] $selectedGroups */
            $selectedGroups = array_filter([$assignedCm, $assignedTd, $assignedTp]);
            foreach ($selectedGroups as $groupe) {
                $scolSemestre->addGroupe($groupe);
                $groupe->addScolariteSemestre($scolSemestre);
                $etudiant->addGroupe($groupe);
                $groupe->addEtudiant($etudiant);

                $grpKey = sprintf('%s - %s', $groupe->getType()->value, $groupe->getLibelle());
                $groupCounters[$grpKey] = ($groupCounters[$grpKey] ?? 0) + 1;
            }

            $scolarite->addScolariteSemestre($scolSemestre);
            $etudiant->addScolarite($scolarite);

            $this->entityManager->persist($etudiant);
            $this->entityManager->persist($scolarite);
            $this->entityManager->persist($scolSemestre);

            if ($sampleStudent === null) {
                $sampleStudent = [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'username' => $username,
                    'password' => $plainPassword,
                    'mailUniv' => $mailUniv,
                    'groupes' => array_map(static fn(StructureGroupe $g) => $g->getLibelle(), $selectedGroups),
                ];
            }

            // Flush par lots pour optimiser la mémoire et la performance
            if (($i + 1) % 25 === 0) {
                $this->entityManager->flush();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf(
            '%d étudiants fictifs ont été générés et affectés au semestre "%s" (ID: %d).',
            $count,
            $semestre->getLibelle(),
            $semestre->getId()
        ));

        // Tableau récapitulatif de la répartition des groupes
        if (!empty($groupCounters)) {
            $io->section('Répartition dans les groupes :');
            $tableRows = [];
            ksort($groupCounters);
            foreach ($groupCounters as $grpName => $grpCount) {
                $tableRows[] = [$grpName, $grpCount];
            }
            $table = new Table($output);
            $table
                ->setHeaders(['Groupe', 'Nombre d\'étudiants affectés'])
                ->setRows($tableRows);
            $table->render();
        }

        // Exemple de compte étudiant créé
        $io->section('Exemple de compte étudiant créé pour vos tests :');
        $io->listing([
            sprintf('Nom / Prénom : %s %s', $sampleStudent['nom'], $sampleStudent['prenom']),
            sprintf('Identifiant (username) : <comment>%s</comment>', $sampleStudent['username']),
            sprintf('Mot de passe : <comment>%s</comment>', $sampleStudent['password']),
            sprintf('E-mail universitaire : %s', $sampleStudent['mailUniv']),
            sprintf('Groupes : %s', implode(', ', array_filter($sampleStudent['groupes']))),
        ]);

        return Command::SUCCESS;
    }

    private function slugify(string $text): string
    {
        if (class_exists(\Transliterator::class)) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
            if ($transliterator) {
                $text = $transliterator->transliterate($text);
            }
        } else {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
            if ($converted !== false) {
                $text = strtolower($converted);
            }
        }

        $text = strtolower($text);
        return (string) preg_replace('/[^a-z0-9]/', '', $text);
    }
}
