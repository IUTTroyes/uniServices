<?php

declare(strict_types=1);

namespace App\Service\Orebut;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Apc\ApcParcours;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Structure\StructureDiplome;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Met à jour le référentiel de compétences d'un diplôme depuis ORéBUT.
 *
 * - Rien n'est supprimé : un élément disparu de la source est conservé et signalé (des traces peuvent le citer).
 * - Relancer la synchronisation sans changement à la source ne modifie rien.
 * - Une autre année de programme crée un nouveau référentiel ; l'ancien est conservé.
 * - La simulation exécute le même traitement puis l'annule.
 */
class ImportReferentiel
{
    /** @var array<string, array{ajouts: int, modifications: int, inchanges: int}> */
    private array $compteurs = [];
    /** @var list<string> */
    private array $absents = [];
    /** @var list<string> */
    private array $alertes = [];

    public function __construct(
        private readonly OrebutClient $client,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @param ?string $codeParcours parcours suivi par ce diplôme, s'il doit être renseigné
     *
     * @return array{simulation: bool, referentiel: array{libelle: string, annee: ?int, nouveau: bool}, compteurs: array<string, array{ajouts: int, modifications: int, inchanges: int}>, absents: list<string>, alertes: list<string>}
     */
    public function synchroniser(StructureDiplome $diplome, string $sigle, ?string $codeParcours = null, bool $simulation = false): array
    {
        // Tout est lu avant d'écrire : une source en panne ne laisse pas un référentiel à moitié mis à jour.
        $competences = $this->client->competences($sigle);
        if ($competences === []) {
            throw new OrebutException(sprintf('ORéBUT ne propose aucune compétence pour la spécialité « %s ».', $sigle));
        }
        $specialite = null;
        foreach ($this->client->specialites() as $candidate) {
            if (strcasecmp($candidate['sigle'], $sigle) === 0) {
                $specialite = $candidate;
            }
        }

        $this->compteurs = array_fill_keys(['competences', 'niveaux', 'apprentissagesCritiques', 'parcours'], ['ajouts' => 0, 'modifications' => 0, 'inchanges' => 0]);
        $this->absents = [];
        $this->alertes = [];

        $connexion = $this->em->getConnection();
        $connexion->beginTransaction();
        try {
            $annee = max(array_map(static fn (array $c): int => (int) ($c['version']['annee'] ?? 0), $competences)) ?: null;
            [$referentiel, $nouveau] = $this->referentiel($diplome, $specialite['libelle'] ?? $sigle, $annee);
            $niveauxSource = $this->importerCompetences($referentiel, $competences);
            $this->importerParcours($diplome, $specialite, $niveauxSource, $codeParcours);

            $rapport = [
                'simulation' => $simulation,
                'referentiel' => ['libelle' => (string) $referentiel->getLibelle(), 'annee' => $referentiel->getAnneePublication(), 'nouveau' => $nouveau],
                'compteurs' => $this->compteurs,
                'absents' => $this->absents,
                'alertes' => $this->alertes,
            ];

            $this->em->flush();
            if ($simulation) {
                $connexion->rollBack();
                $this->em->clear();
            } else {
                $connexion->commit();
            }
        } catch (\Throwable $e) {
            $connexion->rollBack();
            $this->em->clear();
            throw $e;
        }

        return $rapport;
    }

    /** @return array{ApcReferentiel, bool} référentiel et « vient d'être créé » */
    private function referentiel(StructureDiplome $diplome, string $libelle, ?int $annee): array
    {
        $type = $diplome->getTypeDiplome();
        $departement = $diplome->getDepartement();
        if ($type === null || !$type->isApc() || $departement === null) {
            throw new OrebutException('Le diplôme doit être rattaché à un département et à un type de diplôme par compétences.');
        }

        $actuel = $diplome->getReferentiel();
        $memeProgramme = static fn (ApcReferentiel $r): bool => $r->getAnneePublication() === null || $annee === null || $r->getAnneePublication() === $annee;
        $referentiel = $actuel !== null && $memeProgramme($actuel) ? $actuel : null;
        // Les autres parcours de la spécialité partagent le même référentiel.
        $referentiel ??= $this->em->getRepository(ApcReferentiel::class)->findOneBy([
            'departement' => $departement, 'typeDiplome' => $type, 'libelle' => $this->court($libelle, 255), 'anneePublication' => $annee,
        ]);

        $nouveau = $referentiel === null;
        if ($nouveau) {
            $referentiel = (new ApcReferentiel())->setLibelle($this->court($libelle, 255))->setDepartement($departement)->setTypeDiplome($type);
            $this->em->persist($referentiel);
            if ($actuel !== null) {
                $this->alertes[] = sprintf('Le référentiel « %s » (%s) est conservé ; le diplôme utilisera la nouvelle version.', $actuel->getLibelle(), $actuel->getAnneePublication() ?? 'année inconnue');
            }
        }
        if ($referentiel->getAnneePublication() === null && $annee !== null) {
            $referentiel->setAnneePublication($annee);
        }

        $parent = $diplome->getParent();
        foreach ([$diplome, $parent] as $concerne) {
            if ($concerne !== null && ($concerne === $diplome || $concerne->getReferentiel() === null || $concerne->getReferentiel() === $actuel)) {
                $referentiel->addDiplome($concerne);
            }
        }

        return [$referentiel, $nouveau];
    }

    /**
     * @param list<array<string, mixed>> $competences
     *
     * @return array<int, ApcNiveau> identifiant du niveau dans la source => niveau local
     */
    private function importerCompetences(ApcReferentiel $referentiel, array $competences): array
    {
        $existantes = array_values($referentiel->getCompetences()->toArray());
        // Deux compétences d'une spécialité peuvent porter le même nom court : le libellé les départage.
        $paires = $this->apparier(
            array_map(fn (ApcCompetence $c): array => [$this->cle($c->getNomCourt()), $this->cle($c->getLibelle())], $existantes),
            array_map(fn (array $s): array => [
                $this->cle($this->court((string) ($s['nom_court'] ?? $s['libelle'] ?? ''), 50)),
                $this->cle($this->court((string) ($s['libelle'] ?? $s['nom_court'] ?? ''), 255)),
            ], $competences),
            [1, 0],
        );

        $niveauxSource = [];
        foreach ($competences as $rang => $source) {
            $nomCourt = $this->court((string) ($source['nom_court'] ?? $source['libelle'] ?? ''), 50);
            $competence = isset($paires[$rang]) ? $existantes[$paires[$rang]] : null;
            $nouvelle = $competence === null;
            if ($nouvelle) {
                $competence = new ApcCompetence();
                $referentiel->addCompetence($competence);
                // La couleur n'est posée qu'à la création : un choix local est respecté ensuite.
                $competence->setCouleur(isset($source['couleur']) ? $this->court((string) $source['couleur'], 20) : null);
                $this->em->persist($competence);
            }

            $change = $this->maj($competence->getLibelle(), $this->court((string) ($source['libelle'] ?? $nomCourt), 255), $competence->setLibelle(...));
            $change = $this->maj($competence->getNomCourt(), $nomCourt, $competence->setNomCourt(...)) || $change;
            $composantes = array_map(
                static fn (array $c): array => ['libelle' => $c['libelle'] ?? '', 'code' => $c['code'] ?? '', 'ordre' => $c['ordre'] ?? 0],
                array_values($source['apcComposanteEssentielles'] ?? []),
            );
            usort($composantes, static fn (array $a, array $b) => [$a['ordre'], $a['code']] <=> [$b['ordre'], $b['code']]);
            if ($competence->getComposantesEssentielles() != $composantes) {
                $competence->setComposantesEssentielles($composantes);
                $change = true;
            }
            $situations = array_map(static fn (array $s): array => ['libelle' => $s['libelle'] ?? ''], array_values($source['apcSituationProfessionnelles'] ?? []));
            if ($competence->getSituationsProfessionnelles() != $situations) {
                $competence->setSituationsProfessionnelles($situations);
                $change = true;
            }
            $this->compter('competences', $nouvelle, $change);

            $niveauxSource += $this->importerNiveaux($competence, array_values($source['apcNiveaux'] ?? []));
        }

        foreach (array_diff_key($existantes, array_flip($paires)) as $competence) {
            $this->absents[] = 'Compétence « '.($competence->getNomCourt() ?: $competence->getLibelle()).' »';
        }

        return $niveauxSource;
    }

    /**
     * @param list<array<string, mixed>> $niveaux
     *
     * @return array<int, ApcNiveau>
     */
    private function importerNiveaux(ApcCompetence $competence, array $niveaux): array
    {
        $existants = [];
        foreach ($competence->getNiveaux() as $niveau) {
            $existants[(int) $niveau->getOrdre()] = $niveau;
        }

        $correspondances = [];
        $vus = [];
        foreach ($niveaux as $rang => $source) {
            $ordre = (int) ($source['ordre'] ?? $rang + 1);
            $niveau = $existants[$ordre] ?? null;
            $nouveau = $niveau === null;
            if ($nouveau) {
                $niveau = new ApcNiveau();
                $competence->addNiveau($niveau);
                $this->em->persist($niveau);
            }
            $vus[$ordre] = true;

            $change = $this->maj($niveau->getLibelle(), $this->court((string) ($source['libelle'] ?? 'Niveau '.$ordre), 255), $niveau->setLibelle(...));
            $change = $this->maj($niveau->getOrdre(), $ordre, $niveau->setOrdre(...)) || $change;
            // « BUT2 » => deuxième année.
            if (preg_match('/(\d+)\s*$/', (string) ($source['annee']['libelle'] ?? ''), $annee)) {
                $change = $this->maj($niveau->getOrdreAnnee(), (int) $annee[1], $niveau->setOrdreAnnee(...)) || $change;
            }
            $this->compter('niveaux', $nouveau, $change);

            $this->importerApprentissages($niveau, array_values($source['apcApprentissageCritiques'] ?? []));
            if (!empty($source['id'])) {
                $correspondances[(int) $source['id']] = $niveau;
            }
        }

        foreach ($existants as $ordre => $niveau) {
            if (!isset($vus[$ordre])) {
                $this->absents[] = sprintf('Niveau %d de « %s »', $ordre, $competence->getNomCourt());
            }
        }

        return $correspondances;
    }

    /** @param list<array<string, mixed>> $apprentissages */
    private function importerApprentissages(ApcNiveau $niveau, array $apprentissages): void
    {
        $existants = array_values($niveau->getApprentissageCritique()->toArray());
        $paires = $this->apparier(
            array_map(fn (ApcApprentissageCritique $ac): array => [$this->cle($ac->getCode()), $this->cle($ac->getLibelle())], $existants),
            array_map(fn (array $s): array => [
                $this->cle($this->court((string) ($s['code'] ?? ''), 20)),
                $this->cle((string) ($s['libelle'] ?? $s['code'] ?? '')),
            ], $apprentissages),
            [0, 1],
        );

        foreach ($apprentissages as $rang => $source) {
            $code = $this->court((string) ($source['code'] ?? ''), 20);
            $libelle = (string) ($source['libelle'] ?? $code);
            $ac = isset($paires[$rang]) ? $existants[$paires[$rang]] : null;
            $nouveau = $ac === null;
            if ($nouveau) {
                $ac = new ApcApprentissageCritique();
                $niveau->addApprentissageCritique($ac);
                $this->em->persist($ac);
            }

            $change = $this->maj($ac->getLibelle(), $libelle, $ac->setLibelle(...));
            $change = $this->maj($ac->getCode(), $code, $ac->setCode(...)) || $change;
            $this->compter('apprentissagesCritiques', $nouveau, $change);
        }

        foreach (array_diff_key($existants, array_flip($paires)) as $ac) {
            $this->absents[] = 'Apprentissage critique '.($ac->getCode() ?: $ac->getLibelle());
        }
    }

    /**
     * Rapproche les éléments de la source des éléments locaux, chacun décrit par deux valeurs : d'abord les deux
     * ensemble, puis une seule, dans l'ordre demandé, et seulement si elle désigne un unique élément de chaque côté.
     *
     * @param list<array{string, string}> $locaux
     * @param list<array{string, string}> $sources
     * @param array{int, int}             $replis
     *
     * @return array<int, int> rang dans la source => rang local
     */
    private function apparier(array $locaux, array $sources, array $replis): array
    {
        $paires = [];
        foreach ($sources as $rang => $source) {
            foreach ($locaux as $local => $valeurs) {
                if ($valeurs === $source && !\in_array($local, $paires, true)) {
                    $paires[$rang] = $local;
                    break;
                }
            }
        }

        foreach ($replis as $champ) {
            $libres = array_diff_key($locaux, array_flip($paires));
            $restants = array_diff_key($sources, $paires);
            foreach ($restants as $rang => $source) {
                $candidats = array_keys(array_filter($libres, static fn (array $v): bool => $v[$champ] === $source[$champ]));
                $homonymes = array_filter($restants, static fn (array $v): bool => $v[$champ] === $source[$champ]);
                if ($source[$champ] !== '' && \count($candidats) === 1 && \count($homonymes) === 1) {
                    $paires[$rang] = $candidats[0];
                }
            }
        }

        return $paires;
    }

    /**
     * Rattache les niveaux aux parcours de la spécialité. Un niveau sans parcours reste commun à tous.
     *
     * @param ?array{sigle: string, libelle: string, parcours: list<array{code: string, libelle: string, couleur: ?string, niveaux: list<int>}>} $specialite
     * @param array<int, ApcNiveau> $niveaux
     */
    private function importerParcours(StructureDiplome $diplome, ?array $specialite, array $niveaux, ?string $codeParcours): void
    {
        if ($specialite === null) {
            $this->alertes[] = 'Spécialité absente de la liste d’ORéBUT : les parcours n’ont pas été importés.';

            return;
        }

        // Les parcours n'ont pas de département : on ne rapproche que ceux déjà liés à ces niveaux ou à ce diplôme.
        $connus = [];
        if ($diplome->getParcours() !== null) {
            $connus[$this->cle($diplome->getParcours()->getSigle())] = $diplome->getParcours();
        }
        foreach ($niveaux as $niveau) {
            foreach ($niveau->getParcours() as $parcours) {
                $connus[$this->cle($parcours->getSigle())] ??= $parcours;
            }
        }

        $parNiveau = [];
        $rattaches = false;
        $importes = [];
        foreach ($specialite['parcours'] as $source) {
            $code = $this->court($source['code'], 10);
            $parcours = $connus[$this->cle($code)] ?? null;
            $nouveau = $parcours === null;
            if ($nouveau) {
                $parcours = new ApcParcours();
                $parcours->setActif(true);
                $parcours->setCouleur($source['couleur'] !== null ? $this->court($source['couleur'], 20) : null);
                $this->em->persist($parcours);
            }
            $change = $this->maj($parcours->getSigle(), $code, $parcours->setSigle(...));
            $change = $this->maj($parcours->getLibelle(), $this->court($source['libelle'], 255), $parcours->setLibelle(...)) || $change;
            $this->compter('parcours', $nouveau, $change);
            $importes[$this->cle($code)] = $parcours;

            foreach ($source['niveaux'] as $id) {
                if (isset($niveaux[$id])) {
                    $parNiveau[$id][] = $parcours;
                    $rattaches = true;
                }
            }
        }

        if ($specialite['parcours'] !== [] && !$rattaches) {
            $this->alertes[] = 'Aucun niveau n’a pu être rapproché des parcours : les rattachements existants sont conservés.';
        } else {
            foreach ($niveaux as $id => $niveau) {
                $voulus = $parNiveau[$id] ?? [];
                foreach ($niveau->getParcours()->toArray() as $actuel) {
                    if (\in_array($actuel, $importes, true) && !\in_array($actuel, $voulus, true)) {
                        $niveau->getParcours()->removeElement($actuel);
                    }
                }
                foreach ($voulus as $parcours) {
                    $niveau->addParcours($parcours);
                }
            }
        }

        if ($codeParcours !== null && $codeParcours !== '') {
            $choisi = $importes[$this->cle($codeParcours)] ?? throw new OrebutException('Parcours inconnu pour cette spécialité : '.$codeParcours);
            $diplome->setParcours($choisi);
        }
    }

    private function maj(mixed $actuel, mixed $voulu, callable $ecrire): bool
    {
        if ($actuel === $voulu) {
            return false;
        }
        $ecrire($voulu);

        return true;
    }

    private function compter(string $famille, bool $nouveau, bool $change): void
    {
        ++$this->compteurs[$famille][$nouveau ? 'ajouts' : ($change ? 'modifications' : 'inchanges')];
    }

    private function cle(?string $valeur): string
    {
        return mb_strtolower(trim((string) $valeur));
    }

    private function court(string $valeur, int $longueur): string
    {
        return mb_substr(trim($valeur), 0, $longueur);
    }
}
