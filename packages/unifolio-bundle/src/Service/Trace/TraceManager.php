<?php

namespace UnifolioBundle\Service\Trace;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceMedia;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\ConceptionIaEnum;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\RealisationIaEnum;
use UnifolioBundle\Enum\TypeActiviteEnum;
use UnifolioBundle\Enum\TypeMediaEnum;
use UnifolioBundle\Enum\PhaseCampagneEnum;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Service\Campagne\CampagneResolver;
use UnifolioBundle\Service\Grille\GrilleResolver;
use UnifolioBundle\Service\Media\MediaStorageInterface;
use UnifolioBundle\Service\Media\MediaValidationException;
use UnifolioBundle\Enum\TypeNotificationEnum;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use App\Notification\NotificationService;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;

/**
 * Cas d'usage des traces côté étudiant. Les droits sont vérifiés en amont (TraceVoter) ;
 * ce service garantit la cohérence métier et lève UnifolioValidationException.
 */
class TraceManager
{
    public const MEDIAS_MAX = 20;
    public const RATTACHEMENTS_MAX = 30;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UnifolioPerimetre $perimetre,
        private readonly ReferentielAnnee $referentiel,
        private readonly MediaStorageInterface $storage,
        private readonly GrilleResolver $grilles,
        private readonly CampagneResolver $campagnes,
        private readonly NotificationService $notifications,
        private readonly CampagneAffectationRepository $affectations,
    ) {
    }

    /** @param array<string, mixed> $donnees */
    public function creer(Etudiant $etudiant, array $donnees): Trace
    {
        // Par défaut : l'année de formation en cours de l'étudiant.
        $scolarite = $this->scolariteDe($etudiant, $donnees['scolarite'] ?? null)
            ?? $this->perimetre->getScolariteActive($etudiant);

        if ($scolarite !== null && !$this->perimetre->isScolariteEligible($scolarite)) {
            throw UnifolioValidationException::un('Cette année ne relève pas d\'un BUT.');
        }

        $trace = $this->garder(fn () => new Trace($etudiant, (string) ($donnees['titre'] ?? ''), $scolarite));
        $this->hydrater($trace, $donnees);

        $this->em->persist($trace);
        $this->em->flush();

        return $trace;
    }

    /** @param array<string, mixed> $donnees */
    public function modifier(Trace $trace, array $donnees): Trace
    {
        if (\array_key_exists('titre', $donnees)) {
            $this->garder(fn () => $trace->setTitre((string) $donnees['titre']));
        }
        if (\array_key_exists('scolarite', $donnees)) {
            $scolarite = $this->scolariteDe($trace->getEtudiant(), $donnees['scolarite']);
            if ($scolarite !== $trace->getScolarite() && !$trace->getRattachements()->isEmpty()) {
                throw UnifolioValidationException::un('Retirez les rattachements avant de changer l\'année de la trace.');
            }
            $trace->setScolarite($scolarite);
        }
        $this->hydrater($trace, $donnees);
        $this->em->flush();

        return $trace;
    }

    /** @param array<string, mixed> $d */
    private function hydrater(Trace $trace, array $d): void
    {
        if (\array_key_exists('contexte', $d)) {
            $trace->setContexte($d['contexte'] !== null ? (string) $d['contexte'] : null);
        }
        if (\array_key_exists('dateRealisation', $d)) {
            $trace->setDateRealisation($this->date($d['dateRealisation']));
        }
        if (\array_key_exists('typeActivite', $d)) {
            $trace->setTypeActivite($this->enumOuNull(TypeActiviteEnum::class, $d['typeActivite'], 'Type d\'activité inconnu.'));
        }
        if (\array_key_exists('conceptionIa', $d)) {
            $trace->setConceptionIa($this->enumOuNull(ConceptionIaEnum::class, $d['conceptionIa'], 'Déclaration IA (conception) invalide.') ?? ConceptionIaEnum::NON_RENSEIGNE);
        }
        if (\array_key_exists('realisationIa', $d)) {
            $trace->setRealisationIa($this->enumOuNull(RealisationIaEnum::class, $d['realisationIa'], 'Déclaration IA (réalisation) invalide.') ?? RealisationIaEnum::NON_RENSEIGNE);
        }
        if (\array_key_exists('usageIaDetail', $d)) {
            $trace->setUsageIaDetail($d['usageIaDetail'] !== null ? (string) $d['usageIaDetail'] : null);
        }
        if (\array_key_exists('tags', $d)) {
            $trace->setTags(\is_array($d['tags']) ? $d['tags'] : []);
        }
    }

    public function supprimer(Trace $trace): void
    {
        $fichiers = $this->fichiersDe($trace);
        $this->em->remove($trace);
        $this->em->flush();
        $this->effacer($fichiers);
    }

    public function deposer(Trace $trace): void
    {
        $this->verifierCalendrierDepot($trace);
        $erreurs = $trace->getErreursDepot();
        if ($erreurs !== []) {
            throw new UnifolioValidationException($erreurs);
        }
        $this->garder(fn () => $trace->deposer());
        $this->em->flush();

        // Les enseignants chargés de cet étudiant sont prévenus (tout de suite ou dans leur résumé, selon leur choix).
        $deposant = $trace->getEtudiant();
        foreach (array_unique(array_map(static fn ($p) => $p->getId(), $this->affectations->evaluateursDe($deposant))) as $id) {
            $evaluateur = $this->em->getReference(\App\Entity\Users\Personnel::class, $id);
            $this->notifications->notifier(
                $evaluateur,
                TypeNotificationEnum::TRACE_DEPOSEE->cle(),
                sprintf('%s a déposé la trace « %s ».', $deposant->getDisplay(), $trace->getTitre()),
                '/app/unifolio/evaluations/traces/'.$trace->getUuidString(),
            );
        }
    }

    public function retirerEnBrouillon(Trace $trace): void
    {
        $this->garder(fn () => $trace->retirerEnBrouillon());
        $this->em->flush();
    }

    /**
     * Dépôt encadré par la campagne de l'année : impossible une fois clôturée, et après la limite de dépôt
     * seules les traces dont une révision a été demandée peuvent être redéposées.
     */
    private function verifierCalendrierDepot(Trace $trace): void
    {
        $scolarite = $trace->getScolarite();
        $campagne = $scolarite !== null ? $this->campagnes->pourScolarite($scolarite) : null;
        if ($campagne === null) {
            return;
        }
        $phase = $campagne->getPhase();
        if ($phase === PhaseCampagneEnum::CLOTUREE) {
            throw UnifolioValidationException::un(sprintf('La campagne « %s » est clôturée : le dépôt n\'est plus possible.', $campagne->getLibelle()));
        }
        if ($phase === PhaseCampagneEnum::EVALUATION && $trace->getStatut() !== StatutTraceEnum::A_REVISER) {
            throw UnifolioValidationException::un(sprintf('La date limite de dépôt de la campagne « %s » est dépassée.', $campagne->getLibelle()));
        }
    }

    // ---------- Médias ----------

    public function ajouterFichier(Trace $trace, UploadedFile $fichier, ?TypeMediaEnum $type = null, ?string $legende = null): TraceMedia
    {
        $this->verifierPlace($trace);
        try {
            $stocke = $this->storage->store($fichier, $type);
        } catch (MediaValidationException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }

        try {
            $media = TraceMedia::depuisFichier($trace, $stocke->type, $stocke)->setLegende($legende);
            $trace->addMedia($media);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->storage->delete($stocke->chemin);
            throw $e;
        }

        return $media;
    }

    public function ajouterUrl(Trace $trace, TypeMediaEnum $type, string $url, ?string $legende = null): TraceMedia
    {
        $this->verifierPlace($trace);
        $media = $this->garder(fn () => TraceMedia::depuisUrl($trace, $type, $url)->setLegende($legende));
        $trace->addMedia($media);
        $this->em->flush();

        return $media;
    }

    public function ajouterTexte(Trace $trace, TypeMediaEnum $type, string $contenu, ?string $langage = null, ?string $legende = null): TraceMedia
    {
        $this->verifierPlace($trace);
        $media = $this->garder(fn () => TraceMedia::depuisTexte($trace, $type, $contenu, $langage)->setLegende($legende));
        $trace->addMedia($media);
        $this->em->flush();

        return $media;
    }

    /** @param array<string, mixed> $d */
    public function modifierMedia(TraceMedia $media, array $d): TraceMedia
    {
        if (isset($d['type'])) {
            $type = TypeMediaEnum::tryFrom((string) $d['type']) ?? throw UnifolioValidationException::un('Type de preuve inconnu.');
            $this->garder(fn () => $media->changerType($type));
        }
        if (\array_key_exists('url', $d) && $media->getUrl() !== null) {
            $this->garder(fn () => $media->changerUrl((string) $d['url']));
        }
        if (\array_key_exists('langage', $d)) {
            $media->setLangage($d['langage'] !== null ? (string) $d['langage'] : null);
        }
        if (\array_key_exists('legende', $d)) {
            $media->setLegende($d['legende'] !== null ? (string) $d['legende'] : null);
        }
        if (\array_key_exists('contenu', $d)) {
            $this->garder(fn () => $media->setContenu((string) $d['contenu']));
        }
        $this->em->flush();

        return $media;
    }

    public function supprimerMedia(Trace $trace, TraceMedia $media): void
    {
        $chemin = $media->getChemin();
        $trace->removeMedia($media);
        $this->em->flush();
        if ($chemin !== null) {
            $this->effacer([$chemin]);
        }
    }

    /** @param int[] $ids */
    public function reordonnerMedias(Trace $trace, array $ids): void
    {
        $trace->reordonnerMedias($ids);
        $this->em->flush();
    }

    // ---------- Rattachements ----------

    /**
     * Remplace les rattachements de la trace.
     *
     * @param list<array{type?: string, id?: int|string, argumentaire?: ?string}> $demandes
     */
    public function definirRattachements(Trace $trace, array $demandes): void
    {
        $scolarite = $trace->getScolarite();
        if ($scolarite === null && $demandes !== []) {
            throw UnifolioValidationException::un('Choisissez l\'année de BUT de la trace avant de la rattacher au référentiel.');
        }
        if (\count($demandes) > self::RATTACHEMENTS_MAX) {
            throw UnifolioValidationException::un(sprintf('Une trace ne peut pas avoir plus de %d rattachements.', self::RATTACHEMENTS_MAX));
        }

        // D5 : la formation impose le niveau du référentiel sur lequel on rattache (s'il est paramétré).
        $imposee = $scolarite !== null ? $this->grilles->granularitePourScolarite($scolarite) : null;

        $rattachements = [];
        foreach ($demandes as $demande) {
            $granularite = GranulariteEnum::tryFrom((string) ($demande['type'] ?? ''));
            if ($imposee !== null && $granularite !== $imposee) {
                throw UnifolioValidationException::un(sprintf(
                    'Votre formation rattache les traces au niveau « %s » uniquement.',
                    $imposee->getLibelle()
                ));
            }
            $id = (int) ($demande['id'] ?? 0);
            $argumentaire = isset($demande['argumentaire']) ? (string) $demande['argumentaire'] : null;

            $cible = match ($granularite) {
                GranulariteEnum::COMPETENCE => $this->em->find(ApcCompetence::class, $id),
                GranulariteEnum::NIVEAU => $this->em->find(ApcNiveau::class, $id),
                GranulariteEnum::AC => $this->em->find(ApcApprentissageCritique::class, $id),
                null => null,
            };
            if ($cible === null || !$this->referentiel->contient($scolarite, $cible)) {
                throw UnifolioValidationException::un('Un des éléments choisis ne fait pas partie du référentiel de cette année.');
            }

            $rattachements[] = match (true) {
                $cible instanceof ApcCompetence => TraceRattachement::versCompetence($trace, $cible, $argumentaire),
                $cible instanceof ApcNiveau => TraceRattachement::versNiveau($trace, $cible, $argumentaire),
                default => TraceRattachement::versApprentissageCritique($trace, $cible, $argumentaire),
            };
        }

        $trace->remplacerRattachements($rattachements);
        $this->em->flush();
    }

    // ---------- Outils ----------

    private function scolariteDe(Etudiant $etudiant, mixed $id): ?EtudiantScolarite
    {
        if ($id === null || $id === '') {
            return null;
        }
        $scolarite = $this->em->find(EtudiantScolarite::class, (int) $id);
        if ($scolarite === null || $scolarite->getEtudiant()?->getId() !== $etudiant->getId()) {
            throw UnifolioValidationException::un('Année de BUT inconnue.');
        }
        if (!$this->perimetre->isScolariteEligible($scolarite)) {
            throw UnifolioValidationException::un('Cette année ne relève pas d\'un BUT.');
        }

        return $scolarite;
    }

    private function verifierPlace(Trace $trace): void
    {
        if ($trace->getMedias()->count() >= self::MEDIAS_MAX) {
            throw UnifolioValidationException::un(sprintf('Une trace ne peut pas contenir plus de %d preuves.', self::MEDIAS_MAX));
        }
    }

    private function date(mixed $valeur): ?\DateTimeImmutable
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', substr((string) $valeur, 0, 10));
        if ($date === false) {
            throw UnifolioValidationException::un('Date de réalisation invalide (format attendu : AAAA-MM-JJ).');
        }

        return $date;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enum
     *
     * @return T|null
     */
    private function enumOuNull(string $enum, mixed $valeur, string $erreur): ?\BackedEnum
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }
        $backing = (new \ReflectionEnum($enum))->getBackingType()?->getName() === 'int' ? (int) $valeur : (string) $valeur;

        return $enum::tryFrom($backing) ?? throw UnifolioValidationException::un($erreur);
    }

    /**
     * Convertit les erreurs de règle métier des entités en erreurs présentables.
     *
     * @template T
     *
     * @param callable(): T $fn
     *
     * @return T
     */
    private function garder(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\InvalidArgumentException|\DomainException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
    }

    /** @return string[] */
    private function fichiersDe(Trace $trace): array
    {
        $chemins = [];
        foreach ($trace->getMedias() as $media) {
            if ($media->getChemin() !== null) {
                $chemins[] = $media->getChemin();
            }
        }

        return $chemins;
    }

    /** @param string[] $chemins */
    private function effacer(array $chemins): void
    {
        foreach ($chemins as $chemin) {
            try {
                $this->storage->delete($chemin);
            } catch (\Throwable) {
                // Fichier orphelin : sans conséquence fonctionnelle, nettoyable plus tard.
            }
        }
    }
}
