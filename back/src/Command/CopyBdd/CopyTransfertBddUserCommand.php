<?php

namespace App\Command\CopyBdd;

use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\StatutEnum;
use App\Repository\EtudiantRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureDepartementRepository;
use App\Repository\Structure\StructureGroupeRepository;
use App\ValueObject\Adresse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'copy:transfert-bdd:user',
    description: 'Add a short description for your command',
)]
class CopyTransfertBddUserCommand extends Command
{
    protected object $em;

    protected array $tPersonnels = [];
    protected array $tEtudiants = [];
    protected array $tAnneeUniversitaire = [];
    protected array $tDepartements = [];
    protected array $tGroupes = [];

    protected SymfonyStyle $io;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        ManagerRegistry                  $managerRegistry,
        StructureAnneeUniversitaireRepository $structureAnneeUniversitaireRepository,
        StructureDepartementRepository $structureDepartementRepository,
        StructureGroupeRepository $structureGroupeRepository,
        EtudiantRepository $etudiantRepository,
    )
    {
        parent::__construct();
        $this->em = $managerRegistry->getConnection('copy');
        $this->tAnneeUniversitaire = $structureAnneeUniversitaireRepository->findAllByIdArray();
        $this->tDepartements = $structureDepartementRepository->findAllByIdArray();
        $this->tGroupes = $structureGroupeRepository->findAllByOldIdArray();
        $this->tEtudiants = $etudiantRepository->findAllByOldIdArray();
    }

    protected function configure(): void
    {
    }

    private function effacerTables(): void
    {
        // vider les tables de destination et les réinitialiser
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=0');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE personnel');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_departement_personnel');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE etudiant');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->effacerTables();

        // Départements
        $this->addPersonnels();
        $this->addEtudiants();
        $this->addPersonnelsDepartements();
        $this->addEtudiantsGroupes();

        $this->io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }

    private function addPersonnels(): void
    {
        $sql = 'SELECT * FROM personnel';
        $personnels = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($personnels as $pers) {
            $personnel = new Personnel();
            $personnel->setNom($pers['nom']);
            $personnel->setPrenom($pers['prenom']);
            $personnel->setMailUniv($pers['mail_univ']);
            $personnel->setUsername($pers['username']);
            $personnel->setPassword($pers['password']);
            $personnel->setPhotoName($pers['photo_name']);
            $personnel->setInitiales(substr($pers['initiales'], 0,3));
            $personnel->setOldId($pers['id']);
            $personnel->setRoles(json_decode($pers['roles'], true) ?? []);
            $personnel->setAnneeUniversitaire($this->tAnneeUniversitaire[$pers['annee_universitaire_id']]);
            $personnel->setEntreprise($pers['entreprise']);
            $personnel->setTelBureau($pers['tel_bureau']);
            $personnel->setDomaines(
            // transformer le string $pers['domaines'] en tableau
                explode(',', $pers['domaines'])
            );
            $personnel->setBureau($pers['bureau1']);
            $personnel->setNumeroHarpege($pers['numero_harpege']);
            $personnel->setNbHeuresService($pers['nb_heures_service']);
            $personnel->setMailPerso($pers['mail_perso']);
            $personnel->setSitePerso($pers['site_perso']);
            $personnel->setSiteUniv($pers['site_univ']);
            $personnel->setResponsabilites($pers['responsabilites']);
            $personnel->setPosteInterne($pers['poste_interne']);
            $personnel->setStatut(StatutEnum::tryFrom($pers['statut']));
            $personnel->setApplications(['UniTranet']);

            // gestion des adresses
            if ($pers['adresse_id'] !== null && $pers['adresse_id'] !== '') {
                $sql = 'SELECT * FROM adresse WHERE id = ' . $pers['adresse_id'];
                $adresse = $this->em->executeQuery($sql)->fetchAssociative();

                $objAdresse = new Adresse(
                    $adresse['adresse1'] ?? '',
                    $adresse['adresse2'] ?? '',
                    $adresse['adresse3'] ?? '',
                    $adresse['code_postal'] ?? '',
                    $adresse['ville'] ?? '',
                    $adresse['pays'] ?? 'France'
                );
                $personnel->setAdressePersonnelle($objAdresse);
            }

            /*
             * "id" => 1
  "statut" => "vacataire"
  "poste_interne" => null
  "tel_bureau" => null
  "responsabilites" => null
  "domaines" => null
  "entreprise" => null
  "bureau1" => null
  "bureau2" => null
  "numero_harpege" => 18027
  "initiales" => null
  "cv_name" => ""
  "nb_heures_service" => 384.0
  "deleted" => 0
  "couleur" => ""
  "slug" => "a.martinot"
  "type_user" => "vacataire"
  "site_univ" => null
  "mail_perso" => "a.martinot@wanadoo.fr"
  "site_perso" => null
  "civilite" => "M."
  "date_naissance" => "1956-06-17"
  "tel1" => null
  "tel2" => null
  "remarque" => null
  "signature" => null
  "visible" => 1
  "updated" => "2024-09-19 17:27:26"
  "reset_token" => null
  "signature_electronique" => null
  "lieu_naissance" => "Chaumont"
  "configuration" => null
  "access_originaux" => 0
  "id_edu_sign" => "{"3":"xwdv9d59utg5fafu"}"
             */

            $this->tPersonnels[$pers['id']] = $personnel;

            $this->entityManager->persist($personnel);
            $this->io->info('Personnel : ' . $pers['nom'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();
    }

    private function addEtudiants(): void
    {
        $sql = 'SELECT * FROM etudiant WHERE semestre_id IS NOT NULL and annee_sortie = 0'; // juste pour des datas
        $etudiants = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($etudiants as $etu) {
            $etudiant = new Etudiant();
            $etudiant->setNom($etu['nom']);
            $etudiant->setPrenom($etu['prenom']);
            $etudiant->setMailUniv($etu['mail_univ']);
            $etudiant->setMailPerso($etu['mail_perso']);
            $etudiant->setUsername($etu['username']);
            $etudiant->setPhotoName($etu['photo_name']);
            $etudiant->setOldId($etu['id']);
            $etudiant->setPassword($etu['password']);
            $etudiant->setRoles(json_decode($etu['roles'], true) ?? ["ROLE_ETUDIANT"]);
            $etudiant->setSitePerso($etu['site_perso']);
            $etudiant->setSiteUniv($etu['site_univ']);
            $etudiant->setNumEtudiant($etu['num_etudiant']);
            $etudiant->setNumIne($etu['num_ine']);
            $etudiant->setAnneeBac($etu['annee_bac']);
            $etudiant->setBoursier((bool)$etu['boursier']);
            $etudiant->setAmenagementsParticuliers($etu['amenagements_particuliers']);
            $etudiant->setPromotion($etu['promotion']);
            $etudiant->setAnneeSortie($etu['annee_sortie']);
            $etudiant->setDateNaissance(new \DateTime($etu['date_naissance']));
            $etudiant->setTel1($etu['tel1']);
            $etudiant->setTel2($etu['tel2']);
            $etudiant->setLieuNaissance($etu['lieu_naissance']);

            // gestion des adresses : adresse etudiante et adresse parentale
            if ($etu['adresse_id'] !== null && $etu['adresse_id'] !== '') {
                $sql = 'SELECT * FROM adresse WHERE id = ' . $etu['adresse_id'];
                $adresse = $this->em->executeQuery($sql)->fetchAssociative();

                $objAdresseEtudiante = new Adresse(
                    $adresse['adresse1'] ?? '',
                    $adresse['adresse2'] ?? '',
                    $adresse['adresse3'] ?? '',
                    $adresse['ville'] ?? '',
                    $adresse['code_postal'] ?? '',
                    $adresse['pays'] ?? 'France'
                );
                $etudiant->setAdresseEtudiante($objAdresseEtudiante);
            }

            if ($etu['adresse_parentale_id'] !== null && $etu['adresse_parentale_id'] !== '') {
                $sql = 'SELECT * FROM adresse WHERE id = ' . $etu['adresse_parentale_id'];
                $adresse = $this->em->executeQuery($sql)->fetchAssociative();

                $objAdresseParentale = new Adresse(
                    $adresse['adresse1'] ?? '',
                    $adresse['adresse2'] ?? '',
                    $adresse['adresse3'] ?? '',
                    $adresse['ville'] ?? '',
                    $adresse['code_postal'] ?? '',
                    $adresse['pays'] ?? 'France'
                );
                $etudiant->setAdresseParentale($objAdresseParentale);
            }


            $this->entityManager->persist($etudiant);
            $this->io->info('Etudiant : ' . $etu['nom'] . ' ajouté pour insertion');

            /*
             * "id" => 30
  "semestre_id" => null
  "bac_id" => null
  "uuid" => "�G�Lo�C�"
  "num_etudiant" => "21701820"
  "num_ine" => "2410019804U"
  "annee_bac" => 2017
  "boursier" => 0
  "demandeur_emploi" => 0
  "deleted" => 0
  "amenagements_particuliers" => null
  "promotion" => 2019
  "intitule_securite_sociale" => null
  "adresse_securite_sociale" => null
  "annee_sortie" => 2020
  "slug" => "julie.bastard"
  "type_user" => "etudiant"
  "site_univ" => null
  "mail_perso" => null
  "site_perso" => null
  "civilite" => "Mme"
  "date_naissance" => "1999-09-13"
  "tel1" => "06.46.29.77.06"
  "tel2" => null
  "remarque" => null
  "signature" => null
  "visible" => 1
  "updated" => "2021-06-29 10:16:48"
  "reset_token" => null
  "departement_id" => 1
  "login_specifique" => null
  "formation_continue" => 0
  "lieu_naissance" => null
  "id_edu_sign" => null

             */
        }

        $this->entityManager->flush();
    }

    private function addEtudiantsGroupes(): void
    {
        $this->tEtudiants = $this->entityManager->getRepository(Etudiant::class)->findAllByOldIdArray();
        $sql = 'SELECT * FROM etudiant_groupe';
        $etudiantsGroupes = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($etudiantsGroupes as $etuGroupe) {
            // Chercher l'étudiant correspondant
            if (!isset($this->tEtudiants[$etuGroupe['etudiant_id']])) {
                $this->io->warning('Etudiant ID ' . $etuGroupe['etudiant_id'] . ' non trouvé, skip.');
                continue;
            }
            $etudiant = $this->tEtudiants[$etuGroupe['etudiant_id']];

            // Chercher le groupe correspondant
            if (!isset($this->tGroupes[$etuGroupe['groupe_id']])) {
                $this->io->warning('Groupe ID ' . $etuGroupe['groupe_id'] . ' non trouvé, skip.');
                continue;
            }
            $groupe = $this->tGroupes[$etuGroupe['groupe_id']];

            // Ajouter l'étudiant au groupe
            $groupe->addEtudiant($etudiant);
            $etudiant->addGroupe($groupe);

            $this->entityManager->persist($groupe);
            $this->entityManager->persist($etudiant);
            $this->io->info('Etudiant : ' . $etudiant->getNom() . ' ajouté au groupe ' . $groupe->getLibelle());
        }
    }

    private function addPersonnelsDepartements(): void
    {
        $sql = 'SELECT * FROM personnel_departement';
        $persDepts = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($persDepts as $persDept) {
            $depPers = new StructureDepartementPersonnel();
            // Chercher le département correspondant
            $departementTrouve = null;
            foreach ($this->tDepartements as $departement) {
                if ($departement->getOldId() === $persDept['departement_id']) {
                    $departementTrouve = $departement;
                    break;
                }
            }
            // Si le département n'existe pas, on skip
            if (!$departementTrouve) {
                continue;
            }
            $depPers->setDepartement($departementTrouve);
            $depPers->setPersonnel($this->tPersonnels[$persDept['personnel_id']]);
            $depPers->setDefaut((bool)$persDept['defaut']);
            $depPers->setRoles(['intranet' => json_decode($persDept['roles'], true)] ?? []);

            $this->entityManager->persist($depPers);
            // $this->io->info('Personnel : ' . $this->tPersonnels[$persDept['personnel_id']]->getNom() . ' ajouté au département ' . $departementTrouve->getLibelle());
        }

        $this->entityManager->flush();
    }
}
