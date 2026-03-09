<?php

namespace App\Controller;

use App\ApiDto\Security\ResetPasswordDto;
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
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
            // Message générique pour ne pas révéler si l'utilisateur existe ou non
            return new JsonResponse(['error' => 'Identifiants invalides'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Générez un token JWT pour l'utilisateur authentifié
        $token = $jwtManager->create($user);

        // ajouter l'ID de l'utilisateur au token
        $dispatcher->dispatch(new JWTCreatedEvent(['token' => $token], $user));

        $response = new JsonResponse([
            'token' => $token
        ]);

        // Ajouter des en-têtes de sécurité
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[Route('/api/change_password', name: 'api_reset_pwd', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function changePassword(
        Request $request,
        MailerInterface $mailer,
        RateLimiterFactory $passwordResetLimiter
    ): JsonResponse
    {
        // Rate limiting : max 3 demandes par IP par heure
        $limiter = $passwordResetLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            return new JsonResponse(
                ['message' => 'Trop de demandes. Veuillez réessayer plus tard.'],
                JsonResponse::HTTP_TOO_MANY_REQUESTS
            );
        }

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

            // Supprimer les anciens tokens de cet utilisateur (un seul token actif à la fois)
            $this->resetTokenRepository->removeTokensForUser($user);

            // Générer un token de réinitialisation
            $resetToken = bin2hex(random_bytes(32));

            // Stocker le HASH du token (pas le token en clair)
            // Ainsi, si la BDD est compromise, les tokens ne peuvent pas être utilisés
            $hashedToken = hash('sha256', $resetToken);

            // Créer et sauvegarder le token en base
            $tokenEntity = new ResetToken();
            $tokenEntity->setToken($hashedToken);

            // Associer le token à l'utilisateur (étudiant ou personnel)
            if ($user->getTypeUser() === 'etudiant') {
                $tokenEntity->setEtudiant($user);
            } else {
                $tokenEntity->setPersonnel($user);
            }

            $this->resetTokenRepository->save($tokenEntity, true);

            $url_front = $_ENV['URL_FRONTEND'];
            // Construire le mail de réinitialisation
            $resetUrl = sprintf(
                $url_front.'/auth/reset-password/confirm?token=%s',
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
    #[IsGranted('PUBLIC_ACCESS')]
    public function resetPassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        RateLimiterFactory $passwordResetLimiter
    ): JsonResponse
    {
        // Rate limiting : protection contre le brute force
        $limiter = $passwordResetLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            return new JsonResponse(
                ['error' => 'Trop de tentatives. Veuillez réessayer plus tard.'],
                JsonResponse::HTTP_TOO_MANY_REQUESTS
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['token']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Token et mot de passe requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Créer et valider le DTO
        $resetPasswordDto = new ResetPasswordDto();
        $resetPasswordDto->token = $data['token'];
        $resetPasswordDto->password = $data['password'];

        $errors = $validator->validate($resetPasswordDto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $token = $data['token'];
        $password = $data['password'];

        // Hasher le token reçu pour le comparer avec celui stocké en base
        $hashedToken = hash('sha256', $token);

        // Rechercher le token dans la base de données
        $resetToken = $this->resetTokenRepository->findOneByTokenSecure($hashedToken);

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
