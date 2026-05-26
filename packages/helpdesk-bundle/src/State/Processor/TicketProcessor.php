<?php

namespace HelpdeskBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use HelpdeskBundle\Entity\HelpdeskCategorie;
use HelpdeskBundle\Entity\HelpdeskTicket;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Users\Personnel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class TicketProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger,
        #[Autowire('%env(string:HELPDESK_TICKETS_UPLOAD_DIR)%')]
        private readonly string $uploadsDirectory,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $request = $context['request'] ?? null;

        if ($request === null) {
            return $data;
        }

        // 👇 Désérialisation manuelle depuis la requête multipart
        $ticket = new HelpdeskTicket();

        $subject      = $request->request->get('subject');
        $description  = $request->request->get('description');
        $categoriePath = $request->request->get('helpdeskCategorie'); // ex: /api/helpdesk_categories/5
        $auteurPath   = $request->request->get('auteur');             // ex: /api/personnels/3

        $ticket->setSubject($subject);
        $ticket->setDescription($description);

        // Résolution de la catégorie
        if ($categoriePath) {
            $categorieId = basename($categoriePath);
            $categorie = $this->em->getRepository(HelpdeskCategorie::class)->find($categorieId);
            if ($categorie) {
                $ticket->setHelpdeskCategorie($categorie);
            }
        }

        // Résolution de l'auteur
        if ($auteurPath) {
            $auteurId = basename($auteurPath);
            $auteur = $this->em->getRepository(Personnel::class)->find($auteurId);
            if ($auteur) {
                $ticket->setAuteur($auteur);
            }
        }

        // 👇 Gestion des fichiers uploadés
        $savedFiles = [];
        $uploadedFiles = $request->files->all('files') ?? [];

        // Gestion du cas où files[] est envoyé comme tableau
        if (empty($uploadedFiles)) {
            $allFiles = $request->files->all();
            if (!empty($allFiles)) {
                $firstGroup = array_values($allFiles)[0];
                $uploadedFiles = is_array($firstGroup) ? $firstGroup : [$firstGroup];
            }
        }

        if (!empty($uploadedFiles)) {
            if (!is_dir($this->uploadsDirectory) && !@mkdir($this->uploadsDirectory, 0775, true) && !is_dir($this->uploadsDirectory)) {
                throw new \RuntimeException('Impossible de créer le dossier d\'upload.');
            }

            foreach ($uploadedFiles as $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile) {
                    continue;
                }

                // Validation de l'extension
                $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
                $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension();

                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    throw new \RuntimeException(
                        sprintf('Le fichier "%s" n\'est pas dans un format autorisé.', $uploadedFile->getClientOriginalName())
                    );
                }

                // Validation de la taille (15Mo max)
                if ($uploadedFile->getSize() > 15 * 1024 * 1024) {
                    throw new \RuntimeException('Un fichier dépasse la taille maximale autorisée (15Mo).');
                }

                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = (string) $this->slugger->slug($originalFilename)->lower();
                $newFilename      = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $extension);

                $uploadedFile->move($this->uploadsDirectory, $newFilename);
                $savedFiles[] = $newFilename;
            }
        }

        $ticket->setFilesNames($savedFiles ?: null);

        $this->em->persist($ticket);
        $this->em->flush();

        return $ticket;
    }
}
