<?php

namespace UnifolioBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

final class RevisionDemandeeEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'unifolio.revision_demandee';
    }

    public function getLabel(): string
    {
        return 'UniFolio — révision demandée';
    }

    public function getDescription(): string
    {
        return 'Envoyé à l\'étudiant lorsqu\'un enseignant lui demande de réviser une trace.';
    }

    public function getDefaultSubject(): string
    {
        return '[UniFolio] Une révision vous est demandée : {{ trace_titre }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/unifolio/revision_demandee.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'etudiant_nom' => 'Nom et prénom de l\'étudiant',
            'evaluateur_nom' => 'Nom et prénom de l\'enseignant',
            'trace_titre' => 'Titre de la trace',
            'motif' => 'Ce que l\'enseignant demande de réviser',
            'urlPortal' => 'Lien vers la trace',
        ];
    }
}
