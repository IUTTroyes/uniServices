<?php

namespace UnifolioBundle\Service\Grille;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Entity\GrilleVersion;
use UnifolioBundle\Entity\ParametrageFormation;
use UnifolioBundle\Enum\AgregationEnum;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\SourceCritereEnum;
use UnifolioBundle\Enum\TypeCritereEnum;
use UnifolioBundle\Repository\GrilleRepository;
use UnifolioBundle\Repository\ParametrageFormationRepository;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Cas d'usage d'administration : grilles, versions, paramétrage des formations. */
class GrilleManager
{
    public const CRITERES_MAX = 40;
    public const ECHELONS_MAX = 10;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GrilleRepository $grilles,
        private readonly ParametrageFormationRepository $parametrages,
    ) {
    }

    /**
     * Prépare une grille (non persistée : le contrôleur vérifie les droits sur sa portée avant d'enregistrer).
     *
     * @param array<string, mixed> $d
     */
    public function preparer(array $d, StructureTypeDiplome $typeDiplome, ?StructureDepartement $departement, ?StructureDiplome $diplome): Grille
    {
        $granularite = GranulariteEnum::tryFrom((string) ($d['granularite'] ?? ''))
            ?? throw UnifolioValidationException::un('Choisissez ce que la grille évalue (compétence, niveau ou AC).');

        $grille = $this->garder(fn () => new Grille((string) ($d['libelle'] ?? ''), $typeDiplome, $granularite, $departement, $diplome));
        $grille->setDescription(isset($d['description']) ? (string) $d['description'] : null);
        $brouillon = $grille->creerBrouillon();
        $brouillon->ajouterCritere(match ($granularite) {
            GranulariteEnum::AC => 'Niveau d\'acquisition de l\'apprentissage critique',
            GranulariteEnum::NIVEAU => 'Maîtrise du niveau de compétence',
            GranulariteEnum::COMPETENCE => 'Maîtrise de la compétence au niveau attendu',
        }, TypeCritereEnum::ECHELLE);

        return $grille;
    }

    public function enregistrer(Grille $grille): Grille
    {
        $this->em->persist($grille);
        $this->em->flush();

        return $grille;
    }

    /** @param array<string, mixed> $d */
    public function modifier(Grille $grille, array $d): Grille
    {
        if (\array_key_exists('libelle', $d)) {
            $this->garder(fn () => $grille->setLibelle((string) $d['libelle']));
        }
        if (\array_key_exists('description', $d)) {
            $grille->setDescription($d['description'] !== null ? (string) $d['description'] : null);
        }
        if (\array_key_exists('actif', $d)) {
            $grille->setActif((bool) $d['actif']);
        }
        $this->em->flush();

        return $grille;
    }

    public function supprimer(Grille $grille): void
    {
        if ($grille->hasVersionPubliee()) {
            throw UnifolioValidationException::un('Une grille publiée ne peut pas être supprimée : désactivez-la.');
        }
        $this->em->remove($grille);
        $this->em->flush();
    }

    public function nouvelleVersion(Grille $grille): GrilleVersion
    {
        $brouillon = $grille->creerBrouillon();
        $this->em->flush();

        return $brouillon;
    }

    public function abandonnerBrouillon(Grille $grille): void
    {
        if (!$grille->hasVersionPubliee()) {
            throw UnifolioValidationException::un('Le brouillon d\'une grille jamais publiée ne peut pas être abandonné : supprimez la grille.');
        }
        $grille->supprimerBrouillon();
        $this->em->flush();
    }

    /**
     * Remplace le contenu du brouillon (échelle + critères).
     *
     * @param array{echelons?: mixed, criteres?: mixed} $d
     */
    public function enregistrerBrouillon(Grille $grille, array $d): GrilleVersion
    {
        $brouillon = $grille->getBrouillon() ?? throw UnifolioValidationException::un('Aucun brouillon en cours : créez une nouvelle version.');
        $echelons = \is_array($d['echelons'] ?? null) ? array_values($d['echelons']) : [];
        $criteres = \is_array($d['criteres'] ?? null) ? array_values($d['criteres']) : [];

        if (\count($echelons) > self::ECHELONS_MAX) {
            throw UnifolioValidationException::un(sprintf('L\'échelle est limitée à %d niveaux.', self::ECHELONS_MAX));
        }
        if (\count($criteres) > self::CRITERES_MAX) {
            throw UnifolioValidationException::un(sprintf('Une grille est limitée à %d critères.', self::CRITERES_MAX));
        }
        $nbIa = \count(array_filter($criteres, static fn ($c) => ($c['source'] ?? null) === SourceCritereEnum::DECLARATION_IA->value));
        if ($nbIa > 1) {
            throw UnifolioValidationException::un('Une grille ne peut contenir qu\'un critère « Usage de l\'IA ».');
        }

        $this->garder(function () use ($brouillon, $echelons, $criteres): void {
            $brouillon->viderContenu();
            foreach ($echelons as $e) {
                $brouillon->ajouterEchelon(
                    (string) ($e['libelle'] ?? ''),
                    (float) ($e['valeur'] ?? 0),
                    isset($e['couleur']) ? (string) $e['couleur'] : null,
                    isset($e['descripteur']) ? (string) $e['descripteur'] : null,
                );
            }
            foreach ($criteres as $c) {
                $type = TypeCritereEnum::tryFrom((string) ($c['type'] ?? '')) ?? throw new \InvalidArgumentException('Type de critère inconnu.');
                $critere = $brouillon->ajouterCritere((string) ($c['libelle'] ?? ''), $type)
                    ->setDescription(isset($c['description']) ? (string) $c['description'] : null)
                    ->setAide(isset($c['aide']) ? (string) $c['aide'] : null)
                    ->setSource(SourceCritereEnum::tryFrom((string) ($c['source'] ?? '')) ?? SourceCritereEnum::SAISIE)
                    ->setObligatoire((bool) ($c['obligatoire'] ?? true))
                    ->setVisibleEtudiant((bool) ($c['visibleEtudiant'] ?? true))
                    ->setUtiliseAutoEval((bool) ($c['utiliseAutoEval'] ?? true));
                $critere->setPoids(isset($c['poids']) ? (float) $c['poids'] : 1.0);
                $critere->setNoteMax(isset($c['noteMax']) && $c['noteMax'] !== '' ? (float) $c['noteMax'] : null);
            }
        });
        $this->em->flush();

        return $brouillon;
    }

    public function publier(Grille $grille): GrilleVersion
    {
        $brouillon = $grille->getBrouillon() ?? throw UnifolioValidationException::un('Aucun brouillon à publier.');
        $erreurs = $brouillon->getErreursPublication();
        if ($erreurs !== []) {
            throw new UnifolioValidationException($erreurs);
        }
        $this->garder(fn () => $brouillon->publier());
        $this->em->flush();

        return $brouillon;
    }

    /** Copie une grille (contenu de sa dernière version, publiée ou brouillon) vers une nouvelle portée. */
    public function preparerCopie(Grille $source, string $libelle, ?StructureDepartement $departement, ?StructureDiplome $diplome): Grille
    {
        $copie = $this->garder(fn () => new Grille($libelle, $source->getTypeDiplome(), $source->getGranularite(), $departement, $diplome));
        $copie->setDescription($source->getDescription());
        $brouillon = $copie->creerBrouillon();
        ($source->getBrouillon() ?? $source->getVersionPubliee())?->copierContenuVers($brouillon);

        return $copie;
    }

    // ---------- Paramétrage des formations ----------

    /** @param array<string, mixed> $d */
    public function parametrer(StructureDiplome $diplome, StructureAnneeUniversitaire $annee, array $d): ParametrageFormation
    {
        $granularite = GranulariteEnum::tryFrom((string) ($d['granularite'] ?? ''))
            ?? throw UnifolioValidationException::un('Choisissez la granularité d\'évaluation.');

        $parametrage = $this->parametrages->findOneBy(['diplome' => $diplome, 'applicableDepuis' => $annee]);
        if ($parametrage === null) {
            $parametrage = $this->garder(fn () => new ParametrageFormation($diplome, $annee, $granularite));
            $this->em->persist($parametrage);
        }
        $parametrage->setGranularite($granularite);
        if (isset($d['agregation'])) {
            $parametrage->setAgregation(AgregationEnum::tryFrom((string) $d['agregation']) ?? throw UnifolioValidationException::un('Règle d\'agrégation inconnue.'));
        }
        if (\array_key_exists('grille', $d)) {
            $grille = $d['grille'] ? $this->grilles->findOneByUuid((string) $d['grille']) : null;
            if ($d['grille'] && $grille === null) {
                throw UnifolioValidationException::un('Grille introuvable.');
            }
            if ($grille !== null && !$grille->hasVersionPubliee()) {
                throw UnifolioValidationException::un('Publiez la grille avant de l\'associer à une formation.');
            }
            $this->garder(fn () => $parametrage->setGrille($grille));
        }
        $this->em->flush();

        return $parametrage;
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
