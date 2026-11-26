<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Entity\ResetToken;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Repository\EtablissementRepository;
use App\Repository\EtudiantRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ResetTokenRepository;
use App\Service\Email\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Demande de mot de passe oublié.
 *
 * - Un compte qui a un mot de passe reçoit un lien de réinitialisation.
 * - Un compte qui n'en a pas (authentification universitaire, CAS) reçoit l'adresse configurée de l'établissement.
 *
 * L'appelant répond toujours la même chose : rien ne doit révéler l'existence ni le type d'un compte.
 */
class MotDePasseOublie
{
    public function __construct(
        private readonly PersonnelRepository $personnels,
        private readonly EtudiantRepository $etudiants,
        private readonly ResetTokenRepository $jetons,
        private readonly EtablissementRepository $etablissements,
        private readonly EmailService $emails,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(default::URL_FRONTEND)%')]
        private readonly ?string $urlFrontend = null,
    ) {
    }

    public function demander(string $adresse): void
    {
        $adresse = trim($adresse);
        if (filter_var($adresse, \FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $compte = $this->personnels->findOneBy(['mailUniv' => $adresse])
            ?? $this->personnels->findOneBy(['mailPerso' => $adresse])
            ?? $this->etudiants->findOneBy(['mailUniv' => $adresse])
            ?? $this->etudiants->findOneBy(['mailPerso' => $adresse]);
        if ($compte === null) {
            return;
        }

        // L'adresse saisie est celle du compte : c'est là que la personne attend la réponse.
        try {
            if ((string) $compte->getPassword() !== '') {
                $this->envoyerLien($compte, $adresse);
            } else {
                $this->envoyerAdresseUniversitaire($compte, $adresse);
            }
        } catch (\Throwable $e) {
            // Une erreur d'envoi ne doit pas distinguer ce cas d'une adresse inconnue.
            $this->logger->error('Mot de passe oublié : envoi impossible.', ['erreur' => $e->getMessage()]);
        }
    }

    private function envoyerLien(Personnel|Etudiant $compte, string $adresse): void
    {
        // Un seul jeton actif par compte ; seule son empreinte est enregistrée.
        $this->jetons->removeExpiredTokens();
        $this->jetons->removeTokensForUser($compte);

        $secret = bin2hex(random_bytes(32));
        $jeton = (new ResetToken())->setToken(hash('sha256', $secret));
        $compte instanceof Etudiant ? $jeton->setEtudiant($compte) : $jeton->setPersonnel($compte);
        $this->jetons->save($jeton, true);

        $this->emails->send('core.reset_password', $adresse, [
            'user' => $compte,
            'resetUrl' => rtrim((string) $this->urlFrontend, '/').'/app/auth/reset-password/confirm?token='.$secret,
        ]);
    }

    private function envoyerAdresseUniversitaire(Personnel|Etudiant $compte, string $adresse): void
    {
        $url = $this->etablissements->findSingleEtablissement()?->getUrlMotDePasseCas();
        if ($url === null) {
            return;
        }

        $this->emails->send('core.mot_de_passe_universitaire', $adresse, ['user' => $compte, 'casUrl' => $url]);
    }
}
