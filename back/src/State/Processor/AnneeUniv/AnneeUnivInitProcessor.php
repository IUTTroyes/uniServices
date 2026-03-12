<?php

namespace App\State\Processor\AnneeUniv;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Repository\EtudiantNoteRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnneeUnivInitProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StructureAnneeUniversitaireRepository $anneeUniversitaireRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $isAnneeUniv = $data instanceof StructureAnneeUniversitaire;
        $isNew = $isAnneeUniv && (
            $operation instanceof Post ||
            (isset($context['method']) && strtoupper($context['method']) === 'POST') ||
            (isset($context['collection_operation_name']) && strtolower($context['collection_operation_name']) === 'post')
        );

        if ($isNew) {
            $diplomes = $data->getDiplomes();
            foreach ($diplomes as $diplome) {
                // Déléguer la création d'un "pn" par diplôme (implémentation à compléter si nécessaire)
                $pn = $this->createPn($diplome);
                $pn->setAnneeUniversitaire($data);
                $pn->setLibelle('PN '.$diplome->getDepartement()->getLibelle().'-'.$diplome->getLibelle().'-'.$data->getLibelle());
                $pn->setAnneePublication($data->getAnnee());
                $this->em->persist($pn);
                $this->em->flush();

                //todo: lancer la synchro de la structure depuis oréof pr chaque diplome
            }
        }

        // Persist les changements faits à l'année universitaire (Doctrine suivra l'entité)
        $this->em->flush();

        if (!$isAnneeUniv) {
            return $data;
        }

        // si l'année universitaire nouvelle est active, alors on met les autres à inactif
        if ($data->isActif()) {
            $this->anneeUniversitaireRepository->setAllAnneeUnivInactifExcept($data);
        }

        return $data;
    }

    public function createPn(StructureDiplome $diplome) {
        $pn = new StructurePn($diplome);
        $this->em->persist($diplome);
        $this->em->flush();

        return $pn;
    }
}
