<?php

namespace UnifolioBundle\Service\Publication;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPublication;
use UnifolioBundle\Entity\TraceMedia;
use UnifolioBundle\Enum\StatutRenduEnum;
use UnifolioBundle\Enum\TypeNotificationEnum;
use UnifolioBundle\Enum\VisibilitePortfolioEnum;
use UnifolioBundle\Service\Notification\NotificationManager;
use UnifolioBundle\Message\GenererPublication;
use UnifolioBundle\Repository\PortfolioPublicationRepository;
use UnifolioBundle\Service\Media\MediaStorageInterface;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/**
 * Cycle de vie des publications : figer, générer (D2), mettre en ligne, retirer, modérer.
 * La génération passe par Messenger : synchrone tant qu'aucun transport asynchrone n'est configuré pour GenererPublication.
 */
class PublicationManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PortfolioPublicationRepository $publications,
        private readonly PublicationInstantane $instantane,
        private readonly PublicationStorage $stockage,
        private readonly RendererClient $rendu,
        private readonly MediaStorageInterface $medias,
        private readonly LogoManager $logos,
        private readonly NotificationManager $notifications,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
        private readonly int $unifolioPublicationsConservees,
    ) {
    }

    /**
     * Fige le portfolio et lance la génération. Refuse un portfolio privé (rien à partager) ou incomplet.
     */
    public function publier(Portfolio $portfolio): PortfolioPublication
    {
        if ($portfolio->getVisibilite() === VisibilitePortfolioEnum::PRIVE) {
            throw UnifolioValidationException::un('Choisissez d\'abord qui peut voir ce portfolio : un portfolio privé n\'est pas publié.');
        }
        if (!$this->rendu->disponible()) {
            throw UnifolioValidationException::un('La publication est momentanément indisponible (service de rendu non installé).');
        }
        $instantane = $this->instantane->construire($portfolio);

        $publication = new PortfolioPublication($portfolio, $this->publications->prochainNumero($portfolio), $instantane);
        $this->em->persist($publication);
        $this->em->flush();

        $this->bus->dispatch(new GenererPublication($publication->getUuidString()));
        $this->em->refresh($publication);

        if ($publication->getStatut() === StatutRenduEnum::ERREUR) {
            throw UnifolioValidationException::un('La génération du portfolio a échoué : '.$publication->getErreur());
        }

        return $publication;
    }

    /** Génère les fichiers puis, en cas de succès seulement, bascule la version active (l'ancienne reste en ligne en cas d'échec). */
    public function generer(PortfolioPublication $publication): void
    {
        if ($publication->getStatut() !== StatutRenduEnum::EN_ATTENTE) {
            return;
        }
        try {
            $fichiers = $this->ecrireFichiers($publication);
            $publication->marquerGeneree($fichiers);
            foreach ($this->publications->historique($publication->getPortfolio()) as $autre) {
                if ($autre !== $publication) {
                    $autre->desactiver();
                }
            }
            $publication->activer();
        } catch (\Throwable $e) {
            $this->logger->error('Échec de génération d\'une publication UniFolio', ['publication' => $publication->getUuidString(), 'exception' => $e]);
            $this->stockage->supprimer($publication);
            $publication->marquerEnErreur($e->getMessage());
        }
        $this->em->flush();
        $this->elaguer($publication->getPortfolio());
    }

    /** Retire le portfolio du public (l'historique et les exports restent disponibles pour l'étudiant). */
    public function depublier(Portfolio $portfolio): void
    {
        foreach ($this->publications->historique($portfolio) as $publication) {
            $publication->desactiver();
        }
        $this->em->flush();
    }

    public function moderer(PortfolioPublication $publication, string $motif): void
    {
        try {
            $publication->moderer($motif);
        } catch (\InvalidArgumentException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
        $this->em->flush();

        $portfolio = $publication->getPortfolio();
        $this->notifications->notifier(
            $portfolio->getEtudiant(),
            TypeNotificationEnum::PUBLICATION_MODEREE,
            sprintf('Votre portfolio « %s » a été retiré par la modération : %s', $portfolio->getTitre(), $publication->getMotifModeration()),
            '/app/unifolio/portfolios/'.$portfolio->getUuidString(),
        );
    }

    public function retablir(PortfolioPublication $publication): void
    {
        $publication->retablir();
        $this->em->flush();
    }

    /** Régénère une publication existante à partir de son instantané (ex. après évolution du rendu). */
    public function regenerer(PortfolioPublication $publication): void
    {
        $this->ecrireFichiersDe($publication);
        $this->em->flush();
    }

    /**
     * @return list<string>
     */
    private function ecrireFichiers(PortfolioPublication $publication): array
    {
        $instantane = $publication->getInstantane();
        $portfolio = $publication->getPortfolio();
        $pages = $this->rendu->rendre($instantane);

        $ecrits = [];
        foreach ($pages as $fichier => $html) {
            $this->stockage->ecrire($publication, $fichier, $html);
            $ecrits[] = $fichier;
        }
        foreach (PublicationInstantane::medias($instantane) as $m) {
            $media = $this->em->find(TraceMedia::class, $m['mediaId']);
            // Défense en profondeur : un média n'est copié que s'il appartient bien à l'auteur du portfolio.
            if ($media === null || !$media->hasFichier() || !$media->getTrace()->appartientA($portfolio->getEtudiant())) {
                continue;
            }
            $source = $this->medias->cheminAbsolu((string) $media->getChemin());
            if (is_file($source)) {
                $this->stockage->copier($publication, $m['destination'], $source);
                $ecrits[] = $m['destination'];
            }
        }
        $logo = $instantane['portfolio']['logo']['fichier'] ?? null;
        $cheminLogo = $logo !== null ? $this->logos->chemin($portfolio) : null;
        if ($logo !== null && $cheminLogo !== null && LogoManager::nomPublie($portfolio) === $logo) {
            $this->stockage->copier($publication, $logo, $cheminLogo);
            $ecrits[] = $logo;
        }
        $ecrits[] = PublicationStorage::FEUILLE;

        return array_values(array_unique($ecrits));
    }

    private function ecrireFichiersDe(PortfolioPublication $publication): void
    {
        $publication->marquerGeneree($this->ecrireFichiers($publication));
    }

    /** Conserve les N dernières publications ; supprime fichiers et lignes des plus anciennes (jamais l'active). */
    private function elaguer(Portfolio $portfolio): void
    {
        $historique = $this->publications->historique($portfolio);
        foreach (\array_slice($historique, $this->unifolioPublicationsConservees) as $ancienne) {
            if ($ancienne->isActive()) {
                continue;
            }
            $this->stockage->supprimer($ancienne);
            $this->em->remove($ancienne);
        }
        $this->em->flush();
    }
}
