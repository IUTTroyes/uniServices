<?php

namespace App\State\Processor\AnneeUniv;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Structure\{StructureAnneeUniversitaire, StructureDiplome, StructurePn};
use App\Repository\Structure\{StructureAnneeUniversitaireRepository, StructureDiplomeRepository, StructurePnRepository};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AnneeUnivInitProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StructureAnneeUniversitaireRepository $anneeUniversitaireRepository,
        private readonly StructurePnRepository $structurePnRepository,
        private readonly StructureDiplomeRepository $structureDiplomeRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof StructureAnneeUniversitaire) return $data;

        // Une modification des seules métadonnées de l'année ne doit jamais retirer ses PN.
        $ids = $data->getDiplomeIds();
        $diplomes = null;
        if ($ids !== null) {
            foreach ($ids as $id) {
                if (!is_int($id) || $id < 1) throw new BadRequestHttpException('Identifiant de diplôme invalide.');
            }
            $ids = array_values(array_unique($ids));
            $diplomes = $ids === [] ? [] : $this->structureDiplomeRepository->findBy(['id' => $ids]);
            if (count($diplomes) !== count($ids)) throw new BadRequestHttpException('Un diplôme sélectionné est introuvable.');
        }

        return $this->em->wrapInTransaction(function () use ($data, $ids, $diplomes) {
            if ($diplomes !== null) {
                $existants = $data->getId() === null ? [] : $this->structureDiplomeRepository->findByAnneeUniversitaire($data->getId());
                foreach ($existants as $existant) {
                    if (in_array($existant->getId(), $ids, true)) continue;
                    foreach ($this->structurePnRepository->findBy(['diplome' => $existant, 'anneeUniversitaire' => $data]) as $pn) {
                        $data->getPns()->removeElement($pn);
                        $this->em->remove($pn);
                    }
                }
                foreach ($diplomes as $diplome) {
                    $pn = $data->getId() === null ? null : $this->structurePnRepository->findOneBy(['diplome' => $diplome, 'anneeUniversitaire' => $data]);
                    if ($pn === null) {
                        $pn = $this->createPn($diplome, $data);
                        $data->addPn($pn);
                        $this->em->persist($pn);
                    }
                }
            }
            if ($data->isActif()) $this->anneeUniversitaireRepository->setAllAnneeUnivInactifExcept($data);
            $this->em->persist($data);
            $this->em->flush();
            $data->setDiplomeIds(null);
            return $data;
        });
    }

    public function createPn(StructureDiplome $diplome, StructureAnneeUniversitaire $anneeUniversitaire): StructurePn
    {
        $pn = new StructurePn($diplome);
        $pn->setAnneeUniversitaire($anneeUniversitaire);
        $pn->setLibelle('PN '.$diplome->getDepartement()?->getLibelle().'-'.$diplome->getLibelle().'-'.$anneeUniversitaire->getLibelle());
        $pn->setAnneePublication($anneeUniversitaire->getAnnee());
        return $pn;
    }
}
