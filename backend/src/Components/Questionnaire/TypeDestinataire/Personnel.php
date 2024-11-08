<?php
/*
 * Copyright (c) 2023. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Components/Questionnaire/TypeDestinataire/Personnel.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 08/11/2023 17:46
 */

namespace App\Components\Questionnaire\TypeDestinataire;

use App\Classes\Mail\MailerFromTwig;
use App\Components\Questionnaire\DTO\ReponsesUser;
use App\Components\Questionnaire\Interfaces\QuestChoixInterface;
use App\Components\Questionnaire\Interfaces\TypeDestinataireInterface;
use App\Entity\QuestChoixPersonnel;
use App\Event\QualiteRelanceEvent;
use App\Repository\PersonnelRepository;
use App\Repository\QuestChoixPersonnelRepository;
use App\Repository\QuestChoixRepository;
use App\Repository\QuestQuestionRepository;
use App\Repository\QuestReponseRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Personnel extends AbstractTypeDestinataire implements TypeDestinataireInterface
{
    public const LABEL = 'personnel';
    public const ENTITY = QuestChoixPersonnel::class;

    public function __construct(
        QuestChoixRepository $questChoixRepository,
        QuestQuestionRepository $questQuestionRepository,
        QuestReponseRepository $questReponseRepository,
        protected MailerFromTwig $myMailer,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager,
        protected QuestChoixPersonnelRepository $questChoixPersonnelRepository,
        protected PersonnelRepository $personnelRepository
    ) {
        parent::__construct($eventDispatcher, $entityManager, $questChoixRepository, $questQuestionRepository,
            $questReponseRepository);
    }

    public function getListe(): array
    {
        if ($this->questionnaire->getDepartement() !== null) {
            $personnels = $this->personnelRepository->findByDepartement($this->questionnaire->getDepartement());
        } else {
            $personnels = $this->personnelRepository->findAll();
        }
        $dest = $this->questChoixPersonnelRepository->findByQuestionnaire($this->questionnaire);

        $ld = [];
        foreach ($dest as $d) {
            $ld[$d->getPersonnel()->getId()] = $d;
        }

        $liste = [];
        foreach ($personnels as $personnel) {
            $liste[$personnel->getId()]['dest'] = $personnel;
            if (array_key_exists($personnel->getId(), $ld)) {
                $liste[$personnel->getId()]['choix'] = $ld[$personnel->getId()];
            }
        }

        return $liste;
    }

    public function getListeDestinataire(): array
    {
        return $this->questChoixPersonnelRepository->findByQuestionnaire($this->questionnaire);
    }

    public function getNbDestinataires(): int
    {
        return count($this->getListeDestinataire());
    }

    public function getNbDestinatairesRepondus(): int
    {
        return $this->questChoixPersonnelRepository->compteReponse($this->questionnaire);
    }

    public function send(array $liste): void
    {
        foreach ($liste as $pers) {
            $personnel = $this->personnelRepository->find($pers);

            if (null !== $personnel) {
                $questChoixPersonnel = new QuestChoixPersonnel();
                $questChoixPersonnel->setPersonnel($personnel);
                $questChoixPersonnel->setQuestionnaire($this->questionnaire);
                $questChoixPersonnel->setCleQuestionnaire(Uuid::uuid4());
                $this->myMailer->initEmail();
                $this->myMailer->setTemplate('components/questionnaire/mails/envoi_questionnaire.html.twig',
                    ['questionnaire' => $this->questionnaire, 'questChoix' => $questChoixPersonnel]);
                $this->myMailer->sendMessage($personnel->getMails(),
                    '[Questionnaire Qualité] ' . $this->questionnaire->getLibelle());
                $questChoixPersonnel->setDateEnvoi(Carbon::now());
                $this->entityManager->persist($questChoixPersonnel);
                $this->entityManager->flush();
            }
        }
        $this->questionnaire->setEnvoye(true);
        $this->entityManager->flush();
    }

    public function getChoixUser(string $uuid): ?QuestChoixInterface
    {
        $this->choixUser = $this->questChoixPersonnelRepository->findOneBy([
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
        $myMailer->sendMessage($choixUser->getPersonnel()->getMails(),
            'Accusé réception questionnaire ' . $this->questionnaire->getLibelle());

        $myMailer->initEmail();
        $myMailer->setTemplate('mails/qualite-complete-responsable.html.twig',
            ['questionnaire' => $this->questionnaire, 'etudiant' => $choixUser->getPersonnel()]);
        $myMailer->sendMessage($choixUser->getPersonnel()->getDiplome()->getOptResponsableQualite()->getMails(),
            'Accusé réception questionnaire ' . $this->questionnaire->getLibelle());
    }

    public function sauvegardeReponse(QuestChoixInterface $choixUser, string $cleReponse, string $cleQuestion): void
    {
        $this->abstractSauvegardeReponse($choixUser, $cleReponse, $cleQuestion, 'personnel');
    }

    public function sauvegardeReponseTexte(QuestChoixInterface $choixUser, string $cleQuestion, string $value): void
    {
        $this->abstractSauvegardeReponseTexte($choixUser, $cleQuestion, 'personnel', $value);
    }

    public function getReponses(): ReponsesUser
    {
        return $this->abstractGetReponses('personnel');
    }

    public function sendRelanceIndividuelle(int $choix): void
    {
        $data = $this->questChoixPersonnelRepository->findOneBy(
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
