<?php
/*
 * Copyright (c) 2021. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/htdocs/intranetV3/src/Classes/DocumentDelete.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 29/09/2021 09:11
 */

namespace App\Classes;

use App\Entity\Document;
use App\Repository\DocumentFavoriEtudiantRepository;
use App\Repository\DocumentFavoriPersonnelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class DocumentDelete
{
    public function __construct(protected KernelInterface $kernel, protected DocumentFavoriEtudiantRepository $documentFavoriEtudiantRepository, protected DocumentFavoriPersonnelRepository $documentFavoriPersonnelRepository, protected EntityManagerInterface $entityManager)
    {
    }

    public function deleteDocument(Document $document): bool
    {
        $docs = $this->documentFavoriEtudiantRepository->findBy(['document' => $document->getId()]);
        foreach ($docs as $doc) {
            $this->entityManager->remove($doc);
        }

        $docs = $this->documentFavoriPersonnelRepository->findBy(['document' => $document->getId()]);
        foreach ($docs as $doc) {
            $this->entityManager->remove($doc);
        }
        $file = $this->kernel->getProjectDir().'/public/upload/documents/'.$document->getDocumentName();
        if (file_exists($file) && !is_dir($file)) {
            unlink($file);
        }
        $this->entityManager->remove($document);
        $this->entityManager->flush();

        return true;
    }
}
