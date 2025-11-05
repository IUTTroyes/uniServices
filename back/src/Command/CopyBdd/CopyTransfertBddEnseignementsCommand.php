<?php

namespace App\Command\CopyBdd;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Scolarite\ScolEvaluation;
use App\Enum\TypeEnseignementEnum;
use App\Repository\Apc\ApcApprentissageCritiqueRepository;
use App\Repository\Apc\ApcCompetenceRepository;
use App\Repository\Structure\StructureUeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Uid\UuidV4;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'copy:transfert-bdd:enseignements',
    description: 'Copie les matières, ressources, SAE',
)]
class CopyTransfertBddEnseignementsCommand extends Command
{
    protected object $em;

    protected array $tMatieres = [];
    protected array $tCompetences = [];
    protected array $tUes = [];
    protected array $tApprentissages = [];

    protected SymfonyStyle $io;
    protected string $base_url;


    public function __construct(
        protected EntityManagerInterface   $entityManager,
        ManagerRegistry                    $managerRegistry,
        ApcApprentissageCritiqueRepository $apcApprentissageCritiqueRepository,
        ApcCompetenceRepository            $apcCompetenceRepository,
        StructureUeRepository              $structureUeRepository,
        protected HttpClientInterface      $httpClient,
        ParameterBagInterface              $params
    )
    {
        parent::__construct();
        $this->tCompetences = $apcCompetenceRepository->findAllByOldIdArray();
        $this->tApprentissages = $apcApprentissageCritiqueRepository->findAllByOldIdArray();
        $this->tUes = $structureUeRepository->findAllByOldIdArray();
        $this->base_url = $params->get('URL_INTRANET_V3');
        $this->httpClient = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
        ]);
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
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE scol_enseignement');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE scol_enseignement_ue');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE scol_enseignement_apc_apprentissage_critique');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->effacerTables();

        // Départements
        $this->addMatieres();
        $this->addRessources();
        $this->addSaes();

        $this->io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }

    private function addMatieres(): void
    {
        // matières, ressources, SAE
        $sql = 'SELECT * FROM matiere WHERE matiere_parent_id IS NULL';
        $matieres = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($matieres as $mat) {
            if (array_key_exists($mat['ue_id'], $this->tUes)) {
                $matiere = new ScolEnseignement();
                $matiere->setLibelle($mat['libelle']);
                $matiere->setCodeEnseignement($mat['code_matiere']);
                $matiere->setCodeApogee($mat['code_element']);
                $matiere->setHeures([
                    'CM' => ['PN' => (float)$mat['cm_ppn'], 'IUT' => (float)$mat['cm_formation']],
                    'TD' => ['PN' => (float)$mat['td_ppn'], 'IUT' => (float)$mat['td_formation']],
                    'TP' => ['PN' => (float)$mat['tp_ppn'], 'IUT' => (float)$mat['tp_formation']],
                    'Projet' => ['PN' => 0, 'IUT' => 0],
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
                $matiere->setOldId($mat['id']);

                $nbNotes = (int)$mat['nb_notes'];
                    for ($i = 1; $i <= $nbNotes; $i++) {
                        $evaluation = new ScolEvaluation();
                        $evaluation->setLibelle('Évaluation ' . $i);
                        $evaluation->setEnseignement($matiere);
                        $evaluation->setVisible(true);
                        $evaluation->setModifiable(true);
                        $evaluation->setUuid(new UuidV4());
                        $this->entityManager->persist($evaluation);
                    }

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
        }

        $sql = 'SELECT * FROM matiere WHERE matiere_parent_id IS NOT NULL';
        $matieres = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($matieres as $mat) {
            if (array_key_exists($mat['ue_id'], $this->tUes)) {
                $matiere = new ScolEnseignement();
                $matiere->setLibelle($mat['libelle']);
                $matiere->setCodeEnseignement($mat['code_matiere']);
                $matiere->setCodeApogee($mat['code_element']);
                $matiere->setHeures([
                    'CM' => ['PN' => (float)$mat['cm_ppn'], 'IUT' => (float)$mat['cm_formation']],
                    'TD' => ['PN' => (float)$mat['td_ppn'], 'IUT' => (float)$mat['td_formation']],
                    'TP' => ['PN' => (float)$mat['tp_ppn'], 'IUT' => (float)$mat['tp_formation']],
                    'Projet' => ['PN' => 0, 'IUT' => 0],
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
                $matiere->setOldId($mat['id']);

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
        }

        $this->entityManager->flush();
    }

    private function addRessources(): int
    {
        $response = $this->httpClient->request('GET', $this->base_url . '/ressources', [
            'timeout' => 600,
        ]);

        $matieres = json_decode($response->getContent(), true);
        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->io->error('JSON decoding error: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        foreach ($matieres as $mat) {
            $matiere = new ScolEnseignement();
            $matiere->setLibelle($mat['libelle']);
            $matiere->setCodeEnseignement($mat['code_matiere']);
            $matiere->setCodeApogee($mat['code_element']);
            $matiere->setHeures([
                'CM' => ['PN' => (float)$mat['cm_ppn'], 'IUT' => (float)$mat['cm_formation']],
                'TD' => ['PN' => (float)$mat['td_ppn'], 'IUT' => (float)$mat['td_formation']],
                'TP' => ['PN' => (float)$mat['tp_ppn'], 'IUT' => (float)$mat['tp_formation']],
                'Projet' => ['PN' => 0, 'IUT' => 0],
            ]);
            $matiere->setType(TypeEnseignementEnum::TYPE_RESSOURCE);
            $matiere->setBonification(false);
            $matiere->setDescription($mat['description']);
            $matiere->setNbNotes((int)$mat['nb_notes']);
            $matiere->setLibelleCourt($mat['libelle_court']);
            $matiere->setSuspendu((bool)$mat['suspendu']);
            $matiere->setMutualisee((bool)$mat['mutualisee']);
            $matiere->setMotsCles($mat['mots_cles']);
            $matiere->setPrerequis($mat['pre_requis']);
            $matiere->setOldId($mat['id']);

            $nbNotes = (int)$mat['nb_notes'];
            for ($i = 1; $i <= $nbNotes; $i++) {
                $evaluation = new ScolEvaluation();
                $evaluation->setLibelle('Évaluation ' . $i);
                $evaluation->setEnseignement($matiere);
                $evaluation->setVisible(true);
                $evaluation->setModifiable(true);
                $evaluation->setUuid(new UuidV4());
                $this->entityManager->persist($evaluation);
            }

            /*
  "id" => 1
  "semestre_id" => Via les UE ??
  "commentaire" => null
  "ressource_parent" => 0
  "has_coefficient_different" => 0
             */
            $this->entityManager->persist($matiere);
            //todo: traiter les ressources enfants a ajouter dans l'API
            // $this->tMatieres[$mat['id']] = $matiere;
            // dump($mat['id']);
            // récupérer les dépendances de ApcRessources : ApprentissagesCrtiques, Competences, semestre
            $taddUes = [];
            if (array_key_exists('ues', $mat)) {
                foreach ($mat['ues'] as $apcCompetence) {
//                    dd($apcCompetence);
                    if (array_key_exists($apcCompetence['ue_id'], $this->tUes) &&
                        !array_key_exists($this->tUes[$apcCompetence['ue_id']]->getId(), $taddUes)
                    ) {
                        dump($apcCompetence['ue_id']);
                        $apc = new ScolEnseignementUe(
                            $matiere,
                            $this->tUes[$apcCompetence['ue_id']],
                        );
                        $taddUes[$this->tUes[$apcCompetence['ue_id']]->getId()] = $apc;
                        $apc->setCoefficient((float)$apcCompetence['coefficient']);
                        $apc->setEcts((float)$apcCompetence['coefficient']);
                        //todo: parcours
                        $this->entityManager->persist($apc);
                    } else {
                        $this->io->error('UE ' . $apcCompetence['ue_id'] . ' non trouvée dans la table des UEs');
                    }
                }
            }

            $sqlApcCritique = 'SELECT * FROM apc_ressource_apprentissage_critique WHERE ressource_id = ' . $mat['id'];
            $apcCritiques = $this->em->executeQuery($sqlApcCritique)->fetchAllAssociative();

            foreach ($apcCritiques as $apcCritique) {
                if (array_key_exists($apcCritique['apprentissage_critique_id'], $this->tApprentissages) &&
                    !$matiere->getApprentissageCritique()->contains($this->tApprentissages[$apcCritique['apprentissage_critique_id']])
                ) {
                    $matiere->addApprentissageCritique($this->tApprentissages[$apcCritique['apprentissage_critique_id']]);
                }
            }

            $this->io->info('Ressource : ' . $mat['libelle'] . ' ajouté pour insertion');
        }

//        $sql = 'SELECT * FROM apc_ressource WHERE ressource_parent = true';
//        $matieres = $this->em->executeQuery($sql)->fetchAllAssociative();
//
//        foreach ($matieres as $mat) {
//            $matiere = new ScolEnseignement();
//            $matiere->setLibelle($mat['libelle']);
//            $matiere->setCodeEnseignement($mat['code_matiere']);
//            $matiere->setCodeApogee($mat['code_element']);
//            $matiere->setHeures([
//                'heures' => [
//                    'CM' => ['PN' => (float)$mat['cm_ppn'], 'IUT' => (float)$mat['cm_formation']],
//                    'TD' => ['PN' => (float)$mat['td_ppn'], 'IUT' => (float)$mat['td_formation']],
//                    'TP' => ['PN' => (float)$mat['tp_ppn'], 'IUT' => (float)$mat['tp_formation']],
//                    'Projet' => ['PN' => 0, 'IUT' => 0],
//                ],
//            ]);
//            $matiere->setType(TypeEnseignementEnum::TYPE_MATIERE);
//            $matiere->setBonification((bool)$mat['pac']);
//            $matiere->setDescription($mat['description']);
//            $matiere->setNbNotes((int)$mat['nb_notes']);
//            $matiere->setLibelleCourt($mat['libelle_court']);
//            $matiere->setSuspendu((bool)$mat['suspendu']);
//            $matiere->setMutualisee((bool)$mat['mutualisee']);
//            $matiere->setMotsCles($mat['mots_cles']);
//            $matiere->setObjectif($mat['objectifs_module']);
//            $matiere->setPrerequis($mat['pre_requis']);
//            $matiere->setParent($this->tMatieres[$mat['matiere_parent_id']]);
//
//            /*
//             * array:30 [
//  "ppn_id" => 1
//  "parcours_id" => null
//]
//             */
//            $this->entityManager->persist($matiere);
//
//            if ($mat['ue_id'] !== null && $mat['ue_id'] !== '') {
//
//                $matiereUe = new ScolEnseignementUe(
//                    $matiere,
//                    $this->tUes[$mat['ue_id']],
//                );
//                $matiereUe->setCoefficient((float)$mat['coefficient']);
//                $matiereUe->setEcts((float)$mat['nb_ects']);
//                $this->entityManager->persist($matiereUe);
//
//            }
//
//            $sqlApcCritique = 'SELECT * FROM apc_ressource_apprentissage_critique WHERE ressource_id = ' . $mat['id'];
//            $apcCritiques = $this->em->executeQuery($sqlApcCritique)->fetchAllAssociative();
//
//            foreach ($apcCritiques as $apcCritique) {
//                if (array_key_exists($apcCritique['apprentissage_critique_id'], $this->tApprentissages) &&
//                    !$matiere->getApcApprentissageCritique()->contains($this->tApprentissages[$apcCritique['apprentissage_critique_id']])
//                ) {
//                    $matiere->addApcApprentissageCritique($this->tApprentissages[$apcCritique['apprentissage_critique_id']]);
//                }
//            }
//
//            $this->io->info('Ressource enfant : ' . $mat['libelle'] . ' ajouté pour insertion');
//        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function addSaes(): void
    {
        $response = $this->httpClient->request('GET', $this->base_url . '/saes');
        $matieres = json_decode($response->getContent(), true);;
        // matières, ressources, SAE

        foreach ($matieres as $mat) {
            $matiere = new ScolEnseignement();
            $matiere->setLibelle($mat['libelle']);
            $matiere->setCodeEnseignement($mat['code_matiere']);
            $matiere->setCodeApogee($mat['code_element']);
            $matiere->setHeures([
                'CM' => ['PN' => (float)$mat['cm_ppn'], 'IUT' => (float)$mat['cm_formation']],
                'TD' => ['PN' => (float)$mat['td_ppn'], 'IUT' => (float)$mat['td_formation']],
                'TP' => ['PN' => (float)$mat['tp_ppn'], 'IUT' => (float)$mat['tp_formation']],
                'Projet' => ['PN' => (float)$mat['projet_ppn'], 'IUT' => (float)$mat['projet_formation']],
            ]);
            $matiere->setType(TypeEnseignementEnum::TYPE_SAE);
            $matiere->setBonification(false);
            $matiere->setDescription($mat['description']);
            $matiere->setNbNotes((int)$mat['nb_notes']);
            $matiere->setLibelleCourt($mat['libelle_court']);
            $matiere->setSuspendu((bool)$mat['suspendu']);
            $matiere->setMutualisee((bool)$mat['mutualisee']);
            $matiere->setExemple($mat['exemple']);
            $matiere->setLivrables($mat['livrables']);
            $matiere->setOldId($mat['id']);

            $nbNotes = (int)$mat['nb_notes'];
            for ($i = 1; $i <= $nbNotes; $i++) {
                $evaluation = new ScolEvaluation();
                $evaluation->setLibelle('Évaluation ' . $i);
                $evaluation->setEnseignement($matiere);
                $evaluation->setVisible(true);
                $evaluation->setModifiable(true);
                $evaluation->setUuid(new UuidV4());
                $this->entityManager->persist($evaluation);
            }

            /*
??
             */
            $this->entityManager->persist($matiere);
            //  $this->tMatieres[$mat['id']] = $matiere;

            // récupérer les dépendances de ApcRessources : ApprentissagesCrtiques, Competences, semestre
            $taddUes = [];
            if (array_key_exists('ues', $mat)) {
                foreach ($mat['ues'] as $apcCompetence) {
                    //dd($apcCompetence);
                    if (array_key_exists($apcCompetence['ue_id'], $this->tUes) &&
                        !array_key_exists($this->tUes[$apcCompetence['ue_id']]->getId(), $taddUes)
                    ) {
                        dump($apcCompetence['ue_id']);
                        $apc = new ScolEnseignementUe(
                            $matiere,
                            $this->tUes[$apcCompetence['ue_id']],
                        );
                        $taddUes[$this->tUes[$apcCompetence['ue_id']]->getId()] = $apc;
                        $apc->setCoefficient((float)$apcCompetence['coefficient']);
                        $apc->setEcts((float)$apcCompetence['coefficient']);
                        //todo: parcours
                        $this->entityManager->persist($apc);
                    }
                }
            }

            $sqlApcCritique = 'SELECT * FROM apc_sae_apprentissage_critique WHERE sae_id = ' . $mat['id'];
            $apcCritiques = $this->em->executeQuery($sqlApcCritique)->fetchAllAssociative();


            foreach ($apcCritiques as $apcCritique) {
                if (array_key_exists($apcCritique['apprentissage_critique_id'], $this->tApprentissages) &&
                    !$matiere->getApprentissageCritique()->contains($this->tApprentissages[$apcCritique['apprentissage_critique_id']])
                ) {
                    $matiere->addApprentissageCritique($this->tApprentissages[$apcCritique['apprentissage_critique_id']]);
                }
            }

            $this->io->info('SAE : ' . $mat['libelle'] . ' ajouté pour insertion');
        }

        $this->entityManager->flush();
    }
}
