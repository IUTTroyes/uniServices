<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use QuestionnaireBundle\State\Provider\Questionnaire\Runtime\MesInvitationsProvider;

/**
 * Questionnaires auxquels la personne connectée (étudiant ou personnel) est invitée, repérés par ses adresses e-mail.
 * Le jeton n'est livré qu'à son destinataire : il ouvre l'écran de réponse.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/questionnaire/mes-invitations',
            paginationEnabled: false,
            security: "is_granted('IS_PERSONNEL') or is_granted('IS_ETUDIANT')",
            provider: MesInvitationsProvider::class,
        ),
    ],
)]
final class MesInvitationDto
{
    public function __construct(
        public string $uuid,
        public string $title,
        public ?string $description,
        public ?int $estimatedTime,
        public ?string $deadline,
        public string $token,
        public string $status,
        public bool $anonymous,
        /** Le questionnaire accepte encore des réponses (publié, dans sa période d'ouverture). */
        public bool $ouvert,
        public ?string $submittedAt,
        /** Réponses modifiables (questionnaire ouvert, et modification autorisée s'il a déjà été rempli). */
        public bool $modifiable = false,
    ) {
    }
}
