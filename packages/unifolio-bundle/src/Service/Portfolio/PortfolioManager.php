<?php

namespace UnifolioBundle\Service\Portfolio;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPage;
use UnifolioBundle\Enum\PhaseCampagneEnum;
use UnifolioBundle\Enum\TypePortfolioEnum;
use UnifolioBundle\Repository\PortfolioRepository;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Service\Campagne\CampagneResolver;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Cas d'usage des portfolios : création du portfolio académique, pages, blocs. */
class PortfolioManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PortfolioRepository $portfolios,
        private readonly TraceRepository $traces,
        private readonly BlocValidator $validateur,
        private readonly CampagneResolver $campagnes,
        private readonly UnifolioPerimetre $perimetre,
        private readonly ReferentielAnnee $referentiel,
    ) {
    }

    // ---------- Création automatique du portfolio académique ----------

    /**
     * Crée les portfolios académiques manquants : un par année de BUT de l'étudiant qui relève d'une campagne.
     *
     * @return list<Portfolio> les portfolios créés
     */
    public function assurerAcademiques(Etudiant $etudiant): array
    {
        $crees = [];
        foreach ($etudiant->getScolarites() as $scolarite) {
            if (!$this->perimetre->isScolariteEligible($scolarite)) {
                continue;
            }
            $campagne = $this->campagnes->pourScolarite($scolarite);
            if ($campagne === null || $campagne->getPhase() === PhaseCampagneEnum::A_VENIR) {
                continue;
            }
            if ($this->portfolios->findAcademique($etudiant, $scolarite) === null) {
                $crees[] = $this->creerAcademique($etudiant, $scolarite, $campagne);
            }
        }
        if ($crees !== []) {
            $this->em->flush();
        }

        return $crees;
    }

    public function creerAcademique(Etudiant $etudiant, EtudiantScolarite $scolarite, Campagne $campagne): Portfolio
    {
        $annee = $this->perimetre->getAnneeFormation($scolarite)?->getLibelle();
        $titre = trim(sprintf('Portfolio %s — %s', $annee ?? 'BUT', $scolarite->getAnneeUniversitaire()?->getLibelle() ?? ''), ' —');
        $modele = $campagne->getModele();
        $portfolio = $this->garder(fn () => new Portfolio($etudiant, TypePortfolioEnum::ACADEMIQUE, $titre, $scolarite, $modele));

        $pages = $modele?->getPages() ?? ModeleParDefaut::pages($this->competencesDeLAnnee($scolarite), $titre);
        $contexte = new ContexteModele();
        foreach ($pages as $definition) {
            $page = $portfolio->ajouterPage((string) $definition['titre'], (bool) ($definition['verrouillee'] ?? false));
            // Les blocs d'un modèle sont revalidés : le schéma a pu évoluer depuis son enregistrement.
            $page->initialiser($this->validateur->valider($definition['blocs'] ?? [], $contexte), $definition['verrouilles'] ?? []);
        }
        $this->em->persist($portfolio);

        return $portfolio;
    }

    // ---------- Droits d'écriture ----------

    /** Un portfolio académique devient lecture seule à la clôture de la campagne de l'année. */
    public function estModifiable(Portfolio $portfolio): bool
    {
        if ($portfolio->getType() !== TypePortfolioEnum::ACADEMIQUE || $portfolio->getScolarite() === null) {
            return true;
        }

        return $this->campagnes->pourScolarite($portfolio->getScolarite())?->isCloturee() !== true;
    }

    public function verifierModifiable(Portfolio $portfolio): void
    {
        if (!$this->estModifiable($portfolio)) {
            throw UnifolioValidationException::un('La campagne de cette année est clôturée : le portfolio n\'est plus modifiable.');
        }
    }

    // ---------- Portfolio, pages ----------

    public function renommer(Portfolio $portfolio, string $titre): Portfolio
    {
        $this->verifierModifiable($portfolio);
        $this->garder(fn () => $portfolio->setTitre($titre));
        $this->em->flush();

        return $portfolio;
    }

    public function ajouterPage(Portfolio $portfolio, string $titre): PortfolioPage
    {
        $this->verifierModifiable($portfolio);
        $page = $this->garder(fn () => $portfolio->ajouterPage($titre));
        $this->em->flush();

        return $page;
    }

    public function renommerPage(Portfolio $portfolio, PortfolioPage $page, string $titre): PortfolioPage
    {
        $this->verifierModifiable($portfolio);
        if ($page->isVerrouillee()) {
            throw UnifolioValidationException::un('Cette page est imposée par le modèle : elle ne peut pas être renommée.');
        }
        $this->garder(fn () => $page->setTitre($titre));
        $this->em->flush();

        return $page;
    }

    public function supprimerPage(Portfolio $portfolio, PortfolioPage $page): void
    {
        $this->verifierModifiable($portfolio);
        $this->garder(fn () => $portfolio->retirerPage($page));
        $this->em->flush();
    }

    /** @param list<string> $uuids */
    public function reordonner(Portfolio $portfolio, array $uuids): void
    {
        $this->verifierModifiable($portfolio);
        $portfolio->reordonnerPages($uuids);
        $this->em->flush();
    }

    // ---------- Blocs ----------

    /**
     * Enregistre le contenu d'une page après validation. Renvoie les anomalies de complétude
     * (non bloquantes à l'enregistrement).
     *
     * @return list<string>
     */
    public function enregistrerBlocs(Portfolio $portfolio, PortfolioPage $page, mixed $blocs, int $versionConnue): array
    {
        $this->verifierModifiable($portfolio);
        $normalises = $this->validateur->valider($blocs, new ContexteEtudiant($portfolio->getEtudiant(), $this->traces));
        $this->garder(fn () => $page->remplacerBlocs($normalises, $versionConnue));
        $this->em->flush();

        return $this->validateur->incomplets($normalises);
    }

    /** @return list<string> */
    public function incomplets(PortfolioPage $page): array
    {
        return $this->validateur->incomplets($page->getBlocs());
    }

    /** @return list<array{id: int|null, libelle: string|null, nomCourt: string|null}> */
    private function competencesDeLAnnee(EtudiantScolarite $scolarite): array
    {
        return array_map(
            static fn (array $c) => ['id' => $c['id'], 'libelle' => $c['libelle'], 'nomCourt' => $c['nomCourt']],
            $this->referentiel->arbre($scolarite)
        );
    }

    /**
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
}
