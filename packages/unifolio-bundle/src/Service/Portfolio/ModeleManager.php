<?php

namespace UnifolioBundle\Service\Portfolio;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\PortfolioModele;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Modèles de portfolio définis par les référents. */
class ModeleManager
{
    public const PAGES_MAX = 20;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BlocValidator $validateur,
    ) {
    }

    /** @param array<string, mixed> $d */
    public function preparer(array $d, ?StructureDepartement $departement, ?StructureDiplome $diplome): PortfolioModele
    {
        $modele = $this->garder(fn () => new PortfolioModele((string) ($d['libelle'] ?? ''), $departement, $diplome));
        $modele->setDescription(isset($d['description']) ? (string) $d['description'] : null);
        // Un nouveau modèle démarre avec une page de présentation à compléter.
        $modele->setPages([[
            'titre' => 'Présentation',
            'verrouillee' => false,
            'blocs' => $this->validateur->valider([ModeleParDefaut::bloc('heading', ['niveau' => 1, 'texte' => 'Mon portfolio']), ModeleParDefaut::bloc('paragraph', ['texte' => ''])], new ContexteModele()),
            'verrouilles' => [],
        ]]);

        return $modele;
    }

    public function enregistrer(PortfolioModele $modele): PortfolioModele
    {
        $this->em->persist($modele);
        $this->em->flush();

        return $modele;
    }

    /** @param array<string, mixed> $d */
    public function modifier(PortfolioModele $modele, array $d): PortfolioModele
    {
        if (\array_key_exists('libelle', $d)) {
            $this->garder(fn () => $modele->setLibelle((string) $d['libelle']));
        }
        if (\array_key_exists('description', $d)) {
            $modele->setDescription($d['description'] !== null ? (string) $d['description'] : null);
        }
        if (\array_key_exists('actif', $d)) {
            $modele->setActif((bool) $d['actif']);
        }
        $this->em->flush();

        return $modele;
    }

    /**
     * Remplace la structure du modèle. Aucune référence à une trace ou un média personnel n'est acceptée.
     *
     * @param mixed $pages [{ titre, verrouillee?, blocs, verrouilles? }]
     */
    public function definirStructure(PortfolioModele $modele, mixed $pages): PortfolioModele
    {
        if (!\is_array($pages) || !array_is_list($pages) || $pages === [] || \count($pages) > self::PAGES_MAX) {
            throw UnifolioValidationException::un(sprintf('Un modèle compte de 1 à %d pages.', self::PAGES_MAX));
        }

        $structure = [];
        $erreurs = [];
        foreach ($pages as $i => $page) {
            $titre = trim((string) ($page['titre'] ?? ''));
            if ($titre === '' || mb_strlen($titre) > 120) {
                $erreurs[] = sprintf('Page %d : titre de 1 à 120 caractères obligatoire.', $i + 1);
                continue;
            }
            try {
                $blocs = $this->validateur->valider($page['blocs'] ?? [], new ContexteModele());
            } catch (UnifolioValidationException $e) {
                foreach ($e->getErreurs() as $erreur) {
                    $erreurs[] = sprintf('Page « %s » › %s', $titre, $erreur);
                }
                continue;
            }
            $ids = [];
            $this->collecterIds($blocs, $ids);
            $verrouilles = array_values(array_unique(array_filter(
                \is_array($page['verrouilles'] ?? null) ? array_map('strval', $page['verrouilles']) : [],
                static fn (string $id) => isset($ids[$id])
            )));
            $structure[] = ['titre' => $titre, 'verrouillee' => (bool) ($page['verrouillee'] ?? false), 'blocs' => $blocs, 'verrouilles' => $verrouilles];
        }
        if ($erreurs !== []) {
            throw new UnifolioValidationException(\array_slice($erreurs, 0, 20));
        }

        $modele->setPages($structure);
        $this->em->flush();

        return $modele;
    }

    public function supprimer(PortfolioModele $modele): void
    {
        if ($this->em->getRepository(Campagne::class)->count(['modele' => $modele]) > 0) {
            throw UnifolioValidationException::un('Ce modèle est utilisé par une campagne : retirez-le de la campagne ou désactivez-le.');
        }
        $this->em->remove($modele);
        $this->em->flush();
    }

    public function preparerCopie(PortfolioModele $source, string $libelle, ?StructureDepartement $departement, ?StructureDiplome $diplome): PortfolioModele
    {
        $copie = $this->garder(fn () => new PortfolioModele($libelle, $departement, $diplome));
        $copie->setDescription($source->getDescription());
        // Les identifiants de blocs sont conservés : ils ne sont uniques qu'au sein d'un modèle.
        $copie->setPages($source->getPages());

        return $copie;
    }

    /**
     * @param list<array<string, mixed>> $blocs
     * @param array<string, true>        $ids
     */
    private function collecterIds(array $blocs, array &$ids): void
    {
        foreach ($blocs as $bloc) {
            $ids[(string) $bloc['id']] = true;
            $this->collecterIds($bloc['children'] ?? [], $ids);
        }
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
