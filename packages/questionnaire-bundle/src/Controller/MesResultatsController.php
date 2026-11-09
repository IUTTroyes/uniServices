<?php

namespace QuestionnaireBundle\Controller;

use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireDiffusion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Résultats publiés qui concernent la personne connectée : questionnaires et sections dont elle est destinataire.
 * GET /api/questionnaire/mes-resultats
 */
class MesResultatsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/questionnaire/mes-resultats', name: 'api_questionnaire_mes_resultats', methods: ['GET'])]
    #[IsGranted('IS_PERSONNEL')]
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        $diffusions = $this->em->getRepository(QuestionnaireDiffusion::class)->createQueryBuilder('d')
            ->addSelect('s', 'q')
            ->join('d.sectionInstance', 's')
            ->join('s.questionnaire', 'q')
            ->where('d.personnel = :moi')->setParameter('moi', $user instanceof Personnel ? $user : null)
            ->andWhere('q.resultatsPubliesLe IS NOT NULL')
            ->orderBy('q.resultatsPubliesLe', 'DESC')->addOrderBy('s.sortOrder', 'ASC')
            ->getQuery()->getResult();

        $questionnaires = [];
        foreach ($diffusions as $diffusion) {
            $section = $diffusion->getSectionInstance();
            $q = $section->getQuestionnaire();
            $questionnaires[$q->getUuidString()] ??= [
                'uuid' => $q->getUuidString(),
                'title' => $q->getTitle(),
                'anneeUniversitaire' => $q->getAnneeUniversitaire()?->getLibelle(),
                'publieLe' => $q->getResultatsPubliesLe()?->format(\DateTimeInterface::ATOM),
                'sections' => [],
            ];
            $questionnaires[$q->getUuidString()]['sections'][] = [
                'id' => $section->getId(),
                'titre' => $section->getTitleSnapshot(),
                'enseignement' => $section->getEnseignement()?->getLibelle(),
            ];
        }

        return new JsonResponse(array_values($questionnaires));
    }
}
