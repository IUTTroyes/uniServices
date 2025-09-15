<?php

namespace App\Controller;

use App\Repository\EtudiantRepository;
use App\Repository\PersonnelRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EtudiantRepository $etudiantRepository,
        private readonly PersonnelRepository $personnelRepository,
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
            // Générer un token de réinitialisation (exemple simple, à sécuriser en prod)
            $resetToken = bin2hex(random_bytes(32));

            // Ici, il faudrait sauvegarder ce token et sa date d'expiration en base, lié à l'utilisateur

            // Construire le mail de réinitialisation
            $resetUrl = sprintf(
                '%s/reset-password?token=%s',
                $request->getSchemeAndHttpHost(),
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


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
