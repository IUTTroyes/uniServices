<?php

namespace App\Controller;

use App\Entity\Users\Personnel;
use App\Security\Permission\PermissionResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MeSecurityContextController extends AbstractController
{
    public function __construct(
        private readonly PermissionResolver $permissionResolver,
    ) {}

    #[Route('/api/me/security-context', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var Personnel $user */
        $user = $this->getUser();

        $departments = [];

        foreach ($user->getDepartementPersonnels() as $personnelDepartement) {
            $departement = $personnelDepartement->getDepartement();

            $roles = $personnelDepartement->getPermissions();
            $resolvedRoles = $this->permissionResolver->resolve($roles);

            $departments[] = [
                'id' => $departement->getId(),
                'code' => $departement->getLibelle(),
                'label' => $departement->getLibelle(),
                'packages' => $personnelDepartement->getPackages(),
                'permissions' => $resolvedRoles,
            ];
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'displayName' => $user->getDisplay(),
                'type' => 'personnel',
            ],
            'departments' => $departments,
            'currentDepartment' => $departments[0]['code'] ?? null,
        ]);
    }
}
