<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/EventSubscriber/AuthenticationSuccessSubscriber.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 16/05/2022 12:13
 */

namespace App\EventSubscriber;

use App\Entity\Departement;
use App\Entity\Etudiant;
use App\Repository\DepartementRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    use TargetPathTrait;

    public function __construct(private readonly DepartementRepository $departementRepository, private readonly RequestStack $session, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onSecurityAuthenticationSuccess(AuthenticationEvent $event): RedirectResponse
    {
        $target = $this->getTargetPath($this->session->getSession(), $event->getAuthenticationToken()->getFirewallName());
        $user = $event->getAuthenticationToken()->getUser();

        if (null === $user) {
            return new RedirectResponse($this->urlGenerator->generate('security_login'));
        }

        if ($user instanceof Etudiant) {
            return new RedirectResponse($target ?? $this->urlGenerator->generate('default_homepage'));
        }

        $rolesTab = $event->getAuthenticationToken()->getRoleNames();
        if (in_array('ROLE_SUPER_ADMIN', $rolesTab, true) || in_array('ROLE_ADMINISTRATIF', $rolesTab,
                true) || in_array('ROLE_SCOLARITE', $rolesTab, true) || in_array('ROLE_QUALITE', $rolesTab,
                true) || in_array('ROLE_RH', $rolesTab, true)) {
            // c'est un super administrateur : on le rediriger vers l'espace super-admin
            return new RedirectResponse($this->urlGenerator->generate('super_admin_homepage'));
        }

        if (in_array('ROLE_PERMANENT', $rolesTab, true)) {
            // si pas de département
            // pas de departement par défaut, ou pas de departement du tout.
            $departements = $this->departementRepository->findDepartementPersonnel($user);
            if (0 === count($departements)) {
                return new RedirectResponse($this->urlGenerator->generate('security_login',
                    ['message' => 'pas-departement']));
            }

            // init de la session departement
            $departements = $this->departementRepository->findDepartementPersonnelDefaut($user);

            if (0 === count($departements)) {
                return new RedirectResponse($this->urlGenerator->generate('security_choix_departement'));
            }

            if (1 === count($departements)) {
                /** @var Departement $departement */
                $departement = $departements[0];
                $this->session->getSession()->set('departement', $departement->getUuid()); // on sauvegarde

                return new RedirectResponse($target ?? $this->urlGenerator->generate('default_homepage'));
            }

            // donc il y a une departement, mais pas une par défaut.
            return new RedirectResponse($this->urlGenerator->generate('security_choix_departement'));
        }

        // c'est aucun des rôles...
        return new RedirectResponse($this->urlGenerator->generate('security_login',
            ['message' => 'erreur_role']));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'security.authentication.success' => 'onSecurityAuthenticationSuccess',
        ];
    }
}
