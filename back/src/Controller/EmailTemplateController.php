<?php

namespace App\Controller;

use App\Entity\Email\EmailTemplate;
use App\Entity\Structure\StructureDepartement;
use App\Repository\Email\EmailTemplateRepository;
use App\Repository\Structure\StructureDepartementRepository;
use App\Services\Email\EmailRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API pour la gestion des templates d'emails (personnalisation par département).
 *
 * Toutes les routes sont protégées par ROLE_ADMIN ou ROLE_CHEF_DEPARTEMENT
 * (vérifié dans le security voter).
 */
#[Route('/api/email', name: 'api_email_')]
class EmailTemplateController extends AbstractController
{
    public function __construct(
        private readonly EmailRegistry $registry,
        private readonly EmailTemplateRepository $templateRepository,
        private readonly StructureDepartementRepository $departementRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Liste toutes les définitions d'emails connues (depuis le EmailRegistry).
     * Indique pour chaque définition si une personnalisation département existe.
     *
     * GET /api/email/definitions[?departement={id}]
     */
    #[Route('/definitions', name: 'definitions', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function definitions(Request $request): JsonResponse
    {
        $departementId = $request->query->getInt('departement', 0);
        $departement = $departementId > 0
            ? $this->departementRepository->find($departementId)
            : null;

        // Clés déjà personnalisées pour ce département
        $customizedKeys = $departement
            ? $this->templateRepository->findCustomizedKeysByDepartement($departement)
            : [];

        $result = [];
        foreach ($this->registry->groupedByBundle() as $bundle => $definitions) {
            $bundleItems = [];
            foreach ($definitions as $def) {
                $bundleItems[] = [
                    'key'                 => $def->getKey(),
                    'label'               => $def->getLabel(),
                    'description'         => $def->getDescription(),
                    'defaultSubject'      => $def->getDefaultSubject(),
                    'availableVariables'  => $def->getAvailableVariables(),
                    'isCustomized'        => in_array($def->getKey(), $customizedKeys, true),
                ];
            }
            $result[] = [
                'bundle' => $bundle,
                'items'  => $bundleItems,
            ];
        }

        return $this->json($result);
    }

    /**
     * Récupère la personnalisation d'un email pour un département donné.
     * Si aucune personnalisation n'existe, retourne les valeurs par défaut du template.
     *
     * GET /api/email/templates/{key}[?departement={id}]
     */
    #[Route('/templates/{key}', name: 'template_get', methods: ['GET'], requirements: ['key' => '.+'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function getTemplate(string $key, Request $request): JsonResponse
    {
        $definition = $this->registry->get($key);
        if ($definition === null) {
            return $this->json(['error' => 'Clé d\'email inconnue : ' . $key], Response::HTTP_NOT_FOUND);
        }

        $departementId = $request->query->getInt('departement', 0);
        $departement = $departementId > 0
            ? $this->departementRepository->find($departementId)
            : null;

        // Cherche une personnalisation existante
        $template = $departement
            ? $this->templateRepository->findByKeyAndDepartement($key, $departement)
            : $this->templateRepository->findGlobal($key);

        if ($template !== null) {
            return $this->json([
                'id'                 => $template->getId(),
                'emailKey'           => $template->getEmailKey(),
                'departement'        => $departement?->getId(),
                'subject'            => $template->getSubject(),
                'bodyHtml'           => $template->getBodyHtml(),
                'isCustomized'       => true,
                'availableVariables' => $definition->getAvailableVariables(),
            ]);
        }

        // Pas de personnalisation : retourne les valeurs par défaut
        return $this->json([
            'id'                 => null,
            'emailKey'           => $key,
            'departement'        => $departement?->getId(),
            'subject'            => $definition->getDefaultSubject(),
            'bodyHtml'           => null,
            'isCustomized'       => false,
            'availableVariables' => $definition->getAvailableVariables(),
        ]);
    }

    /**
     * Crée ou met à jour la personnalisation d'un email pour un département.
     *
     * POST /api/email/templates
     * Body JSON : { emailKey, departement (id|null), subject, bodyHtml }
     */
    #[Route('/templates', name: 'template_save', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function saveTemplate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['emailKey']) || empty($data['subject']) || empty($data['bodyHtml'])) {
            return $this->json(['error' => 'Champs requis : emailKey, subject, bodyHtml'], Response::HTTP_BAD_REQUEST);
        }

        $key = $data['emailKey'];

        if ($this->registry->get($key) === null) {
            return $this->json(['error' => 'Clé d\'email inconnue : ' . $key], Response::HTTP_NOT_FOUND);
        }

        // Résout le département
        $departement = null;
        if (!empty($data['departement'])) {
            $departement = $this->departementRepository->find($data['departement']);
            if ($departement === null) {
                return $this->json(['error' => 'Département introuvable'], Response::HTTP_NOT_FOUND);
            }
        }

        // Cherche s'il existe déjà
        $template = $departement
            ? $this->templateRepository->findByKeyAndDepartement($key, $departement)
            : $this->templateRepository->findGlobal($key);

        if ($template === null) {
            $template = new EmailTemplate($key, $data['subject'], $data['bodyHtml']);
            $template->setDepartement($departement);
            $this->em->persist($template);
        } else {
            $template->setSubject($data['subject']);
            $template->setBodyHtml($data['bodyHtml']);
        }

        $this->em->flush();

        return $this->json([
            'id'         => $template->getId(),
            'emailKey'   => $template->getEmailKey(),
            'departement'=> $departement?->getId(),
            'subject'    => $template->getSubject(),
            'bodyHtml'   => $template->getBodyHtml(),
        ], Response::HTTP_OK);
    }

    /**
     * Supprime une personnalisation (revient au template par défaut du package).
     *
     * DELETE /api/email/templates/{id}
     */
    #[Route('/templates/{id}', name: 'template_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function deleteTemplate(int $id): JsonResponse
    {
        $template = $this->templateRepository->find($id);
        if ($template === null) {
            return $this->json(['error' => 'Template introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($template);
        $this->em->flush();

        return $this->json(['deleted' => true], Response::HTTP_OK);
    }

    /**
     * Retourne la liste des départements (utilisé par le sélecteur de département dans l'UI).
     *
     * GET /api/email/departements
     */
    #[Route('/departements', name: 'departements', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function departements(): JsonResponse
    {
        $departements = $this->departementRepository->findBy(['actif' => true], ['libelle' => 'ASC']);

        return $this->json(
            array_map(fn (StructureDepartement $d) => [
                'id'      => $d->getId(),
                'libelle' => $d->getLibelle(),
            ], $departements)
        );
    }
}
