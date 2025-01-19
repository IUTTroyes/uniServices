<?php

namespace App\Command\CopyBdd;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Repository\EtudiantRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureDepartementRepository;
use App\Repository\Structure\StructureGroupeRepository;
use App\Repository\Structure\StructureSemestreRepository;
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
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE etudiant_scolarite_structure_groupe');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
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
        $sql = 'SELECT * FROM etudiant WHERE semestre_id IS NOT NULL and annee_sortie = 0';
        $etudiants = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($etudiants as $etu) {
            $response = $this->httpClient->request('GET', $this->base_url . '/etudiant/' . $etu['id']);
            $scols = json_decode($response->getContent(), true);

            foreach ($scols as $scol) {

                if (!array_key_exists($scol['annee'], $this->tAnneeUniversitaire)) {
                    continue;
                }

                $scolarite = new EtudiantScolarite();
                $scolarite->setUuid(UuidV4::v4());
                $scolarite->setEtudiant($this->tEtudiants[$etu['id']]);
                $scolarite->setStructureAnneeUniversitaire($this->tAnneeUniversitaire[$scol['annee']]);

                $scolarite->setOrdre($scol['ordre'] ?? 0);
                $scolarite->setProposition($scol['proposition']);
                $scolarite->setMoyenne($scol['moyenne']);
                $scolarite->setNbAbsences($scol['nbAbsences']);
                $scolarite->setCommentaire($scol['commentaire']);
                $scolarite->setPublic($scol['diffuse']);
                $scolarite->setMoyennesMatiere($scol['moyennesMatieres']);
                $scolarite->setMoyennesUe($scol['moyennesUes']);

                foreach ($this->tDepartements as $departement) {
                    if ($departement->getOldId() === $etu['departement_id']) {
                        $scolarite->setDepartement($departement);
                        break;
                    }
                }

                if ($scol['groupes']) {
                    foreach ($scol['groupes'] as $groupe) {
                        foreach ($this->tGroupes as $groupeDest) {
                            if ($groupeDest->getOldId() === $groupe) {
                                $scolarite->addGroupe($groupeDest);
                            }
                        }
                    }
                }

                // Create EtudiantScolariteSemestre
                foreach ($this->tSemestres as $semestre) {
                    if ($semestre->getOldId() === $scol['semestre']) {
                        $etudiantScolariteSemestre = new EtudiantScolariteSemestre();
                        $etudiantScolariteSemestre->setEtudiantScolarite($scolarite);
                        $etudiantScolariteSemestre->setStructureSemestre($semestre);
                        $this->entityManager->persist($etudiantScolariteSemestre);
                    }
                }

                $this->entityManager->persist($scolarite);
            }
        }
        $this->entityManager->flush();
    }
}
