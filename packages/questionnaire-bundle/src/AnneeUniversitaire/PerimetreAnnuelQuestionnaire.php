<?php

declare(strict_types=1);

namespace QuestionnaireBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use Symfony\Component\HttpFoundation\Request;

final class PerimetreAnnuelQuestionnaire extends AbstractPerimetreAnnuelProvider
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function chemins(): array
    {
        return [
            'Questionnaire' => 'anneeUniversitaire',
            'QuestionnaireSection' => 'questionnaire.anneeUniversitaire',
            'QuestionnaireQuestion' => 'section.questionnaire.anneeUniversitaire',
            'QuestionnaireSectionInstance' => 'questionnaire.anneeUniversitaire',
            'QuestionnaireInvitation' => 'questionnaire.anneeUniversitaire',
            'QuestionnaireAnswer' => 'invitation.questionnaire.anneeUniversitaire',
            'QuestionnaireRetour' => 'sectionInstance.questionnaire.anneeUniversitaire',
            'QuestionnaireDiffusion' => 'sectionInstance.questionnaire.anneeUniversitaire',
        ];
    }

    public function modesDto(): array
    {
        return [
            'QuestionnaireAnalyticsDto' => 'ressource', 'PublishOutputDto' => 'ressource',
            'PreviewIndexDto' => 'ressource', 'PreviewSectionDto' => 'ressource',
            'InvitationIndexDto' => 'ressource', 'InvitationSectionResource' => 'ressource',
            'SaveAnswersResource' => 'ressource', 'SubmitResource' => 'ressource',
            'DuplicateOutputDto' => 'annuel', 'MesInvitationDto' => 'transversal',
        ];
    }

    public function espaceDeNoms(): string
    {
        return 'QuestionnaireBundle';
    }

    public function requeteHorsAnnee(string $classe, Request $request, array $corps): bool
    {
        return $classe === Questionnaire::class
            && ($request->query->getBoolean('estModele') || ($corps['estModele'] ?? false) === true);
    }

    public function conditionHorsAnnee(string $classe, string $alias): ?string
    {
        return $classe === Questionnaire::class ? $alias.'.estModele = true' : null;
    }

    public function entiteDeContexte(string $classe, Request $request): ?object
    {
        if (!str_starts_with($classe, 'QuestionnaireBundle\\ApiDto\\')) {
            return null;
        }

        $entite = null;
        foreach (['questionnaireUuid', 'surveyId'] as $cle) {
            if ($uuid = $request->attributes->get($cle)) {
                $entite = $this->em->getRepository(Questionnaire::class)->findOneBy(['uuid' => $uuid]);
            }
        }
        if ($jeton = $request->attributes->get('token')) {
            $entite = $this->em->getRepository(QuestionnaireInvitation::class)->findOneBy(['token' => $jeton]);
        }

        return $entite;
    }
}
