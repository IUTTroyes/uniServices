<?php

namespace App\Command\CopyBdd;

use App\Entity\Previsionnel\Previsionnel;
use App\Repository\PersonnelRepository;
use App\Repository\ScolEnseignementRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'copy:transfert-bdd:previsionnel',
    description: 'Copie des previ',
)]
class CopyTransfertBddPrevisionnelCommand extends Command
{
    protected object $em;

    protected array $tPersonnels = [];
    protected array $tAnneeUniversitaire = [];
    protected array $tEnseignements = [];

    protected SymfonyStyle $io;
    protected string $base_url;


    public function __construct(
        protected EntityManagerInterface   $entityManager,
        ManagerRegistry                    $managerRegistry,
        protected HttpClientInterface      $httpClient,
        ParameterBagInterface              $params,
        PersonnelRepository                $personnelRepository,
        ScolEnseignementRepository         $scolEnseignementRepository,
        StructureAnneeUniversitaireRepository $structureAnneeUniversitaireRepository
    )
    {
        parent::__construct();
        $this->tPersonnels = $personnelRepository->findAllByOldIdArray();
        $this->tAnneeUniversitaire = $structureAnneeUniversitaireRepository->findAllByOldIdArray();
        $this->base_url = $params->get('URL_INTRANET_V3');
        $this->httpClient = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
        ]);
        $this->em = $managerRegistry->getConnection('copy');
        $this->scolEnseignementRepository = $scolEnseignementRepository;
    }

    protected function configure(): void
    {
    }

    private function effacerTables(): void
    {
        // vider les tables de destination et les réinitialiser
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=0');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE previsionnel');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->effacerTables();

        $this->addPrevisEnseignement();
        $this->addPrevisRessource();
        $this->addPrevisSae();

        $this->io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }

    private function fetchAllPages(string $url): array
    {
        $page = 1;
        $limit = 50;
        $allData = [];

        do {
            $response = $this->httpClient->request('GET', $url, [
                'query' => [
                    'page' => $page,
                    'limit' => $limit,
                ],
                'timeout' => 600,
            ]);
            $data = json_decode($response->getContent(), true);
            $allData = array_merge($allData, $data);
            $page++;
        } while (count($data) === $limit);

        return $allData;
    }

    private function addPrevisEnseignement(): int
    {
        $previs = $this->fetchAllPages($this->base_url . '/previsionnels/matiere');

        foreach ($previs as $previ) {
            $enseignement = $this->scolEnseignementRepository->findOneBy(['oldId' => $previ['matiere']['id'], 'type' => $previ['matiere']['type']]);

            $previsionnel = new Previsionnel();
            if (array_key_exists($previ['personnel'], $this->tPersonnels)) {
                $previsionnel->setPersonnel($this->tPersonnels[$previ['personnel']]);
            }
            if (array_key_exists($previ['annee'], $this->tAnneeUniversitaire)) {
                $previsionnel->setAnneeUniversitaire($this->tAnneeUniversitaire[$previ['annee']]);
            }
            $previsionnel->setEnseignement($enseignement);
            $previsionnel->setReferent($previ['referent']);
            $previsionnel->setHeures([
                'CM' => $previ['nbHCm'],
                'TD' => $previ['nbHTd'],
                'TP' => $previ['nbHTp'],
                'Projet' => 0,
            ]);
            $previsionnel->setGroupes([
                'CM' => $previ['nbGrCm'],
                'TD' => $previ['nbGrTd'],
                'TP' => $previ['nbGrTp'],
                'Projet' => 0,
            ]);

            $this->entityManager->persist($previsionnel);
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function addPrevisRessource(): int
    {
        $previs = $this->fetchAllPages($this->base_url . '/previsionnels/ressource');

        foreach ($previs as $previ) {
            $enseignement = $this->scolEnseignementRepository->findOneBy(['oldId' => $previ['matiere']['id'], 'type' => $previ['matiere']['type']]);

            $previsionnel = new Previsionnel();
            if (array_key_exists($previ['personnel'], $this->tPersonnels)) {
                $previsionnel->setPersonnel($this->tPersonnels[$previ['personnel']]);
            }
            if (array_key_exists($previ['annee'], $this->tAnneeUniversitaire)) {
                $previsionnel->setAnneeUniversitaire($this->tAnneeUniversitaire[$previ['annee']]);
            }
            $previsionnel->setEnseignement($enseignement);
            $previsionnel->setReferent($previ['referent']);
            $previsionnel->setHeures([
                'CM' => $previ['nbHCm'],
                'TD' => $previ['nbHTd'],
                'TP' => $previ['nbHTp'],
                'Projet' => 0,
            ]);
            $previsionnel->setGroupes([
                'CM' => $previ['nbGrCm'],
                'TD' => $previ['nbGrTd'],
                'TP' => $previ['nbGrTp'],
                'Projet' => 0,
            ]);

            $this->entityManager->persist($previsionnel);
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function addPrevisSae(): int
    {
        $previs = $this->fetchAllPages($this->base_url . '/previsionnels/sae');

        foreach ($previs as $previ) {
            $enseignement = $this->scolEnseignementRepository->findOneBy(['oldId' => $previ['matiere']['id'], 'type' => $previ['matiere']['type']]);

            $previsionnel = new Previsionnel();
            if (array_key_exists($previ['personnel'], $this->tPersonnels)) {
                $previsionnel->setPersonnel($this->tPersonnels[$previ['personnel']]);
            }
            if (array_key_exists($previ['annee'], $this->tAnneeUniversitaire)) {
                $previsionnel->setAnneeUniversitaire($this->tAnneeUniversitaire[$previ['annee']]);
            }
            $previsionnel->setEnseignement($enseignement);
            $previsionnel->setReferent($previ['referent']);
            $previsionnel->setHeures([
                'CM' => $previ['nbHCm'],
                'TD' => $previ['nbHTd'],
                'TP' => $previ['nbHTp'],
                'Projet' => 0,
            ]);
            $previsionnel->setGroupes([
                'CM' => $previ['nbGrCm'],
                'TD' => $previ['nbGrTd'],
                'TP' => $previ['nbGrTp'],
                'Projet' => 0,
            ]);

            $this->entityManager->persist($previsionnel);
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
