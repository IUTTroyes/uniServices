<?php

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Etablissement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EtablissementProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%env(string:ETABLISSEMENT_LOGO_UPLOAD_DIR)%')]
        private readonly string $uploadsDirectory,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $request = $context['request'] ?? null;
        $isLogoUploadOperation = $request && str_ends_with((string) $request->getPathInfo(), '/logo');

        if ($isLogoUploadOperation) {
            $entity = $data instanceof Etablissement ? $data : ($context['previous_data'] ?? null);
            if (!$entity instanceof Etablissement) {
                throw new \RuntimeException('Établissement introuvable pour l\'upload du logo.');
            }

            $file = $request->files->get('file');
            if (!$file instanceof UploadedFile) {
                $file = $request->files->get('logo');
            }
            if (!$file instanceof UploadedFile) {
                $uploadedFiles = $request->files->all();
                if (!empty($uploadedFiles)) {
                    $firstUploaded = array_values($uploadedFiles)[0];
                    $file = is_array($firstUploaded) ? (array_values($firstUploaded)[0] ?? null) : $firstUploaded;
                }
            }

            if (!$file) {
                return $entity;
            }

            if (!in_array($file->guessExtension(), ['png', 'jpg', 'jpeg'])) {
                throw new \RuntimeException('Le fichier doit être au format PNG ou JPG.');
            }

            if ($file->getSize() > 15 * 1024 * 1024) {
                throw new \RuntimeException('Le fichier doit être inférieur à 15Mo.');
            }

            // renommer le fichier "logo_etablissement.extension"
            $fileName = sprintf('logo_etablissement_%s.%s', uniqid('', true), $file->guessExtension());

            if (!is_dir($this->uploadsDirectory) && !@mkdir($this->uploadsDirectory, 0775, true) && !is_dir($this->uploadsDirectory)) {
                throw new \RuntimeException('Impossible de créer le dossier d\'upload.');
            }

            $fileName = sprintf('%s_%s.%s', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), uniqid('', true), $file->guessExtension());
            $file->move($this->uploadsDirectory, $fileName);
            $entity->setLogoName($fileName);

            $this->em->persist($entity);
            $this->em->flush();

            return $entity;
        }

        if (!$data instanceof Etablissement) {
            return $data;
        }

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
