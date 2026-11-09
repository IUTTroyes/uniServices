<?php

namespace QuestionnaireBundle\Controller;

use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireRetour;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSectionInstance;
use QuestionnaireBundle\Security\QuestionnaireAccess;
use QuestionnaireBundle\Security\QuestionnaireResolver;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use QuestionnaireBundle\Services\Notification\QuestionnaireNotifier;
use QuestionnaireBundle\Services\QuestionnaireReglages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Retours des enseignants sur leurs résultats (commentaire, plan d'action), une fois les résultats publiés.
 *
 * GET /api/questionnaires/{uuid}/retours   équipe : tous les retours ; destinataire : les siens
 * PUT /api/questionnaires/{uuid}/retours   destinataire : {"section": id, "commentaire": "…", "planAction": "…"}
 */
class QuestionnaireRetourController extends AbstractController
{
    private const LONGUEUR_MAX = 10000;

    public function __construct(
        private readonly QuestionnaireResolver $questionnaires,
        private readonly QuestionnaireAccess $access,
        private readonly QuestionnaireReglages $reglages,
        private readonly QuestionnaireNotifier $notifier,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/questionnaires/{uuid}/retours', name: 'api_questionnaire_retours', methods: ['GET'])]
    public function liste(string $uuid): JsonResponse
    {
        $q = $this->questionnaires->charger($uuid, QuestionnaireVoter::SYNTHESE);
        $user = $this->getUser();
        $criteres = ['sectionInstance' => $this->em->getRepository(QuestionnaireSectionInstance::class)->findBy(['questionnaire' => $q])];
        if (!$this->access->peutVoir($user, $q)) {
            $criteres['personnel'] = $user;
        }

        return new JsonResponse(array_map(static fn (QuestionnaireRetour $r) => [
            'section' => $r->getSectionInstance()->getId(),
            'sectionTitre' => $r->getSectionInstance()->getTitleSnapshot(),
            'personnel' => $r->getPersonnel()->getDisplay(),
            'commentaire' => $r->getCommentaire(),
            'planAction' => $r->getPlanAction(),
            'modifieLe' => $r->getModifieLe()->format(\DateTimeInterface::ATOM),
        ], $this->em->getRepository(QuestionnaireRetour::class)->findBy($criteres)));
    }

    #[Route('/api/questionnaires/{uuid}/retours', name: 'api_questionnaire_retours_enregistrer', methods: ['PUT'])]
    public function enregistrer(string $uuid, Request $request): JsonResponse
    {
        $q = $this->questionnaires->charger($uuid, QuestionnaireVoter::SYNTHESE);
        $user = $this->getUser();
        $donnees = json_decode($request->getContent(), true);
        $sectionId = \is_array($donnees) ? (int) ($donnees['section'] ?? 0) : 0;
        if (!$user instanceof Personnel || !\in_array($sectionId, $this->access->sectionsDestinataire($user, $q), true)) {
            throw new AccessDeniedHttpException('Seul un destinataire des résultats de cette section peut y répondre.');
        }
        $commentaire = isset($donnees['commentaire']) ? (string) $donnees['commentaire'] : null;
        $plan = isset($donnees['planAction']) ? (string) $donnees['planAction'] : null;
        if (mb_strlen((string) $commentaire) > self::LONGUEUR_MAX || mb_strlen((string) $plan) > self::LONGUEUR_MAX) {
            throw new UnprocessableEntityHttpException('Texte trop long.');
        }

        $section = $this->em->find(QuestionnaireSectionInstance::class, $sectionId);
        $retour = $this->em->getRepository(QuestionnaireRetour::class)->findOneBy(['sectionInstance' => $section, 'personnel' => $user])
            ?? new QuestionnaireRetour($section, $user);
        $retour->modifier($commentaire, $plan);
        $this->em->persist($retour);
        $this->em->flush();

        foreach ($this->responsables($q) as $responsable) {
            $this->notifier->retour($q, $responsable, $user, (string) $section->getTitleSnapshot());
        }

        return new JsonResponse(['section' => $sectionId, 'commentaire' => $retour->getCommentaire(), 'planAction' => $retour->getPlanAction(), 'modifieLe' => $retour->getModifieLe()->format(\DateTimeInterface::ATOM)]);
    }

    /** @return list<Personnel> auteur et référent qualité du département */
    private function responsables(Questionnaire $q): array
    {
        $personnes = [];
        if ($q->getAuthor() !== null) {
            $personnes[$q->getAuthor()->getId()] = $q->getAuthor();
        }
        $referent = $this->reglages->pour($q->getDepartement())['responsable_qualite'];
        if ($referent !== null && ($p = $this->em->find(Personnel::class, $referent)) !== null) {
            $personnes[$p->getId()] = $p;
        }

        return array_values($personnes);
    }
}
