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
use App\Repository\Structure\StructureDiplomeRepository;
use App\Repository\Structure\StructurePnRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnneeUnivInitProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface                $em,
        private readonly StructureAnneeUniversitaireRepository $anneeUniversitaireRepository,
        private readonly StructurePnRepository                 $structurePnRepository, private readonly StructureDiplomeRepository $structureDiplomeRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof StructureAnneeUniversitaire) {
            return $data;
        }

        // Créer un "pn" par diplôme
        $diplomes = $data->getDiplomes();
        // récupérer les diplomes existants pour cette année universitaire
        $existingDiplomes = $this->structureDiplomeRepository->findByAnneeUniversitaire($data->getId());

        // si existingDiplomes n'est pas dans diplomes alors on supprime son pn
        foreach ($existingDiplomes as $existingDiplome) {
            if (!$diplomes->contains($existingDiplome)) {
                $pn = $this->structurePnRepository->findOneBy(['diplome' => $existingDiplome, 'anneeUniversitaire' => $data]);
                if ($pn) {
                    $this->em->remove($pn);
                }
            }
        }

        foreach ($diplomes as $diplome) {
            $pn = $this->structurePnRepository->findOneBy(['diplome' => $diplome, 'anneeUniversitaire' => $data]);
            if (!$pn) {
                $pn = $this->createPn($diplome, $data);
                $this->em->persist($pn);
                //todo: lancer la synchro de la structure depuis oréof pr chaque diplome
            }
        }

        // si l'année universitaire nouvelle est active, alors on met les autres à inactif
        if ($data->isActif()) {
            $this->anneeUniversitaireRepository->setAllAnneeUnivInactifExcept($data);
        }

        $this->em->flush();

        return $data;
    }

    public function createPn(StructureDiplome $diplome, StructureAnneeUniversitaire $anneeUniversitaire): StructurePn
    {
        $pn = new StructurePn($diplome);
        $pn->setAnneeUniversitaire($anneeUniversitaire);
        $pn->setLibelle('PN ' . $diplome->getDepartement()?->getLibelle() . '-' . $diplome->getLibelle() . '-' . $anneeUniversitaire->getLibelle());
        $pn->setAnneePublication($anneeUniversitaire->getAnnee());

        return $pn;
    }
}
