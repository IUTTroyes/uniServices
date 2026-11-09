<?php

namespace StageBundle\Controller;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Security\StageAccess;
use StageBundle\Security\StageVoter;
use StageBundle\Service\Notification\StageNotifier;
use StageBundle\Service\StageRapportStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Rapport de stage :
 *  - dépôt / remplacement : l'étudiant (ou le service des stages), une fois la demande validée ;
 *  - téléchargement : toute personne qui peut voir le dossier (étudiant, tuteur, responsables) ;
 *  - retrait : l'étudiant tant que le stage n'est pas noté, ou le service des stages.
 */
#[Route('/api/stage_etudiants/{id}/rapport', requirements: ['id' => '\d+'])]
class StageRapportController extends AbstractController
{
    /** États dans lesquels un rapport peut être déposé (demande validée). */
    private const ETATS_DEPOT = [
        EtatStageEnum::VALIDE,
        EtatStageEnum::CONVENTION_IMPRIMEE,
        EtatStageEnum::CONVENTION_ENVOYEE,
        EtatStageEnum::CONVENTION_RECUE,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StageRapportStorage $storage,
        private readonly StageAccess $access,
        private readonly StageNotifier $notifier,
    ) {
    }

    #[Route('', name: 'api_stage_etudiants_rapport_deposer', methods: ['POST'])]
    public function deposer(int $id, Request $request): JsonResponse
    {
        $stage = $this->stage($id);
        $user = $this->getUser();
        if (!$this->access->estProprietaire($user, $stage) && !$this->access->peutGerer($user, $stage)) {
            throw $this->createAccessDeniedException('Seul l\'étudiant peut déposer son rapport.');
        }
        if (!\in_array($stage->getEtatStage(), self::ETATS_DEPOT, true)) {
            return $this->json(['error' => 'Le rapport peut être déposé une fois la demande de convention validée.'], Response::HTTP_CONFLICT);
        }

        $fichier = $request->files->get('file');
        if (!$fichier instanceof UploadedFile) {
            return $this->json(['error' => 'Aucun fichier reçu (champ « file »).'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $chemin = $this->storage->enregistrer($fichier);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ancien = $stage->getReportPath();
        $stage->deposerRapport($chemin, StageRapportStorage::nettoyerNom($fichier->getClientOriginalName()));
        try {
            $this->em->flush();
        } catch (\Throwable $e) {
            // Dossier non mis à jour : le nouveau fichier ne doit pas rester orphelin sur le disque.
            $this->storage->supprimer($chemin);
            throw $e;
        }
        $this->storage->supprimer($ancien);

        $this->notifier->rapportDepose($stage, $user instanceof Etudiant || $user instanceof Personnel ? $user : null);

        return $this->json($this->etat($stage), Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_stage_etudiants_rapport', methods: ['GET'])]
    public function telecharger(int $id): Response
    {
        $stage = $this->stage($id);
        $this->denyAccessUnlessGranted(StageVoter::STAGE_ETUDIANT_VIEW, $stage);
        $chemin = $stage->getReportPath();
        if ($chemin === null || !$this->storage->existe($chemin)) {
            throw $this->createNotFoundException('Aucun rapport déposé.');
        }

        // Nom imposé (« Rapport-stage_NOM-Prenom_periode.pdf ») : le nom d'origine reste affiché dans l'application.
        $etudiant = $stage->getEtudiant();
        $nom = StageRapportStorage::nomTelechargement($etudiant?->getNom(), $etudiant?->getPrenom(), $stage->getStagePeriode()?->getLibelle());

        $reponse = new BinaryFileResponse($this->storage->cheminAbsolu($chemin));
        $reponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $nom);
        $reponse->headers->set('Content-Type', StageRapportStorage::MIME);
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');
        // Si un navigateur ouvrait quand même le fichier : aucun script, aucun accès à l'application.
        $reponse->headers->set('Content-Security-Policy', "sandbox; default-src 'none'");
        $reponse->headers->set('Cache-Control', 'private, no-cache');

        return $reponse;
    }

    #[Route('', name: 'api_stage_etudiants_rapport_retirer', methods: ['DELETE'])]
    public function retirer(int $id): JsonResponse
    {
        $stage = $this->stage($id);
        $user = $this->getUser();
        $gestion = $this->access->peutGerer($user, $stage);
        if (!$gestion && !$this->access->estProprietaire($user, $stage)) {
            throw $this->createAccessDeniedException();
        }
        if (!$gestion && $stage->getEvaluationNote() !== null) {
            return $this->json(['error' => 'Le stage est noté : le rapport ne peut plus être retiré.'], Response::HTTP_CONFLICT);
        }

        $ancien = $stage->getReportPath();
        $stage->deposerRapport(null, null);
        $this->em->flush();
        $this->storage->supprimer($ancien);

        return $this->json($this->etat($stage));
    }

    private function stage(int $id): StageEtudiant
    {
        return $this->em->getRepository(StageEtudiant::class)->find($id) ?? throw $this->createNotFoundException('Dossier de stage introuvable.');
    }

    /** @return array{reportUploaded: bool, reportName: ?string, reportDate: ?string} */
    private function etat(StageEtudiant $stage): array
    {
        return [
            'reportUploaded' => $stage->getReportUploaded(),
            'reportName' => $stage->getReportName(),
            'reportDate' => $stage->getReportDate()?->format(\DATE_ATOM),
        ];
    }
}
