<?php

namespace App\Command\CopyBdd;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Structure\StructureUe;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\TypeEnseignementEnum;
use App\ValueObject\Adresse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\UuidV4;

#[AsCommand(
    name: 'copy:transfert-bdd:structure',
    description: 'Add a short description for your command',
)]
class CopyTransfertBddCommand extends Command
{
    protected object $em;

    /** @param array<int, StructureDepartement> $tDepartements */
    protected array $tDepartements = [];
    protected array $tAnneeUniversitaire = [];
    protected array $tTypeDiplomes = [];
    protected array $tDiplomes = [];
    protected array $tAnnees = [];
    protected array $tPersonnels = [];
    protected array $tSemestres = [];
    protected array $tMatieres = [];
    protected array $tUes = [];

    protected SymfonyStyle $io;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        ManagerRegistry                  $managerRegistry
    )
    {
        parent::__construct();
        $this->em = $managerRegistry->getConnection('copy');
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
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_type_diplome');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_departement');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_diplome');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_annee');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_semestre');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_ue');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE structure_annee_universitaire');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE scol_edt_event');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE scol_enseignement');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->effacerTables();
        $this->addAnneeUniversitaire();

        // Départements
        $this->addPersonnels();
        //$this->addEtudiants();
        $this->addTypeDiplome();
        $this->addDepartements();
        $this->addPersonnelsDepartements();
        $this->addDiplomes();
        $this->addAnnee();
        $this->addSemestre();
        $this->addUe();
        //APC

        $this->addMatieres();
        $this->addApc();
        //$this->addRessources();
        //$this->addSae();
        $this->addEdt();

        $this->io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }

    public function addAnneeUniversitaire(): void
    {
        $sql = "SELECT * FROM annee_universitaire";
        $annees = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($annees as $annee) {
            $anneeUniversitaire = new StructureAnneeUniversitaire();
            $anneeUniversitaire->setLibelle($annee['libelle']);
            $anneeUniversitaire->setAnnee($annee['annee']);
            $anneeUniversitaire->setActif((bool)$annee['active']);
            $anneeUniversitaire->setCommentaire($annee['commentaire']);

            $this->tAnneeUniversitaire[$annee['id']] = $anneeUniversitaire;

            $this->entityManager->persist($anneeUniversitaire);
            $this->io->info('Année Universitaire : ' . $annee['libelle'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();
    }

    public function addDepartements(): void
    {
        $sql = "SELECT * FROM departement";
        $departements = $this->em->executeQuery($sql)->fetchAllAssociative();
        // dd($departements);

        // parcourir les départements et les insérer dans la base de données de destination avec les mêmes données et l'entité correspondante StructureDepartement

        foreach ($departements as $dept) {
            $departement = new StructureDepartement();
            $departement->setUuid(new UuidV4());
            $departement->setLibelle($dept['libelle']);
            $departement->setLogoName($dept['logo_name']);
            $departement->setTelContact($dept['tel_contact']);
            $departement->setCouleur($dept['couleur']);
            $departement->setSiteWeb($dept['site_web']);
            $departement->setDescription($dept['description']);
            $departement->setActif($dept['actif']);
            $departement->setOpt([
                'materiel' => (bool)$dept['opt_materiel'],
                'edt' => (bool)$dept['opt_edt'],
                'stage' => (bool)$dept['opt_stage'],
            ]);

            $this->tDepartements[$dept['id']] = $departement;

            $this->entityManager->persist($departement);
            $this->io->info('Département : ' . $dept['libelle'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();
    }

    private function addDiplomes(): void
    {
        $sql = "SELECT * FROM diplome WHERE parent_id IS NULL";
        $diplomes = $this->em->executeQuery($sql)->fetchAllAssociative();
        foreach ($diplomes as $dip) {
            $diplome = new StructureDiplome();
            // $diplome->setResponsableDiplome();
            $diplome->setDepartement($this->tDepartements[$dip['departement_id']]);
            $diplome->setLibelle($dip['libelle']);
            $diplome->setActif((bool)$dip['actif']);
            $diplome->setSigle($dip['sigle']);
            $diplome->setKeyEduSign($dip['key_edu_sign']);
            $diplome->setVolumeHoraire($dip['volume_horaire']);
            $diplome->setCodeCelcatDepartement($dip['code_celcat_departement']);
            $diplome->setLogoPartenaire($dip['logo_partenaire']);
            $diplome->setOpt([
                'nb_jours_saisie_absence' => $dip['opt_nb_jours_saisie'],
                'supp_absence' => (bool)$dip['opt_suppr_absence'],
                'anonymat' => (bool)$dip['opt_anonymat'],
                'commentaire_releve' => (bool)$dip['opt_commentaires_releve'],
                'espace_perso_visible' => (bool)$dip['opt_espace_perso_visible'],
                'semaine_visible' => $dip['opt_semaines_visibles'],
                'certif_qualite' => (bool)$dip['opt_certifie_qualite'],
                'resp_qualite' => 0,
                'update_celcat' => (bool)$dip['opt_update_celcat'],
                'saisie_cm_autorisee' => (bool)$dip['saisie_cm_autorise'],
            ]);
            $diplome->setApogeeCodeDiplome($dip['code_diplome']);
            $diplome->setApogeeCodeVersion($dip['code_version']);
            $diplome->setApogeeCodeDepartement($dip['code_departement']);
            $diplome->setTypeDiplome($this->tTypeDiplomes[$dip['type_diplome_id']]);

            /*
             *  "id" => 1
              "responsable_diplome_id" => 200
              "assistant_diplome_id" => 360
              "opt_responsable_qualite_id" => 541
              "referentiel_id" => null
              "apc_parcours_id" => null
             */
            $this->tDiplomes[$dip['id']] = $diplome;

            $this->entityManager->persist($diplome);
            $this->io->info('Diplôme : ' . $dip['libelle'] . ' ajouté pour insertion');
        }

        $sql = "SELECT * FROM diplome WHERE parent_id IS NOT NULL";
        $diplomes = $this->em->executeQuery($sql)->fetchAllAssociative();
        foreach ($diplomes as $dip) {
            $diplome = new StructureDiplome();
            // $diplome->setResponsableDiplome();
            $diplome->setDepartement($this->tDepartements[$dip['departement_id']]);
            $diplome->setLibelle($dip['libelle']);
            $diplome->setActif((bool)$dip['actif']);
            $diplome->setSigle($dip['sigle']);
            $diplome->setKeyEduSign($dip['key_edu_sign']);
            $diplome->setVolumeHoraire($dip['volume_horaire']);
            $diplome->setCodeCelcatDepartement($dip['code_celcat_departement']);
            $diplome->setLogoPartenaire($dip['logo_partenaire']);
            $diplome->setParent($this->tDiplomes[$dip['parent_id']]);
            $diplome->setOpt([
                'nb_jours_saisie_absence' => $dip['opt_nb_jours_saisie'],
                'supp_absence' => (bool)$dip['opt_suppr_absence'],
                'anonymat' => (bool)$dip['opt_anonymat'],
                'commentaire_releve' => (bool)$dip['opt_commentaires_releve'],
                'espace_perso_visible' => (bool)$dip['opt_espace_perso_visible'],
                'semaine_visible' => $dip['opt_semaines_visibles'],
                'certif_qualite' => (bool)$dip['opt_certifie_qualite'],
                'resp_qualite' => 0,
                'update_celcat' => (bool)$dip['opt_update_celcat'],
                'saisie_cm_autorisee' => (bool)$dip['saisie_cm_autorise'],
            ]);

            /*
             *  "id" => 1
              "responsable_diplome_id" => 200
              "assistant_diplome_id" => 360
              "type_diplome_id" => 2
              "code_version" => "111"
              "code_departement" => "285"
              "code_diplome" => "5PSP1"
              "opt_responsable_qualite_id" => 541
              "referentiel_id" => null
              "parent_id" => null
              "apc_parcours_id" => null
             */
            $this->tDiplomes[$dip['id']] = $diplome;

            $this->entityManager->persist($diplome);
        }

        $this->entityManager->flush();
    }

    private function addAnnee(): void
    {
        $sql = "SELECT * FROM annee";
        $annees = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($annees as $an) {
            $annee = new StructureAnnee();
            // $annee->setDiplome($this->tDiplomes[$an['diplome_id']]);
            $annee->setLibelle($an['libelle']);
            $annee->setOrdre($an['ordre']);
            $annee->setLibelleLong($an['libelle_long']);
            $annee->setActif((bool)$an['actif']);
            $annee->setCouleur($an['couleur']);
            $annee->setOpt([
                'alternance' => (bool)$an['opt_alternance'],
            ]);
            $annee->setApogeeCodeEtape($an['code_etape']);
            $annee->setApogeeCodeVersion($an['code_version']);

            $this->tAnnees[$an['id']] = $annee;

            $this->entityManager->persist($annee);
        }

        $this->entityManager->flush();
    }

    private function addSemestre(): void
    {
        $sql = "SELECT * FROM semestre";
        $semestres = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($semestres as $sem) {
            $semestre = new StructureSemestre();
            $semestre->setOpt(
                [
                    'mail_releve' => (bool)$sem['opt_mail_releve'],
                    'mail_modif_note' => (bool)$sem['opt_mail_modification_note'],
                    'dest_mail_releve' => $sem['opt_dest_mail_releve_id'] ?? 0,
                    'dest_mail_modif_note' => $sem['opt_dest_mail_modif_note_id'] ?? 0,
                    'eval_visible' => (bool)$sem['opt_evaluation_visible'],
                    'eval_modif' => (bool)$sem['opt_evaluation_modifiable'],
                    'penalite_absence' => (float)$sem['opt_point_penalite_absence'],
                    'mail_absence_resp' => (bool)$sem['opt_mail_absence_resp'],
                    'dest_mail_absence_resp' => $sem['opt_dest_mail_absence_resp_id'] ?? 0,
                    'mail_absence_etudiant' => (bool)$sem['opt_mail_absence_etudiant'],
                    'opt_penalite_absence' => (bool)$sem['opt_penalite_absence'],
                    'mail_assistante_justif_absence' => (bool)$sem['opt_mail_assistante_justificatif_absence'],
                    'bilan_semestre' => (bool)$sem['opt_bilan_semestre'],
                    'rattrapage' => (bool)$sem['opt_rattrapage'],
                    'mail_rattrapage' => $sem['opt_mail_rattrapage'] ?? 0,
                ]
            );

            $semestre->setAnnee($this->tAnnees[$sem['annee_id']]);
            $semestre->setLibelle($sem['libelle']);
            $semestre->setOrdreAnnee($sem['ordre_annee']);
            $semestre->setOrdreLmd($sem['ordre_lmd']);
            $semestre->setActif((bool)$sem['actif']);
            $semestre->setNbGroupesCm((int)$sem['nb_groupes_cm']);
            $semestre->setNbGroupesTd((int)$sem['nb_groupes_td']);
            $semestre->setNbGroupesTp((int)$sem['nb_groupes_tp']);
            $semestre->setKeyEduSign($sem['id_edu_sign']);
            $semestre->setCodeElement($sem['code_element']);

            /*
             *   "id" => 1
  "ppn_actif_id" => 1
  "created" => "2019-12-09 21:38:01"
  "updated" => "2023-10-13 11:17:10"
  "mois_debut" => 9
             */

            $this->tSemestres[$sem['id']] = $semestre;

            $this->entityManager->persist($semestre);
            $this->io->info('Semestre : ' . $sem['libelle'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();
    }

    private function addUe(): void
    {
        $sql = "SELECT * FROM ue";
        $ues = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($ues as $u) {
            $ue = new StructureUe();
            $ue->setSemestre($this->tSemestres[$u['semestre_id']]);
            $ue->setLibelle($u['libelle']);
            $ue->setNumero((int)$u['numero_ue']);
            $ue->setCodeElement($u['code_element']);
            $ue->setActif((bool)$u['actif']);
            $ue->setBonification((bool)$u['bonification']);
            $ue->setNbEcts((float)$u['nb_ects']); //Apc?
            $this->tUes[$u['id']] = $ue;

            $this->entityManager->persist($ue);
            $this->io->info('UE : ' . $u['libelle'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();
    }

    private function addTypeDiplome(): void
    {
        $sql = "SELECT * FROM type_diplome";
        $typeD = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($typeD as $type) {
            $typeDiplome = new StructureTypeDiplome();
            $typeDiplome->setLibelle($type['libelle']);
            $typeDiplome->setSigle($type['sigle']);
            $typeDiplome->setApc((bool)$type['apc']);

            $this->tTypeDiplomes[$type['id']] = $typeDiplome;

            $this->entityManager->persist($typeDiplome);
            $this->io->info('Type Diplome : ' . $type['libelle'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();
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
            $personnel->setRoles(json_decode($pers['roles'], true) ?? []);
            $personnel->setStructureAnneeUniversitaire($this->tAnneeUniversitaire[$pers['annee_universitaire_id']]);

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
  "password" => "$2y$13$mNYMwSaTHH9vqqYjlsma4OXq5wQCr3aagR9bqT0.bpusnKKfcebI."
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
        $sql = 'SELECT * FROM etudiant';
        $etudiants = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($etudiants as $etu) {
            $etudiant = new Etudiant();
            $etudiant->setNom($etu['nom']);
            $etudiant->setPrenom($etu['prenom']);
            $etudiant->setMailUniv($etu['mail_univ']);
            $etudiant->setUsername($etu['username']);
            $etudiant->setPhotoName($etu['photo_name']);
            $etudiant->setPassword($etu['password']);
            $etudiant->setRoles(json_decode($etu['roles'], true) ?? ["ROLE_ETUDIANT"]);

            // gestion des adresses : adresse etudiante et adresse parentale
            if ($etu['adresse_id'] !== null && $etu['adresse_id'] !== '') {
                $sql = 'SELECT * FROM adresse WHERE id = ' . $etu['adresse_id'];
                $adresse = $this->em->executeQuery($sql)->fetchAssociative();

                $objAdresseEtudiante = new Adresse(
                    $adresse['adresse1'] ?? '',
                    $adresse['adresse2'] ?? '',
                    $adresse['adresse3'] ?? '',
                    $adresse['code_postal'] ?? '',
                    $adresse['ville'] ?? '',
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
                    $adresse['code_postal'] ?? '',
                    $adresse['ville'] ?? '',
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

    private function addPersonnelsDepartements(): void
    {
        $sql = 'SELECT * FROM personnel_departement';
        $persDepts = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($persDepts as $persDept) {
            $depPers = new StructureDepartementPersonnel();
            $depPers->setDepartement($this->tDepartements[$persDept['departement_id']]);
            $depPers->setPersonnel($this->tPersonnels[$persDept['personnel_id']]);
            $depPers->setDefaut((bool)$persDept['defaut']);
            $depPers->setRoles(json_decode($persDept['roles'], true) ?? []);

            $this->entityManager->persist($depPers);
            $this->io->info('Personnel : ' . $this->tPersonnels[$persDept['personnel_id']]->getNom() . ' ajouté au département ' . $this->tDepartements[$persDept['departement_id']]->getLibelle());
        }

        $this->entityManager->flush();
    }

    private function addMatieres(): void
    {
        // matières, ressources, SAE
        $sql = 'SELECT * FROM matiere WHERE matiere_parent_id IS NULL';
        $matieres = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($matieres as $mat) {
            $matiere = new ScolEnseignement();
            $matiere->setLibelle($mat['libelle']);
            $matiere->setCodeMatiere($mat['code_matiere']);
            $matiere->setCodeApogee($mat['code_element']);
            $matiere->setHeures([
                'heures' => [
                    'CM' => ['PN' => (float)$mat['cm_ppn'], 'IUT' => (float)$mat['cm_formation']],
                    'TD' => ['PN' => (float)$mat['td_ppn'], 'IUT' => (float)$mat['td_formation']],
                    'TP' => ['PN' => (float)$mat['tp_ppn'], 'IUT' => (float)$mat['tp_formation']],
                    'Projet' => ['PN' => 0, 'IUT' => 0],
                ],
            ]);
            $matiere->setType(TypeEnseignementEnum::TYPE_MATIERE);
            $matiere->setBonification((bool)$mat['pac']);
            $matiere->setDescription($mat['description']);
            $matiere->setNbNotes((int)$mat['nb_notes']);
            $matiere->setLibelleCourt($mat['libelle_court']);
            $matiere->setSuspendu((bool)$mat['suspendu']);
            $matiere->setMutualisee((bool)$mat['mutualisee']);
            $matiere->setMotsCles($mat['mots_cles']);
            $matiere->setObjectif($mat['objectifs_module']);
            $matiere->setPrerequis($mat['pre_requis']);

            /*
             * array:30 [
  "ppn_id" => 1
  "parcours_id" => null
]
             */
            $this->entityManager->persist($matiere);
            $this->tMatieres[$mat['id']] = $matiere;

            if ($mat['ue_id'] !== null && $mat['ue_id'] !== '') {

                $matiereUe = new ScolEnseignementUe(
                    $matiere,
                    $this->tUes[$mat['ue_id']],
                );
                $matiereUe->setCoefficient((float)$mat['coefficient']);
                $matiereUe->setEcts((float)$mat['nb_ects']);
                $this->entityManager->persist($matiereUe);

            }

            $this->io->info('Matière : ' . $mat['libelle'] . ' ajouté pour insertion');
        }

        $sql = 'SELECT * FROM matiere WHERE matiere_parent_id IS NOT NULL';
        $matieres = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($matieres as $mat) {
            $matiere = new ScolEnseignement();
            $matiere->setLibelle($mat['libelle']);
            $matiere->setCodeMatiere($mat['code_matiere']);
            $matiere->setCodeApogee($mat['code_element']);
            $matiere->setHeures([
                'heures' => [
                    'CM' => ['PN' => (float)$mat['cm_ppn'], 'IUT' => (float)$mat['cm_formation']],
                    'TD' => ['PN' => (float)$mat['td_ppn'], 'IUT' => (float)$mat['td_formation']],
                    'TP' => ['PN' => (float)$mat['tp_ppn'], 'IUT' => (float)$mat['tp_formation']],
                    'Projet' => ['PN' => 0, 'IUT' => 0],
                ],
            ]);
            $matiere->setType(TypeEnseignementEnum::TYPE_MATIERE);
            $matiere->setBonification((bool)$mat['pac']);
            $matiere->setDescription($mat['description']);
            $matiere->setNbNotes((int)$mat['nb_notes']);
            $matiere->setLibelleCourt($mat['libelle_court']);
            $matiere->setSuspendu((bool)$mat['suspendu']);
            $matiere->setMutualisee((bool)$mat['mutualisee']);
            $matiere->setMotsCles($mat['mots_cles']);
            $matiere->setObjectif($mat['objectifs_module']);
            $matiere->setPrerequis($mat['pre_requis']);
            $matiere->setParent($this->tMatieres[$mat['matiere_parent_id']]);

            /*
             * array:30 [
  "ppn_id" => 1
  "parcours_id" => null
]
             */
            $this->entityManager->persist($matiere);

            if ($mat['ue_id'] !== null && $mat['ue_id'] !== '') {

                $matiereUe = new ScolEnseignementUe(
                    $matiere,
                    $this->tUes[$mat['ue_id']],
                );
                $matiereUe->setCoefficient((float)$mat['coefficient']);
                $matiereUe->setEcts((float)$mat['nb_ects']);
                $this->entityManager->persist($matiereUe);

            }

            $this->io->info('Matière : ' . $mat['libelle'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();
    }

    private function addEdt(): void
    {}

    private function addApc()
    {

    }
}
