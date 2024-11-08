<?php
/*
 * Copyright (c) 2023. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Components/Questionnaire/Interfaces/TypeDestinataireInterface.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 08/11/2023 17:47
 */

namespace App\Components\Questionnaire\Interfaces;

use App\Classes\Mail\MailerFromTwig;
use App\Components\Questionnaire\DTO\ReponsesUser;

interface TypeDestinataireInterface
{
    public function getListe(): array; // liste pour l'affichage des choix

    public function getListeDestinataire(): array; // liste des répondants

    public function send(array $liste): void; // envoi des questionnaires

    public function getChoixUser(string $uuid): ?QuestChoixInterface;

    public function sendRelanceIndividuelle(int $choix): void;

    public function sendMailAccuse(QuestChoixInterface $choixUser, MailerFromTwig $myMailer): void;


//    public function sendMail(QuestChoixInterface $choixUser, MailerFromTwig $myMailer): void;

    public function sauvegardeReponse(QuestChoixInterface $choixUser, string $cleReponse, string $cleQuestion): void;

    public function sauvegardeReponseTexte(QuestChoixInterface $choixUser, string $cleQuestion, string $value): void;

    public function getReponses(): ReponsesUser;

}
