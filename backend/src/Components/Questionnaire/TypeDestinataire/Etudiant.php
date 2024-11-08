<?php
/*
 * Copyright (c) 2023. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Components/Questionnaire/TypeDestinataire/Etudiant.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 08/11/2023 17:47
 */

namespace App\Components\Questionnaire\TypeDestinataire;

use App\Classes\Mail\MailerFromTwig;
use App\Components\Questionnaire\DTO\ReponsesUser;
use App\Components\Questionnaire\Interfaces\QuestChoixInterface;
use App\Components\Questionnaire\Interfaces\TypeDestinataireInterface;
use App\Entity\QuestChoixEtudiant;
use App\Event\QualiteRelanceEvent;
use App\Repository\EtudiantRepository;
use App\Repository\QuestChoixEtudiantRepository;
use App\Repository\QuestChoixRepository;
use App\Repository\QuestQuestionRepository;
use App\Repository\QuestReponseRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Etudiant extends AbstractTypeDestinataire implements TypeDestinataireInterface
{
    public const LABEL = 'etudiant';
    public const ENTITY = QuestChoixEtudiant::class;

    public function __construct(
        QuestChoixRepository $questChoixRepository,
        QuestQuestionRepository $questQuestionRepository,
        QuestReponseRepository $questReponseRepository,
        protected MailerFromTwig $myMailer,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager,
        protected QuestChoixEtudiantRepository $questChoixEtudiantRepository,
        protected EtudiantRepository $etudiantRepository
    ) {
        parent::__construct($eventDispatcher, $entityManager, $questChoixRepository, $questQuestionRepository,
            $questReponseRepository);
    }

    public function getListe(): array
    {
        if ($this->questionnaire->getSemestre() !== null) {
            $etudiants = $this->etudiantRepository->findBySemestre($this->questionnaire->getSemestre());
            $dest = $this->questChoixEtudiantRepository->findByQuestionnaire($this->questionnaire);

            $ld = [];
            foreach ($dest as $d) {
                $ld[$d->getEtudiant()->getId()] = $d;
            }

            $liste = [];
            foreach ($etudiants as $etudiant) {
                $liste[$etudiant->getId()]['dest'] = $etudiant;
                if (array_key_exists($etudiant->getId(), $ld)) {
                    $liste[$etudiant->getId()]['choix'] = $ld[$etudiant->getId()];
                }
            }

            return $liste;
        }

        return [];
    }

    public function getListeDestinataire(): array
    {
        return $this->questChoixEtudiantRepository->findByQuestionnaire($this->questionnaire);
    }

    public function getNbDestinatairesRepondus(): int
    {
        return $this->questChoixEtudiantRepository->compteReponse($this->questionnaire);
    }

    public function getNbDestinataires(): int
    {
        return count($this->getListeDestinataire());
    }

    public function send(array $liste): void
    {
        foreach ($liste as $etu) {
            $etudiant = $this->etudiantRepository->find($etu);

            if (null !== $etudiant) {
                $questChoixEtudiant = new QuestChoixEtudiant();
                $questChoixEtudiant->setEtudiant($etudiant);
                $questChoixEtudiant->setQuestionnaire($this->questionnaire);
                $questChoixEtudiant->setCleQuestionnaire(Uuid::uuid4());
                $this->myMailer->initEmail();
                $this->myMailer->setTemplate('components/questionnaire/mails/envoi_questionnaire.html.twig',
                    ['questionnaire' => $this->questionnaire, 'questChoix' => $questChoixEtudiant]);
                $this->myMailer->sendMessage($etudiant->getMails(),
                    '[Questionnaire Qualité] ' . $this->questionnaire->getLibelle());
                $questChoixEtudiant->setDateEnvoi(Carbon::now());
                $this->entityManager->persist($questChoixEtudiant);
                $this->entityManager->flush();
            }
        }
        $this->questionnaire->setEnvoye(true);
        $this->entityManager->flush();
    }

    public function getChoixUser(string $uuid): ?QuestChoixInterface
    {
        $this->choixUser = $this->questChoixEtudiantRepository->findOneBy([
            'cleQuestionnaire' => $uuid,
            'questionnaire' => $this->questionnaire,
        ]);

        return $this->choixUser;
    }

    public function sendMailAccuse(QuestChoixInterface $choixUser, MailerFromTwig $myMailer): void
    {
        $myMailer->initEmail();
        $myMailer->setTemplate('mails/qualite-complete-etudiant.html.twig',
            ['questionnaire' => $this->questionnaire, 'user' => $choixUser]);
        $myMailer->sendMessage($choixUser->getEtudiant()->getMails(),
            'Accusé réception questionnaire ' . $this->questionnaire->getLibelle());

        $myMailer->initEmail();
        $myMailer->setTemplate('mails/qualite-complete-responsable.html.twig',
            ['questionnaire' => $this->questionnaire, 'etudiant' => $choixUser->getEtudiant()]);
        $myMailer->sendMessage($choixUser->getEtudiant()->getDiplome()->getOptResponsableQualite()->getMails(),
            'Accusé réception questionnaire ' . $this->questionnaire->getLibelle());
    }

    public function sauvegardeReponse(QuestChoixInterface $choixUser, string $cleReponse, string $cleQuestion): void
    {
        $this->abstractSauvegardeReponse($choixUser, $cleReponse, $cleQuestion, 'etudiant');
    }

    public function sauvegardeReponseTexte(QuestChoixInterface $choixUser, string $cleQuestion, string $value): void
    {
        $this->abstractSauvegardeReponseTexte($choixUser, $cleQuestion, 'etudiant', $value);
    }

    public function getReponses(): ReponsesUser
    {
        return $this->abstractGetReponses('etudiant');
    }

    public function getAllReponses(): ReponsesUser
    {
        return $this->abstractGetAllReponses('etudiant');
    }

    public function sendRelanceIndividuelle(int $choix): void
    {
        $data = $this->questChoixEtudiantRepository->findOneBy(
            [
                'questionnaire' => $this->questionnaire,
                'id' => $choix,
            ]
        );

        if ($data !== null) {
            $event = new QualiteRelanceEvent($this->questionnaire);
            $event->setUser($data);
            $this->eventDispatcher->dispatch($event, QualiteRelanceEvent::SEND_RELANCE);
        }
    }
}
