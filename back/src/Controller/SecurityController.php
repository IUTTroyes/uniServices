<?php

namespace App\Controller;

use App\Entity\ResetToken;
use App\Repository\EtudiantRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ResetTokenRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EtudiantRepository $etudiantRepository,
        private readonly PersonnelRepository $personnelRepository,
        private readonly ResetTokenRepository $resetTokenRepository,
    ) {

    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $dispatcher,
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Générez un token JWT pour l'utilisateur authentifié
        $token = $jwtManager->create($user);

        // ajouter l'ID de l'utilisateur au token
        $dispatcher->dispatch(new JWTCreatedEvent(['token' => $token], $user));

        return new JsonResponse([
            'token' => $token
        ]);
    }

    #[Route('/api/change_password', name: 'api_reset_pwd', methods: ['POST'])]
    public function changePassword(Request $request, MailerInterface $mailer): JsonResponse
    {
        $emailAddress = $request->getContent();

        // chercher un utilisateur par son email dans les champs mail_univ ou mail_perso des entités Etudiant et Personnel
        $user = $this->personnelRepository->findOneBy(['mailUniv' => $emailAddress])
            ?? $this->personnelRepository->findOneBy(['mailPerso' => $emailAddress])
            ?? $this->etudiantRepository->findOneBy(['mailUniv' => $emailAddress])
            ?? $this->etudiantRepository->findOneBy(['mailPerso' => $emailAddress]);

        // si on trouve l'utilisateur
        if ($user) {
            // Nettoyer les anciens tokens pour cet utilisateur
            $this->resetTokenRepository->removeExpiredTokens();

            // Générer un token de réinitialisation
            $resetToken = bin2hex(random_bytes(32));

            // Créer et sauvegarder le token en base
            $tokenEntity = new ResetToken();
            $tokenEntity->setToken($resetToken);

            // Associer le token à l'utilisateur (étudiant ou personnel)
            if ($user->getTypeUser() === 'etudiant') {
                $tokenEntity->setEtudiant($user);
            } else {
                $tokenEntity->setPersonnel($user);
            }

            $this->resetTokenRepository->save($tokenEntity, true);

            // Construire le mail de réinitialisation
            $resetUrl = sprintf(
                'http://localhost:3000/auth/reset-password/confirm?token=%s',
                $resetToken
            );

            // Déterminer l'adresse email à utiliser (mail universitaire en priorité, sinon mail personnel)
            $recipientEmail = $user->getMailUniv() ?? $user->getMailPerso();

            $email = (new TemplatedEmail())
                ->from('no-reply@univ-reims.fr')
                ->to($recipientEmail)
                ->subject('UniServices - Réinitialisation de mot de passe')
                ->htmlTemplate('emails/reset_password.html.twig')
                ->context(['resetUrl' => $resetUrl, 'user' => $user]);
            $mailer->send($email);

            return new JsonResponse(['message' => 'Un email de réinitialisation a été envoyé si l\'adresse existe dans notre système.'], JsonResponse::HTTP_OK);
        }

        // Pour des raisons de sécurité, ne pas indiquer si l'email existe ou non
        return new JsonResponse(['message' => 'Un email de réinitialisation a été envoyé si l\'adresse existe dans notre système.'], JsonResponse::HTTP_OK);
    }


    #[Route('/api/reset_password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Token et mot de passe requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $token = $data['token'];
        $password = $data['password'];

        // Rechercher le token dans la base de données
        $resetToken = $this->resetTokenRepository->findOneByToken($token);

        if (!$resetToken) {
            return new JsonResponse(['error' => 'Token invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifier si le token n'est pas expiré
        if ($resetToken->isExpired()) {
            $this->resetTokenRepository->remove($resetToken, true);
            return new JsonResponse(['error' => 'Token expiré'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer l'utilisateur associé au token
        $user = $resetToken->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Hasher le nouveau mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Sauvegarder le nouveau mot de passe
        $entityManager = $this->resetTokenRepository->getEntityManager();
        $entityManager->persist($user);

        // Supprimer le token
        $entityManager->remove($resetToken);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès'], JsonResponse::HTTP_OK);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
