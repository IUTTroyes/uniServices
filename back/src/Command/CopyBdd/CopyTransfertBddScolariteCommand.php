<?php

namespace App\Command\CopyBdd;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Repository\EtudiantRepository;
use App\Repository\ScolEnseignementRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureDepartementRepository;
use App\Repository\Structure\StructureGroupeRepository;
use App\Repository\Structure\StructureSemestreRepository;
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
    name: 'copy:transfert-bdd:scolarite',
    description: 'Add a short description for your command',
)]
class CopyTransfertBddScolariteCommand extends Command
{
    protected object $em;

    protected array $tEtudiants = [];
    protected array $tAnneeUniversitaire = [];
    protected array $tSemestres = [];
    protected array $tDepartements = [];
    protected array $tMatieres = [];
    protected array $tUes = [];

    protected array $tGroupes = [];
    protected string $base_url;


    protected SymfonyStyle $io;

    public function __construct(
        protected EntityManagerInterface      $entityManager,
        ManagerRegistry                       $managerRegistry,
        StructureAnneeUniversitaireRepository $structureAnneeUniversitaireRepository,
        StructureSemestreRepository           $structureSemestreRepository,
        EtudiantRepository                    $etudiantRepository,
        protected HttpClientInterface         $httpClient,
        StructureDepartementRepository $structureDepartementRepository,
        StructureGroupeRepository $structureGroupeRepository,
        ScolEnseignementRepository $scolEnseignementRepository,
        StructureUeRepository $structureUeRepository,
        ParameterBagInterface                 $params
    )
    {
        parent::__construct();
        $this->em = $managerRegistry->getConnection('copy');
        $this->tAnneeUniversitaire = $structureAnneeUniversitaireRepository->findAllByOldIdArray();
        $this->tSemestres = $structureSemestreRepository->findAllByOldIdArray();
        $this->tEtudiants = $etudiantRepository->findAllByOldIdArray();
        $this->tDepartements = $structureDepartementRepository->findAllByIdArray();
        $this->tGroupes = $structureGroupeRepository->findAllByOldIdArray();
        $this->tMatieres = $scolEnseignementRepository->findAllByOldIdArray();
        $this->tUes = $structureUeRepository->findAllByOldIdArray();
        $this->base_url = $params->get('URL_INTRANET_V3');
        $this->httpClient = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
        ]);

    }

    protected function configure(): void
    {
    }

    private function effacerTables(): void
    {
        // vider les tables de destination et les réinitialiser
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=0');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE etudiant_scolarite');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=0');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE etudiant_scolarite_semestre');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE etudiant_scolarite_structure_annee');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
//        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE etudiant_scolarite_structure_groupe');
//        $this->entityManager->getConnection()->executeQuery('SET
//FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->effacerTables();
        $this->addEtudiantScolarite();

        $this->io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }

    private function addEtudiantScolarite(): void
    {
        ini_set('memory_limit', '2G');
        $sql = 'SELECT * FROM etudiant WHERE semestre_id IS NOT NULL and annee_sortie = 0';
        $etudiants = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($etudiants as $etu) {
            $response = $this->httpClient->request('GET', $this->base_url . '/etudiant/' . $etu['id']);
            $scolarites = json_decode($response->getContent(), true);

            if ($scolarites && isset($this->tEtudiants[$etu['id']])) {
                foreach ($scolarites as $scol) {
                    if (!array_key_exists($scol['annee'], $this->tAnneeUniversitaire)) {
                        continue;
                    }

                    $scolarite = new EtudiantScolarite();
                    $scolarite->setUuid(UuidV4::v4());
                    $scolarite->setEtudiant($this->tEtudiants[$etu['id']]);
                    $scolarite->setAnneeUniversitaire($this->tAnneeUniversitaire[$scol['annee']]);

                    // Définir les propriétés globales de la scolarité
                    $scolarite->setMoyenne(isset($scol['bilan']['moyenne']) ? round($scol['bilan']['moyenne'], 2) : 0);
                    $scolarite->setNbAbsences($scol['bilan']['nbAbsences'] ?? 0);

                    // Process moyennesMatieres with correct IDs
                    $moyennesMatieres = [];
                    if (isset($scol['bilan']['moyennesMatieres']) && is_array($scol['bilan']['moyennesMatieres'])) {
                        foreach ($scol['bilan']['moyennesMatieres'] as $oldId => $moyenneMatiere) {
                            // Remove "matiere_" prefix if present
                            $cleanOldId = str_replace('matiere_', '', $oldId);

                            // If it's a numeric ID, try to find the corresponding matiere
                            if (is_numeric($cleanOldId) && isset($this->tMatieres[$cleanOldId])) {
                                $newId = $this->tMatieres[$cleanOldId]->getId();
                                $moyennesMatieres[$newId] = $moyenneMatiere;
                            } else {
                                // Keep the original ID if we can't find a mapping
                                $moyennesMatieres[$oldId] = $moyenneMatiere;
                            }
                        }
                    }
                    $scolarite->setMoyennesMatiere($moyennesMatieres);

                    // Process moyennesUes with correct IDs
                    $moyennesUes = [];
                    if (isset($scol['bilan']['moyennesUes']) && is_array($scol['bilan']['moyennesUes'])) {
                        foreach ($scol['bilan']['moyennesUes'] as $oldId => $moyenneUe) {
                            // Round moyenne to 2 decimal places
                            if (isset($moyenneUe['moyenne'])) {
                                $moyenneUe['moyenne'] = round($moyenneUe['moyenne'], 2);

                                // If decision is empty, calculate it based on the moyenne
                                if (!isset($moyenneUe['decision']) || $moyenneUe['decision'] === null) {
                                    $moyenneUe['decision'] = $moyenneUe['moyenne'] >= 10 ? 'V' : 'NV';
                                }
                            }

                            // If it's a numeric ID, try to find the corresponding UE
                            if (is_numeric($oldId) && isset($this->tUes[$oldId])) {
                                $newId = $this->tUes[$oldId]->getId();
                                $moyennesUes[$newId] = $moyenneUe;
                            } else {
                                // Keep the original ID if we can't find a mapping
                                $moyennesUes[$oldId] = $moyenneUe;
                            }
                        }
                    }
                    $scolarite->setMoyennesUe($moyennesUes);
                    $scolarite->setCommentaire($scol['bilan']['commentaire'] ?? '');

                    // Set decision from bilan data (convert string to boolean if needed)
                    $decision = $scol['bilan']['decision'] ?? null;
                    if ($decision === 'V') {
                        $scolarite->setDecision(true);
                    } elseif ($decision === null) {
                        $scolarite->setDecision(null);
                    } else {
                        $scolarite->setDecision(false);
                    }

                    // Set proposition if available in the last semester
                    if (!empty($scol['semestres'])) {
                        $lastSemester = end($scol['semestres']);
                        if (isset($lastSemester['proposition']) && $lastSemester['proposition'] !== null) {
                            // If the proposition is for the next year (like "DUT"), find the appropriate year
                            foreach ($this->tAnneeUniversitaire as $annee) {
                                if ($annee->getLibelle() === $lastSemester['proposition']) {
                                    $scolarite->setProposition($annee->getAnnee());
                                    break;
                                }
                            }
                        }
                    }

                    $scolarite->setOrdre($scol['ordre'] ?? count($scolarites));

                    foreach ($this->tDepartements as $departement) {
                        if ($departement->getOldId() === $etu['departement_id']) {
                            $scolarite->setDepartement($departement);
                            break;
                        }
                    }

                    // Ajouter les semestres
                    foreach ($scol['semestres'] as $semestre) {
                        foreach ($this->tSemestres as $semestreDest) {
                            if ($semestreDest->getOldId() === $semestre['id']) {
                                $annee = $semestreDest->getAnnee();
                                $scolarite->addAnnee($annee);

                                $etudiantScolSemestre = new EtudiantScolariteSemestre();
                                $etudiantScolSemestre->setScolarite($scolarite);
                                $etudiantScolSemestre->setSemestre($semestreDest);

                                // Set decision from semestre data (convert string to boolean if needed)
                                $decision = $semestre['decision'] ?? null;
                                if ($decision === 'V') {
                                    $etudiantScolSemestre->setDecision(true);
                                } elseif ($decision === null) {
                                    $etudiantScolSemestre->setDecision(null);
                                } else {
                                    $etudiantScolSemestre->setDecision(false);
                                }

                                // Set proposition if available
                                if (isset($semestre['proposition']) && $semestre['proposition'] !== null) {
                                    // Find the appropriate semester for the proposition
                                    foreach ($this->tSemestres as $propositionSemestre) {
                                        if ($propositionSemestre->getLibelle() === $semestre['proposition']) {
                                            $etudiantScolSemestre->setProposition($propositionSemestre);
                                            break;
                                        }
                                    }
                                }

                                // Set moyenne from semestre data
                                $etudiantScolSemestre->setMoyenne(isset($semestre['moyenne']) ? round($semestre['moyenne'], 2) : 0);

                                // Set moyennesMatieres and moyennesUes from semestre data
                                // Process moyennesMatieres with correct IDs
                                $moyennesMatieres = [];
                                if (isset($semestre['moyennesMatieres']) && is_array($semestre['moyennesMatieres'])) {
                                    foreach ($semestre['moyennesMatieres'] as $oldId => $moyenneMatiere) {
                                        // Remove "matiere_" prefix if present
                                        $cleanOldId = str_replace('matiere_', '', $oldId);

                                        // If it's a numeric ID, try to find the corresponding matiere
                                        if (is_numeric($cleanOldId) && isset($this->tMatieres[$cleanOldId])) {
                                            $newId = $this->tMatieres[$cleanOldId]->getId();
                                            $moyennesMatieres[$newId] = $moyenneMatiere;
                                        } else {
                                            // Keep the original ID if we can't find a mapping
                                            $moyennesMatieres[$oldId] = $moyenneMatiere;
                                        }
                                    }
                                }
                                $etudiantScolSemestre->setMoyennesMatiere($moyennesMatieres);

                                // Process moyennesUes with correct IDs
                                $moyennesUes = [];
                                if (isset($semestre['moyennesUes']) && is_array($semestre['moyennesUes'])) {
                                    foreach ($semestre['moyennesUes'] as $oldId => $moyenneUe) {
                                        // Round moyenne to 2 decimal places
                                        if (isset($moyenneUe['moyenne'])) {
                                            $moyenneUe['moyenne'] = round($moyenneUe['moyenne'], 2);

                                            // If decision is empty, calculate it based on the moyenne
                                            if (!isset($moyenneUe['decision']) || $moyenneUe['decision'] === null) {
                                                $moyenneUe['decision'] = $moyenneUe['moyenne'] >= 10 ? 'V' : 'NV';
                                            }
                                        }

                                        // If it's a numeric ID, try to find the corresponding UE
                                        if (is_numeric($oldId) && isset($this->tUes[$oldId])) {
                                            $newId = $this->tUes[$oldId]->getId();
                                            $moyennesUes[$newId] = $moyenneUe;
                                        } else {
                                            // Keep the original ID if we can't find a mapping
                                            $moyennesUes[$oldId] = $moyenneUe;
                                        }
                                    }
                                }
                                $etudiantScolSemestre->setMoyennesUe($moyennesUes);

                                $this->entityManager->persist($etudiantScolSemestre);
                            }
                        }
                    }

                    $this->entityManager->persist($scolarite);
                }
            }
        }
        $this->entityManager->flush();
    }
}
