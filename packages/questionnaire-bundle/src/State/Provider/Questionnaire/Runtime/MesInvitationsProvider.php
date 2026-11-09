<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\MesInvitationDto;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireInvitationRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<MesInvitationDto>
 */
final class MesInvitationsProvider implements ProviderInterface
{
    public function __construct(
        private readonly QuestionnaireInvitationRepository $invitations,
        private readonly Security $security,
    ) {
    }

    /** @return list<MesInvitationDto> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof Etudiant && !$user instanceof Personnel) {
            return [];
        }

        $out = [];
        foreach ($this->invitations->findPourAdresses(self::adresses($user)) as $invitation) {
            $q = $invitation->getQuestionnaire();
            $out[] = new MesInvitationDto(
                uuid: $q->getUuidString(),
                title: (string) $q->getTitle(),
                description: $q->getDescription(),
                estimatedTime: $q->getEstimatedTime(),
                deadline: $q->getClosingDate()?->format('d/m/Y'),
                token: (string) $invitation->getToken(),
                status: $invitation->getStatus()->value,
                anonymous: $q->estAnonyme(),
                ouvert: $q->estOuvert(),
                submittedAt: $invitation->getSubmittedAt()?->format(\DateTimeInterface::ATOM),
                modifiable: $q->estOuvert() && (!$invitation->isSubmitted() || $q->autoriseModificationReponses()),
            );
        }

        return $out;
    }

    /** @return list<string> adresses de la personne, en minuscules */
    public static function adresses(Etudiant|Personnel $user): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (?string $mail) => $mail !== null && trim($mail) !== '' ? mb_strtolower(trim($mail)) : null,
            [$user->getMailUniv(), $user->getMailPerso()],
        ))));
    }
}
