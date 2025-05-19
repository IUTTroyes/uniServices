<?php

namespace App\Command\CopyBdd;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Apc\ApcParcours;
use App\Entity\Apc\Referentiel;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureTypeDiplome;
use App\Repository\Structure\StructureUeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'copy:transfert-bdd:apc',
    description: 'Add a short description for your command',
)]
class CopyTransfertBddApcCommand extends Command
{
    protected object $em;

    protected SymfonyStyle $io;

    private array $tReferentiels = [];
    private array $tCompetences = [];
    private array $tParcours = [];
    private array $tNiveaux = [];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        ManagerRegistry                  $managerRegistry,
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
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE apc_referentiel');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE apc_competence');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE apc_niveau');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE apc_parcours');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE apc_niveau_apc_parcours');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE apc_apprentissage_critique');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->effacerTables();

        //APC
        $this->addReferentiel();
        $this->addCompetences();
        $this->addParcours();
        $this->addNiveaux();
        $this->addApprentissageCritique();

        $this->io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }

    public function addReferentiel(): void
    {
        $diplomes = $this->entityManager->getRepository(StructureTypeDiplome::class)->findAllByIdArray();
        $departements = $this->entityManager->getRepository(StructureDepartement::class)->findAllByIdArray();


        $sql = "SELECT * FROM apc_referentiel";
        $referentiels = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($referentiels as $ref) {
            $referentiel = new Referentiel();
            $referentiel->setLibelle($ref['libelle']);
            $referentiel->setDescription($ref['description']);
            $referentiel->setAnneePublication((int)$ref['annee_publication']);
            $referentiel->setDepartement($departements[$ref['departement_id']]);
            $referentiel->setTypeDiplome(
                $diplomes[$ref['type_diplome_id']]
            );

            $this->tReferentiels[$ref['id']] = $referentiel;

            $this->entityManager->persist($referentiel);
            $this->io->info('Referentiel ' . $ref['id'] . ' ajouté');
        }

        $this->entityManager->flush();
    }
    public function addParcours(): void
    {
        $sql = "SELECT * FROM apc_parcours";
        $parcours = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($parcours as $par) {
            $parcour = new ApcParcours();
            $parcour->setReferentiel($this->tReferentiels[$par['apc_referentiel_id']]);
            $parcour->setLibelle($par['libelle']);
            $parcour->setOldId($par['id']);
            $parcour->setActif($par['actif']);
            $parcour->setSigle($par['code']);
            $parcour->setCouleur($par['couleur']);
            $parcour->setOpt(
                [
                    'formation_continue' => (bool)$par['formation_continue'],
                ]
            );

            $this->tParcours[$par['id']] = $parcour;

            $this->entityManager->persist($parcour);
            $this->io->info('Parcours ' . $par['id'] . ' ajouté');
        }

        $this->entityManager->flush();
    }
    public function addCompetences(): void
    {
        $sql = "SELECT * FROM apc_competence";
        $competences = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($competences as $comp) {
            $competence = new ApcCompetence();
            $competence->setOldId($comp['id']);
            $competence->setReferentiel($this->tReferentiels[$comp['apc_referentiel_id']]);
            $competence->setLibelle($comp['libelle']);
            $competence->setNomCourt($comp['nom_court']);
            $competence->setCouleur($comp['couleur']);

            // récupérer les composantes essentielles et les ajouter dans le tableau

            $sqlCompEss = "SELECT * FROM apc_composante_essentielle WHERE competence_id = " . $comp['id'];
            $compEss = $this->em->executeQuery($sqlCompEss)->fetchAllAssociative();
            $tCompEss = [];
            foreach ($compEss as $ce) {
                $tCompEss[] = $ce['libelle'];
            }
            $competence->setComposantesEssentielles($tCompEss);

            // récupérer les situations professionnelles et les ajouter dans le tableau

            $sqlSitPro = "SELECT * FROM apc_situation_professionnelle WHERE competence_id = " . $comp['id'];
            $sitPro = $this->em->executeQuery($sqlSitPro)->fetchAllAssociative();
            $tSitPro = [];
            foreach ($sitPro as $sp) {
                $tSitPro[] = $sp['libelle'];
            }
            $competence->setSituationsProfessionnelles($tSitPro);

            $this->tCompetences[$comp['id']] = $competence;

            $this->entityManager->persist($competence);
            $this->io->info('Competence ' . $comp['id'] . ' ajouté');
        }

        $this->entityManager->flush();
    }
    public function addNiveaux(): void
    {
        $sql = "SELECT * FROM apc_niveau";
        $niveaux = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($niveaux as $niv) {
            $niveau = new ApcNiveau();
            $niveau->setCompetence($this->tCompetences[$niv['competence_id']]);
            $niveau->setLibelle($niv['libelle']);
            $niveau->setOrdre($niv['ordre']);

            // récupérer les parcours associés au niveau pour les ajouter dans la collection
            $sqlNivPar = "SELECT * FROM apc_parcours_niveau WHERE niveau_id = " . $niv['id'];
            $nivPar = $this->em->executeQuery($sqlNivPar)->fetchAllAssociative();
            foreach ($nivPar as $np) {
                $niveau->addParcours($this->tParcours[$np['parcours_id']]);
            }

            //todo: récupérer toutes les années sur l'ordre du diplome associé...
            // ajouter toutes les années
            //$niveau->addAnnee();

            $this->tNiveaux[$niv['id']] = $niveau;

            $this->entityManager->persist($niveau);
            $this->io->info('Niveau ' . $niv['id'] . ' ajouté');
        }

        $this->entityManager->flush();
    }

    public function addApprentissageCritique(): void
    {
        $sql = "SELECT * FROM apc_apprentissage_critique";
        $apcs = $this->em->executeQuery($sql)->fetchAllAssociative();

        foreach ($apcs as $ap) {
            $apc = new ApcApprentissageCritique();
            $apc->setApcNiveau($this->tNiveaux[$ap['niveau_id']]);
            $apc->setLibelle($ap['libelle']);
            $apc->setCode($ap['code']);
            $apc->setOldId($ap['id']);

            $this->entityManager->persist($apc);
            $this->io->info('Apprentissage critique ' . $ap['id'] . ' ajouté');
        }

        $this->entityManager->flush();
    }
}
