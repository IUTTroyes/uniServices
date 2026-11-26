<?php

namespace App\Controller;

use App\Service\Security\MotDePasseOublie;
use App\ApiDto\Security\ResetPasswordDto;
use App\Repository\EtudiantRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ResetTokenRepository;
use App\Security\DepartmentPermissionChecker;
use App\Security\PermissionRegistry;
use App\Security\PermissionResolver;
use App\Security\UserEffectivePermissionService;
use App\Security\UserPackageResolver;
use App\Entity\Users\Personnel;
use App\Entity\Users\Etudiant;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[\App\AnneeUniversitaire\ContexteAnnuel('independant', 'Identité, paramétrage partagé ou données sans année universitaire.')]
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
            // HelpdeskMessage générique pour ne pas révéler si l'utilisateur existe ou non
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
        MotDePasseOublie $motDePasseOublie,
        #[Autowire(service: 'limiter.password_reset')] RateLimiterFactory $passwordResetLimiter
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

        $motDePasseOublie->demander($request->getContent());

        // Pour des raisons de sécurité, ne pas indiquer si l'email existe ou non
        return new JsonResponse(['message' => 'Un email a été envoyé si l\'adresse existe dans notre système.'], JsonResponse::HTTP_OK);
    }


    #[Route('/api/reset_password', name: 'api_reset_password', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function resetPassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        #[Autowire(service: 'limiter.password_reset')] RateLimiterFactory $passwordResetLimiter
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

    #[Route(path: '/api/me/security-context', name: 'api_me_security_context', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getSecurityContext(
        DepartmentPermissionChecker $checker,
        StructureDepartementPersonnelRepository $sdpRepo,
        PermissionResolver $resolver,
        UserEffectivePermissionService $effectivePermissionService,
        UserPackageResolver $packageResolver
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Non authentifié'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $departmentsData = [];
        $currentDepartment = null;
        $activePackages = [];
        $resolvedPermissions = [];

        if ($user instanceof Personnel) {
            $dps = $sdpRepo->findBy(['personnel' => $user]);
            foreach ($dps as $dp) {
                $dept = $dp->getDepartement();
                if (!$dept) {
                    continue;
                }

                $departmentsData[] = [
                    'id' => $dept->getId(),
                    'libelle' => $dept->getLibelle(),
                    'couleur' => $dept->getCouleur(),
                    'defaut' => $dp->isDefaut(),
                ];

                if ($dp->isDefaut()) {
                    $currentDepartment = [
                        'id' => $dept->getId(),
                        'libelle' => $dept->getLibelle(),
                        'couleur' => $dept->getCouleur(),
                    ];
                    $resolvedPermissions = $resolver->resolveForDepartment($dp);
                }
            }

            if (null === $currentDepartment && !empty($dps)) {
                $dp = $dps[0];
                $dept = $dp->getDepartement();
                if ($dept) {
                    $currentDepartment = [
                        'id' => $dept->getId(),
                        'libelle' => $dept->getLibelle(),
                        'couleur' => $dept->getCouleur(),
                    ];
                    $resolvedPermissions = $resolver->resolveForDepartment($dp);
                }
            }
            $activePackages = $packageResolver->packages($user);
        } elseif ($user instanceof Etudiant) {
            $studentDept = $checker->getStudentDepartment($user);
            if ($studentDept) {
                $currentDepartment = [
                    'id' => $studentDept->getId(),
                    'libelle' => $studentDept->getLibelle(),
                    'couleur' => $studentDept->getCouleur(),
                ];
                $departmentsData[] = $currentDepartment;
            }

            $activePackages = $packageResolver->packages($user);
            $resolvedPermissions = [];
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'prenom' => $user->getPrenom(),
                'nom' => $user->getNom(),
                'email' => $user->getMailUniv() ?? $user->getMailPerso(),
                'type' => $user instanceof Personnel ? 'personnels' : 'etudiants',
                'roles' => $effectivePermissionService->getEffectivePermissions($user),
                'applications' => $activePackages,
            ],
            'currentDepartment' => $currentDepartment,
            'departments' => $departmentsData,
            'packages' => $activePackages,
            'permissions' => $resolvedPermissions,
            'scopedPermissions' => $effectivePermissionService->getScopedPermissions($user),
        ]);
    }

    #[Route(path: '/api/security/permissions', name: 'api_security_permissions', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getPermissions(PermissionRegistry $registry): JsonResponse
    {
        $grouped = [];
        foreach ($registry->getPermissions() as $definition) {
            $grouped[$definition->getPackage()][] = $definition->toArray();
        }
        return new JsonResponse($grouped);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
