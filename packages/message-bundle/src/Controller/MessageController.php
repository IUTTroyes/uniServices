<?php

namespace MessageBundle\Controller;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Repository\EtudiantRepository;
use App\Repository\PersonnelRepository;
use App\Repository\Structure\StructureGroupeRepository;
use App\Repository\Structure\StructureSemestreRepository;
use App\Security\UserEffectivePermissionService;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use MessageBundle\Entity\Message;
use MessageBundle\Entity\MessageDestinataire;
use MessageBundle\Entity\MessagePieceJointe;
use MessageBundle\Repository\MessageDestinataireRepository;
use MessageBundle\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/messages')]
class MessageController extends AbstractController
{
    private const PERMANENTS_STATUTS = ['MCF', 'PU', 'PRCE', 'PRAG', 'CONTRACTUEL', 'CONTRAC', 'PRCACDD', 'CDD'];
    private const VACATAIRES_STATUTS = ['VAC', 'VACATAIRE', 'ATER', 'ENSAM', 'DO', 'PRO', 'PROFESSION', 'PR'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MessageRepository $messageRepository,
        private readonly MessageDestinataireRepository $destinataireRepository,
        private readonly EtudiantRepository $etudiantRepository,
        private readonly PersonnelRepository $personnelRepository,
        private readonly StructureGroupeRepository $groupeRepository,
        private readonly StructureSemestreRepository $semestreRepository,
        private readonly UserEffectivePermissionService $permissions,
        private readonly ?EmailService $emailService = null,
    ) {
    }

    /**
     * Boîte de réception de l'utilisateur connecté (Étudiant ou Personnel).
     */
    #[Route('', name: 'api_messages_inbox', methods: ['GET'])]
    public function getInbox(Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search', '');
        $limit = max(1, min(100, (int) $request->query->get('limit', 50)));
        $offset = max(0, (int) $request->query->get('offset', 0));

        $destinataires = $this->destinataireRepository->findForUser(
            $user,
            ['status' => $status, 'search' => $search],
            $limit,
            $offset
        );

        $data = array_map(function (MessageDestinataire $d) {
            $msg = $d->getMessage();
            $exp = $msg?->getExpediteur();

            return [
                'destinataireId' => $d->getId(),
                'id' => $msg?->getId(),
                'sujet' => $msg?->getSujet(),
                'contenu' => $msg?->getContenu(),
                'prioritaire' => $msg?->isPrioritaire() ?? false,
                'copieEmail' => $msg?->isCopieEmail() ?? false,
                'typeCible' => $msg?->getTypeCible(),
                'cibleLibelle' => $msg?->getCibleLibelle(),
                'createdAt' => $msg?->getCreatedAt()?->format('c'),
                'isLu' => $d->isLu(),
                'isCopie' => $d->isCopie(),
                'hasAttachments' => $msg ? !$msg->getPiecesJointes()->isEmpty() : false,
                'dateLecture' => $d->getDateLecture()?->format('c'),
                'isArchive' => $d->isArchive(),
                'expediteur' => $exp ? [
                    'id' => $exp->getId(),
                    'nom' => $exp->getNom(),
                    'prenom' => $exp->getPrenom(),
                    'email' => $exp->getMailUniv(),
                    'display' => sprintf('%s %s', $exp->getPrenom(), $exp->getNom()),
                ] : null,
            ];
        }, $destinataires);

        $unreadCount = $this->destinataireRepository->countUnreadForUser($user);

        return new JsonResponse([
            'messages' => $data,
            'unreadCount' => $unreadCount,
            'total' => count($data),
        ]);
    }

    /**
     * Compteur de messages non lus (utilisé par le badge de la Topbar).
     */
    #[Route('/unread-count', name: 'api_messages_unread_count', methods: ['GET'])]
    public function getUnreadCount(): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return new JsonResponse(['unreadCount' => 0]);
        }

        $unreadCount = $this->destinataireRepository->countUnreadForUser($user);

        return new JsonResponse(['unreadCount' => $unreadCount]);
    }

    /**
     * Tout marquer comme lu pour l'utilisateur connecté.
     */
    #[Route('/read-all', name: 'api_messages_read_all', methods: ['PATCH', 'POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return new JsonResponse(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $count = $this->destinataireRepository->markAllAsReadForUser($user);

        return new JsonResponse([
            'success' => true,
            'updated' => $count,
            'message' => sprintf('%d message(s) marqué(s) comme lu(s)', $count),
            'unreadCount' => 0,
        ]);
    }

    /**
     * Détail d'un message reçu ou envoyé.
     */
    #[Route('/{id}', name: 'api_messages_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getMessage(int $id): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $message = $this->messageRepository->find($id);
        if (null === $message) {
            return new JsonResponse(['message' => 'Message introuvable'], Response::HTTP_NOT_FOUND);
        }

        $destinataire = $this->destinataireRepository->findOneByUser($id, $user);
        $isAuthor = ($user instanceof Personnel && $message->getExpediteur()?->getId() === $user->getId());

        if (null === $destinataire && !$isAuthor) {
            return new JsonResponse(['message' => 'Accès refusé à ce message'], Response::HTTP_FORBIDDEN);
        }

        // Marquer automatiquement comme lu si l'utilisateur est le destinataire
        if ($destinataire !== null && !$destinataire->isLu()) {
            $destinataire->setIsLu(true);
            $this->em->flush();
        }

        $exp = $message->getExpediteur();

        $piecesJointes = [];
        foreach ($message->getPiecesJointes() as $pj) {
            $piecesJointes[] = [
                'id' => $pj->getId(),
                'nomOriginal' => $pj->getNomOriginal(),
                'taille' => $pj->getTaille(),
                'mimeType' => $pj->getMimeType(),
                'url' => sprintf('/api/messages/%d/pieces-jointes/%d', $message->getId(), $pj->getId()),
            ];
        }

        return new JsonResponse([
            'id' => $message->getId(),
            'sujet' => $message->getSujet(),
            'contenu' => $message->getContenu(),
            'prioritaire' => $message->isPrioritaire(),
            'copieEmail' => $message->isCopieEmail(),
            'typeCible' => $message->getTypeCible(),
            'cibleLibelle' => $message->getCibleLibelle(),
            'createdAt' => $message->getCreatedAt()?->format('c'),
            'isAuthor' => $isAuthor,
            'isLu' => $destinataire?->isLu() ?? true,
            'isCopie' => $destinataire?->isCopie() ?? false,
            'dateLecture' => $destinataire?->getDateLecture()?->format('c'),
            'isArchive' => $destinataire?->isArchive() ?? false,
            'piecesJointes' => $piecesJointes,
            'expediteur' => $exp ? [
                'id' => $exp->getId(),
                'nom' => $exp->getNom(),
                'prenom' => $exp->getPrenom(),
                'email' => $exp->getMailUniv(),
                'display' => sprintf('%s %s', $exp->getPrenom(), $exp->getNom()),
            ] : null,
        ]);
    }

    /**
     * Téléchargement d'une pièce jointe d'un message.
     */
    #[Route('/{id}/pieces-jointes/{pjId}', name: 'api_messages_download_pj', requirements: ['id' => '\d+', 'pjId' => '\d+'], methods: ['GET'])]
    public function downloadPieceJointe(int $id, int $pjId): Response
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return new JsonResponse(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $message = $this->messageRepository->find($id);
        if (null === $message) {
            return new JsonResponse(['message' => 'Message introuvable'], Response::HTTP_NOT_FOUND);
        }

        $destinataire = $this->destinataireRepository->findOneByUser($id, $user);
        $isAuthor = ($user instanceof Personnel && $message->getExpediteur()?->getId() === $user->getId());

        if (null === $destinataire && !$isAuthor) {
            return new JsonResponse(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $pieceJointe = null;
        foreach ($message->getPiecesJointes() as $pj) {
            if ($pj->getId() === $pjId) {
                $pieceJointe = $pj;
                break;
            }
        }

        if (null === $pieceJointe) {
            return new JsonResponse(['message' => 'Pièce jointe introuvable'], Response::HTTP_NOT_FOUND);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/messages/' . $pieceJointe->getFichier();
        if (!file_exists($filePath)) {
            return new JsonResponse(['message' => 'Fichier introuvable sur le serveur'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $pieceJointe->getNomOriginal()
        );
        if ($pieceJointe->getMimeType()) {
            $response->headers->set('Content-Type', $pieceJointe->getMimeType());
        }

        return $response;
    }

    /**
     * Marquer comme lu / non lu.
     */
    #[Route('/{id}/read', name: 'api_messages_toggle_read', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function toggleRead(int $id, Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return new JsonResponse(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $destinataire = $this->destinataireRepository->findOneByUser($id, $user);
        if (null === $destinataire) {
            return new JsonResponse(['message' => 'Message introuvable pour cet utilisateur'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?: [];
        $isLu = isset($body['isLu']) ? (bool) $body['isLu'] : !$destinataire->isLu();

        $destinataire->setIsLu($isLu);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'isLu' => $destinataire->isLu(),
            'dateLecture' => $destinataire->getDateLecture()?->format('c'),
            'unreadCount' => $this->destinataireRepository->countUnreadForUser($user),
        ]);
    }

    /**
     * Archiver / Désarchiver un message reçu.
     */
    #[Route('/{id}/archive', name: 'api_messages_toggle_archive', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function toggleArchive(int $id, Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return new JsonResponse(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $destinataire = $this->destinataireRepository->findOneByUser($id, $user);
        if (null === $destinataire) {
            return new JsonResponse(['message' => 'Message introuvable pour cet utilisateur'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?: [];
        $isArchive = isset($body['isArchive']) ? (bool) $body['isArchive'] : !$destinataire->isArchive();

        $destinataire->setIsArchive($isArchive);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'isArchive' => $destinataire->isArchive(),
        ]);
    }

    /**
     * Liste des messages envoyés par le personnel connecté (Réservé Personnel).
     */
    #[Route('/sent', name: 'api_messages_sent', methods: ['GET'])]
    public function getSentMessages(): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (!$user instanceof Personnel) {
            return new JsonResponse(['message' => 'Accès réservé au personnel'], Response::HTTP_FORBIDDEN);
        }

        $messages = $this->messageRepository->findSentByPersonnel($user);

        $data = array_map(function (Message $m) {
            $stats = $this->destinataireRepository->getReadStatisticsForMessage($m);

            return [
                'id' => $m->getId(),
                'sujet' => $m->getSujet(),
                'contenu' => mb_substr(strip_tags((string) $m->getContenu()), 0, 150) . '...',
                'prioritaire' => $m->isPrioritaire(),
                'copieEmail' => $m->isCopieEmail(),
                'typeCible' => $m->getTypeCible(),
                'cibleLibelle' => $m->getCibleLibelle(),
                'hasAttachments' => !$m->getPiecesJointes()->isEmpty(),
                'createdAt' => $m->getCreatedAt()?->format('c'),
                'totalDestinataires' => $stats['total'],
                'readCount' => $stats['readCount'],
                'percent' => $stats['percent'],
            ];
        }, $messages);

        return new JsonResponse(['messages' => $data]);
    }

    /**
     * Statistiques de lecture détaillées d'un message envoyé.
     */
    #[Route('/sent/{id}/stats', name: 'api_messages_sent_stats', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getSentMessageStats(int $id): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (!$user instanceof Personnel) {
            return new JsonResponse(['message' => 'Accès réservé au personnel'], Response::HTTP_FORBIDDEN);
        }

        $message = $this->messageRepository->find($id);
        if (null === $message || $message->getExpediteur()?->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Message introuvable ou non autorisé'], Response::HTTP_NOT_FOUND);
        }

        $stats = $this->destinataireRepository->getReadStatisticsForMessage($message);

        return new JsonResponse([
            'id' => $message->getId(),
            'sujet' => $message->getSujet(),
            'typeCible' => $message->getTypeCible(),
            'cibleLibelle' => $message->getCibleLibelle(),
            'createdAt' => $message->getCreatedAt()?->format('c'),
            'total' => $stats['total'],
            'readCount' => $stats['readCount'],
            'percent' => $stats['percent'],
            'readers' => $stats['readers'],
        ]);
    }

    /**
     * Options de cibles pour la rédaction (semestres, groupes, étudiants, enseignants, personnels CC).
     */
    #[Route('/targets', name: 'api_messages_targets', methods: ['GET'])]
    public function getTargetOptions(): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (!$user instanceof Personnel) {
            return new JsonResponse(['message' => 'Accès réservé au personnel'], Response::HTTP_FORBIDDEN);
        }

        $assignment = $this->permissions->getActiveDepartementPersonnel($user);
        $departement = $assignment?->getDepartement();

        if ($departement === null) {
            return new JsonResponse([
                'departement' => null,
                'semestres' => [],
                'groupes' => [],
                'etudiants' => [],
                'enseignants' => [],
                'personnels' => [],
            ]);
        }

        // 1. Semestres actifs du département
        $semestresActifs = $this->semestreRepository->createQueryBuilder('s')
            ->innerJoin('s.annee', 'sa')
            ->innerJoin('sa.pn', 'pn')
            ->innerJoin('pn.diplome', 'd')
            ->where('d.departement = :dept')
            ->andWhere('s.actif = true')
            ->setParameter('dept', $departement)
            ->orderBy('s.ordreLmd', 'ASC')
            ->addOrderBy('s.libelle', 'ASC')
            ->getQuery()
            ->getResult();

        $semestres = [];
        $groupes = [];
        $groupeIdsMap = [];
        $etudiants = [];
        $etudiantIdsMap = [];

        foreach ($semestresActifs as $s) {
            $semestres[] = [
                'id' => $s->getId(),
                'libelle' => $s->getLibelle(),
                'code' => $s->getCodeElement() ?: ('S' . $s->getOrdreLmd()),
                'ordreLmd' => $s->getOrdreLmd(),
            ];

            // Groupes rattachés aux semestres actifs
            foreach ($s->getGroupes() as $g) {
                $gId = $g->getId();
                $typeStr = $g->getType()->value ?? (string) $g->getType();
                if (!isset($groupeIdsMap[$gId])) {
                    $groupeIdsMap[$gId] = [
                        'id' => $gId,
                        'libelle' => $g->getLibelle(),
                        'type' => $typeStr,
                        'semestres' => [$s->getLibelle()],
                        'semestreIds' => [$s->getId()],
                    ];
                } else {
                    if (!in_array($s->getLibelle(), $groupeIdsMap[$gId]['semestres'], true)) {
                        $groupeIdsMap[$gId]['semestres'][] = $s->getLibelle();
                        $groupeIdsMap[$gId]['semestreIds'][] = $s->getId();
                    }
                }
            }

            // Étudiants inscrits dans ces semestres
            foreach ($s->getScolariteSemestre() as $scolSem) {
                $etudiant = $scolSem->getScolarite()?->getEtudiant();
                if ($etudiant && !isset($etudiantIdsMap[$etudiant->getId()])) {
                    $etudiantIdsMap[$etudiant->getId()] = true;
                    $etudiants[] = [
                        'id' => $etudiant->getId(),
                        'nom' => $etudiant->getNom(),
                        'prenom' => $etudiant->getPrenom(),
                        'display' => sprintf('%s %s', $etudiant->getPrenom(), $etudiant->getNom()),
                        'email' => $etudiant->getMailUniv(),
                        'numeroEtudiant' => $etudiant->getNumEtudiant(),
                        'semestre' => $s->getLibelle(),
                    ];
                }
            }
        }

        $groupes = array_values($groupeIdsMap);
        usort($groupes, function ($a, $b) {
            $priority = ['CM' => 1, 'TD' => 2, 'TP' => 3];
            $pA = $priority[$a['type']] ?? 4;
            $pB = $priority[$b['type']] ?? 4;
            if ($pA !== $pB) {
                return $pA <=> $pB;
            }
            return strnatcasecmp((string) $a['libelle'], (string) $b['libelle']);
        });

        // Compléter avec les étudiants ayant une scolarité active dans le département
        $scols = $this->em->getRepository(\App\Entity\Etudiant\EtudiantScolarite::class)->findBy([
            'departement' => $departement,
            'actif' => true,
        ]);
        foreach ($scols as $sc) {
            $etudiant = $sc->getEtudiant();
            if ($etudiant && !isset($etudiantIdsMap[$etudiant->getId()])) {
                $etudiantIdsMap[$etudiant->getId()] = true;
                $etudiants[] = [
                    'id' => $etudiant->getId(),
                    'nom' => $etudiant->getNom(),
                    'prenom' => $etudiant->getPrenom(),
                    'display' => sprintf('%s %s', $etudiant->getPrenom(), $etudiant->getNom()),
                    'email' => $etudiant->getMailUniv(),
                    'numeroEtudiant' => $etudiant->getNumEtudiant(),
                    'semestre' => null,
                ];
            }
        }

        usort($etudiants, function ($a, $b) {
            $c = strcasecmp((string) $a['nom'], (string) $b['nom']);
            return 0 !== $c ? $c : strcasecmp((string) $a['prenom'], (string) $b['prenom']);
        });

        // 2. Enseignants et Personnels du département
        $departementPersonnels = $this->em->getRepository(StructureDepartementPersonnel::class)->findBy([
            'departement' => $departement,
        ]);

        $enseignants = [];
        $personnels = [];
        $seenPersonnelIds = [];

        foreach ($departementPersonnels as $dp) {
            $p = $dp->getPersonnel();
            if ($p === null || isset($seenPersonnelIds[$p->getId()])) {
                continue;
            }
            $seenPersonnelIds[$p->getId()] = true;

            $permissions = $dp->getPermissions();
            $statutVal = $p->getStatut()?->value;
            $isNonTeacher = in_array(mb_strtoupper(trim((string) $statutVal)), ['ADM', 'TEC', 'ASS', 'BIATSS'], true);
            $isTeacher = !$isNonTeacher && (
                in_array('ROLE_TEACHER', $permissions, true)
                || in_array('ROLE_PERMANENT', $permissions, true)
                || in_array('ROLE_RESP_PARCOURS', $permissions, true)
                || in_array('ROLE_DIRECTEUR_ETUDES', $permissions, true)
                || in_array('ROLE_CHEF_DEPARTEMENT', $permissions, true)
                || $this->isTeacherStatut($statutVal)
            );
            $isVacataire = $this->isVacataireStatut($statutVal);
            $statutLibelle = $p->getStatut()?->getLibelle() ?? $statutVal ?? 'Personnel';

            $personnelData = [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prenom' => $p->getPrenom(),
                'display' => sprintf('%s %s', $p->getPrenom(), $p->getNom()),
                'email' => $p->getMailUniv(),
                'statut' => $statutVal,
                'statutLibelle' => $statutLibelle,
                'isEnseignant' => $isTeacher,
                'isVacataire' => $isVacataire,
                'categorie' => $isVacataire ? 'VACATAIRE' : 'PERMANENT',
            ];

            $personnels[] = $personnelData;

            if ($isTeacher) {
                $enseignants[] = $personnelData;
            }
        }

        $sortPersonnels = function ($a, $b) {
            $c = strcasecmp((string) $a['nom'], (string) $b['nom']);
            return 0 !== $c ? $c : strcasecmp((string) $a['prenom'], (string) $b['prenom']);
        };
        usort($enseignants, $sortPersonnels);
        usort($personnels, $sortPersonnels);

        return new JsonResponse([
            'departement' => [
                'id' => $departement->getId(),
                'libelle' => $departement->getLibelle(),
            ],
            'semestres' => $semestres,
            'groupes' => $groupes,
            'etudiants' => $etudiants,
            'enseignants' => $enseignants,
            'personnels' => $personnels,
        ]);
    }

    /**
     * Envoi d'un nouveau message (Réservé Personnel : les étudiants ne peuvent que recevoir).
     */
    #[Route('', name: 'api_messages_create', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();
        if (!$user instanceof Personnel) {
            return new JsonResponse([
                'message' => 'Seuls les personnels et enseignants sont autorisés à rédiger des messages.'
            ], Response::HTTP_FORBIDDEN);
        }

        $payload = [];
        if (str_starts_with((string) $request->headers->get('content-type', ''), 'multipart/form-data')) {
            $payload = $request->request->all();
            if (isset($payload['payload']) && is_string($payload['payload'])) {
                $decoded = json_decode($payload['payload'], true);
                if (is_array($decoded)) {
                    $payload = array_merge($payload, $decoded);
                }
            } else {
                foreach (['semestreIds', 'groupeIds', 'etudiantIds', 'enseignantIds', 'copiePersonnelIds'] as $arrKey) {
                    if (isset($payload[$arrKey]) && is_string($payload[$arrKey])) {
                        $decoded = json_decode($payload[$arrKey], true);
                        if (is_array($decoded)) {
                            $payload[$arrKey] = $decoded;
                        }
                    }
                }
            }
        } else {
            $payload = json_decode($request->getContent(), true) ?: [];
        }

        $sujet = trim((string) ($payload['sujet'] ?? ''));
        $contenu = trim((string) ($payload['contenu'] ?? ''));
        $destinataireType = (string) ($payload['destinataireType'] ?? $payload['typeCible'] ?? 'SEMESTRES');
        $prioritaire = (bool) ($payload['prioritaire'] ?? false);
        $copieEmail = (bool) ($payload['copieEmail'] ?? false);

        if (empty($sujet) || empty($contenu)) {
            return new JsonResponse(['message' => 'Le sujet et le contenu sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $assignment = $this->permissions->getActiveDepartementPersonnel($user);
        $departement = $assignment?->getDepartement();

        /** @var Etudiant[] $primaryEtudiants */
        $primaryEtudiants = [];
        $primaryEtudiantIds = [];

        /** @var Personnel[] $primaryPersonnels */
        $primaryPersonnels = [];
        $primaryPersonnelIds = [];

        $cibleLibelle = '';

        // 1. Résolution des destinataires principaux
        if (in_array($destinataireType, ['SEMESTRES', 'PROMO'], true)) {
            $semestreIds = (array) ($payload['semestreIds'] ?? ($payload['semestreId'] ? [$payload['semestreId']] : []));
            $semestreIds = array_map('intval', array_filter($semestreIds));
            $nomsSemestres = [];

            if (!empty($semestreIds)) {
                $semestres = $this->semestreRepository->findBy(['id' => $semestreIds]);
                foreach ($semestres as $semestre) {
                    $nomsSemestres[] = $semestre->getLibelle();
                    foreach ($semestre->getScolariteSemestre() as $scol) {
                        $etudiant = $scol->getScolarite()?->getEtudiant();
                        if ($etudiant && !isset($primaryEtudiantIds[$etudiant->getId()])) {
                            $primaryEtudiantIds[$etudiant->getId()] = true;
                            $primaryEtudiants[] = $etudiant;
                        }
                    }
                    foreach ($semestre->getGroupes() as $g) {
                        foreach ($g->getScolariteSemestres() as $scolSem) {
                            $etudiant = $scolSem->getScolarite()?->getEtudiant();
                            if ($etudiant && !isset($primaryEtudiantIds[$etudiant->getId()])) {
                                $primaryEtudiantIds[$etudiant->getId()] = true;
                                $primaryEtudiants[] = $etudiant;
                            }
                        }
                        foreach ($g->getEtudiants() as $etudiant) {
                            if ($etudiant && !isset($primaryEtudiantIds[$etudiant->getId()])) {
                                $primaryEtudiantIds[$etudiant->getId()] = true;
                                $primaryEtudiants[] = $etudiant;
                            }
                        }
                    }
                }
            }
            $cibleLibelle = 'Semestre(s) ' . implode(', ', $nomsSemestres);
        } elseif (in_array($destinataireType, ['GROUPES', 'GROUPE'], true)) {
            $groupeIds = (array) ($payload['groupeIds'] ?? ($payload['groupeId'] ? [$payload['groupeId']] : []));
            $groupeIds = array_map('intval', array_filter($groupeIds));
            $nomsGroupes = [];

            if (!empty($groupeIds)) {
                $groupes = $this->groupeRepository->findBy(['id' => $groupeIds]);
                foreach ($groupes as $groupe) {
                    $nomsGroupes[] = $groupe->getLibelle();
                    foreach ($groupe->getScolariteSemestres() as $scolSem) {
                        $etudiant = $scolSem->getScolarite()?->getEtudiant();
                        if ($etudiant && !isset($primaryEtudiantIds[$etudiant->getId()])) {
                            $primaryEtudiantIds[$etudiant->getId()] = true;
                            $primaryEtudiants[] = $etudiant;
                        }
                    }
                    foreach ($groupe->getEtudiants() as $etudiant) {
                        if ($etudiant && !isset($primaryEtudiantIds[$etudiant->getId()])) {
                            $primaryEtudiantIds[$etudiant->getId()] = true;
                            $primaryEtudiants[] = $etudiant;
                        }
                    }
                }
            }
            $cibleLibelle = 'Groupe(s) ' . implode(', ', $nomsGroupes);
        } elseif (in_array($destinataireType, ['ETUDIANTS', 'INDIVIDUEL'], true)) {
            $etudiantIds = array_map('intval', (array) ($payload['etudiantIds'] ?? []));
            if (!empty($etudiantIds)) {
                $primaryEtudiants = $this->etudiantRepository->findBy(['id' => $etudiantIds]);
                foreach ($primaryEtudiants as $et) {
                    $primaryEtudiantIds[$et->getId()] = true;
                }
            }
            $count = count($primaryEtudiants);
            if ($count === 1) {
                $cibleLibelle = sprintf('%s %s', $primaryEtudiants[0]->getPrenom(), $primaryEtudiants[0]->getNom());
            } elseif ($count <= 3) {
                $names = array_map(fn (Etudiant $e) => sprintf('%s %s', $e->getPrenom(), $e->getNom()), $primaryEtudiants);
                $cibleLibelle = implode(', ', $names);
            } else {
                $cibleLibelle = sprintf('%d étudiants', $count);
            }
        } elseif ($destinataireType === 'ENSEIGNANTS') {
            $enseignantsType = (string) ($payload['enseignantsType'] ?? 'ALL'); // ALL, PERMANENT, VACATAIRE, NOMINATIF
            $departementPersonnels = $departement !== null ? $this->em->getRepository(StructureDepartementPersonnel::class)->findBy([
                'departement' => $departement,
            ]) : [];

            $deptTeachers = [];
            foreach ($departementPersonnels as $dp) {
                $p = $dp->getPersonnel();
                if ($p === null) {
                    continue;
                }
                $isTeacher = in_array('ROLE_TEACHER', $dp->getPermissions(), true) || $this->isTeacherStatut($p->getStatut()?->value);
                if ($isTeacher) {
                    $deptTeachers[$p->getId()] = $p;
                }
            }

            if ($enseignantsType === 'ALL') {
                $primaryPersonnels = array_values($deptTeachers);
                $cibleLibelle = 'Tous les enseignants';
            } elseif ($enseignantsType === 'PERMANENT') {
                foreach ($deptTeachers as $p) {
                    if (!$this->isVacataireStatut($p->getStatut()?->value)) {
                        $primaryPersonnels[] = $p;
                    }
                }
                $cibleLibelle = 'Enseignants permanents';
            } elseif ($enseignantsType === 'VACATAIRE') {
                foreach ($deptTeachers as $p) {
                    if ($this->isVacataireStatut($p->getStatut()?->value)) {
                        $primaryPersonnels[] = $p;
                    }
                }
                $cibleLibelle = 'Enseignants vacataires';
            } elseif ($enseignantsType === 'NOMINATIF') {
                $teacherIds = array_map('intval', (array) ($payload['enseignantIds'] ?? []));
                foreach ($teacherIds as $tId) {
                    if (isset($deptTeachers[$tId])) {
                        $primaryPersonnels[] = $deptTeachers[$tId];
                    }
                }
                $count = count($primaryPersonnels);
                if ($count <= 2 && $count > 0) {
                    $names = array_map(fn (Personnel $p) => sprintf('%s %s', $p->getPrenom(), $p->getNom()), $primaryPersonnels);
                    $cibleLibelle = implode(', ', $names);
                } else {
                    $cibleLibelle = sprintf('%d enseignant(s)', $count);
                }
            }
            foreach ($primaryPersonnels as $p) {
                $primaryPersonnelIds[$p->getId()] = true;
            }
        }

        if (empty($cibleLibelle)) {
            $cibleLibelle = (string) ($payload['cibleLibelle'] ?? $destinataireType);
        }

        $message = new Message();
        $message->setSujet($sujet);
        $message->setContenu($contenu);
        $message->setExpediteur($user);
        $message->setTypeCible($destinataireType);
        $message->setCibleLibelle($cibleLibelle);
        $message->setPrioritaire($prioritaire);
        $message->setCopieEmail($copieEmail);
        $message->setDepartement($departement);

        // Ajout des destinataires principaux ($isCopie = false)
        foreach ($primaryEtudiants as $etudiant) {
            $dest = new MessageDestinataire();
            $dest->setEtudiant($etudiant);
            $dest->setIsCopie(false);
            $message->addDestinataire($dest);
        }

        foreach ($primaryPersonnels as $personnel) {
            $dest = new MessageDestinataire();
            $dest->setPersonnel($personnel);
            $dest->setIsCopie(false);
            $message->addDestinataire($dest);
        }

        // 2. Destinataires en copie (CC) : membres du personnel toutes catégories
        $copiePersonnelIds = array_map('intval', (array) ($payload['copiePersonnelIds'] ?? []));
        $copiePersonnelIds = array_unique($copiePersonnelIds);

        foreach ($copiePersonnelIds as $pId) {
            // Éviter d'ajouter en copie un personnel déjà destinataire principal
            if (isset($primaryPersonnelIds[$pId])) {
                continue;
            }
            $personnel = $this->personnelRepository->find($pId);
            if ($personnel !== null) {
                $dest = new MessageDestinataire();
                $dest->setPersonnel($personnel);
                $dest->setIsCopie(true);
                $message->addDestinataire($dest);
            }
        }

        if ($message->getDestinataires()->isEmpty()) {
            return new JsonResponse(['message' => 'Aucun destinataire sélectionné ou trouvé.'], Response::HTTP_BAD_REQUEST);
        }

        // Traitement des pièces jointes envoyées en multipart
        $uploadedFiles = $request->files->get('files') ?? $request->files->get('piecesJointes') ?? [];
        if ($uploadedFiles instanceof UploadedFile) {
            $uploadedFiles = [$uploadedFiles];
        } elseif (!is_array($uploadedFiles)) {
            $uploadedFiles = [];
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/messages';
        if (!empty($uploadedFiles) && !is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        foreach ($uploadedFiles as $file) {
            if ($file instanceof UploadedFile) {
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
                $size = (int) $file->getSize();
                $ext = $file->getClientOriginalExtension() ?: 'bin';
                $safeName = sprintf('pj_%s_%s.%s', uniqid(), bin2hex(random_bytes(4)), $ext);
                $file->move($uploadDir, $safeName);

                $pj = new MessagePieceJointe();
                $pj->setNomOriginal($originalName);
                $pj->setFichier($safeName);
                $pj->setTaille($size);
                $pj->setMimeType($mimeType);
                $message->addPieceJointe($pj);
            }
        }

        $this->em->persist($message);
        $this->em->flush();

        // Envoi optionnel par email si copie demandée
        if ($copieEmail && $this->emailService !== null) {
            $this->dispatchEmailCopies($message, $departement);
        }

        return new JsonResponse([
            'success' => true,
            'messageId' => $message->getId(),
            'destinatairesCount' => $message->getDestinataires()->count(),
            'piecesJointesCount' => $message->getPiecesJointes()->count(),
        ], Response::HTTP_CREATED);
    }

    private function dispatchEmailCopies(Message $message, ?object $departement): void
    {
        if (null === $this->emailService) {
            return;
        }

        $emailAttachments = [];
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/messages';
        foreach ($message->getPiecesJointes() as $pj) {
            $filePath = $uploadDir . '/' . $pj->getFichier();
            if (file_exists($filePath)) {
                $emailAttachments[] = [
                    'path' => $filePath,
                    'name' => $pj->getNomOriginal(),
                    'contentType' => $pj->getMimeType(),
                ];
            }
        }

        $exp = $message->getExpediteur();
        $expName = $exp ? sprintf('%s %s', $exp->getPrenom(), $exp->getNom()) : 'Expéditeur';

        foreach ($message->getDestinataires() as $dest) {
            $email = null;
            $destName = '';

            if ($dest->getEtudiant() !== null) {
                $email = $dest->getEtudiant()->getMailUniv();
                $destName = sprintf('%s %s', $dest->getEtudiant()->getPrenom(), $dest->getEtudiant()->getNom());
            } elseif ($dest->getPersonnel() !== null) {
                $email = $dest->getPersonnel()->getMailUniv();
                $destName = sprintf('%s %s', $dest->getPersonnel()->getPrenom(), $dest->getPersonnel()->getNom());
            }

            if (empty($email)) {
                continue;
            }

            $subjectPrefix = $dest->isCopie() ? '[Copie] ' : ($message->isPrioritaire() ? '[URGENT] ' : '');

            try {
                $this->emailService->send(
                    emailKey: 'message.new_message',
                    to: $email,
                    context: [
                        'message' => $message,
                        'sujet' => $subjectPrefix . $message->getSujet(),
                        'contenu' => $message->getContenu(),
                        'destinataire_nom' => $destName,
                        'expediteur_nom' => $expName,
                        'is_copie' => $dest->isCopie(),
                        'prioritaire' => $message->isPrioritaire(),
                        'date_envoi' => $message->getCreatedAt()?->format('d/m/Y H:i'),
                    ],
                    departement: $departement instanceof StructureDepartement ? $departement : null,
                    attachments: $emailAttachments,
                );
            } catch (\Throwable) {
                // Ne pas bloquer l'enregistrement du message si l'envoi individuel échoue
            }
        }
    }

    private function isTeacherStatut(?string $statut): bool
    {
        if (null === $statut) {
            return false;
        }
        $s = mb_strtoupper(trim($statut));

        return in_array($s, self::PERMANENTS_STATUTS, true) || in_array($s, self::VACATAIRES_STATUTS, true);
    }

    private function isVacataireStatut(?string $statut): bool
    {
        if (null === $statut) {
            return false;
        }

        return in_array(mb_strtoupper(trim($statut)), self::VACATAIRES_STATUTS, true);
    }

    private function getCurrentUser(): Personnel|Etudiant|null
    {
        $user = $this->getUser();

        return ($user instanceof Personnel || $user instanceof Etudiant) ? $user : null;
    }
}

