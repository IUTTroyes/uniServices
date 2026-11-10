<?php

namespace UnifolioBundle\Service\Portfolio;

use App\Entity\Users\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Cv;
use UnifolioBundle\Entity\CvExperience;
use UnifolioBundle\Entity\CvFormation;
use UnifolioBundle\Repository\CvRepository;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

class CvManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CvRepository $cvs,
    ) {
    }

    public function charger(Etudiant $etudiant): ?Cv
    {
        return $this->cvs->findOneByEtudiant($etudiant);
    }

    /**
     * Remplace tout le CV (document complet). Toutes les anomalies sont signalées d'un coup.
     *
     * @param array<string, mixed> $d
     */
    public function enregistrer(Etudiant $etudiant, array $d): Cv
    {
        $erreurs = [];
        $cv = $this->cvs->findOneByEtudiant($etudiant) ?? new Cv($etudiant);

        $cv->setPoste($this->texte($d['poste'] ?? null, 255, 'Le poste recherché', $erreurs))
            ->setDescription($this->texte($d['description'] ?? null, 3000, 'La présentation', $erreurs))
            ->setLangues($this->langues($d['langues'] ?? [], $erreurs))
            ->setSoftSkills($this->liste($d['softSkills'] ?? [], 'Les soft skills', $erreurs))
            ->setHardSkills($this->liste($d['hardSkills'] ?? [], 'Les compétences techniques', $erreurs))
            ->setReseaux($this->reseaux($d['reseaux'] ?? [], $erreurs));

        $experiences = [];
        foreach ($this->elements($d['experiences'] ?? [], 'Les expériences', $erreurs) as $i => $e) {
            try {
                $experiences[] = new CvExperience($cv, (string) ($e['poste'] ?? ''), $this->texte($e['entreprise'] ?? null, 255, 'L\'entreprise', $erreurs), $this->date($e['dateDebut'] ?? null, $erreurs), $this->date($e['dateFin'] ?? null, $erreurs), $this->texte($e['description'] ?? null, 2000, 'La description', $erreurs));
            } catch (\InvalidArgumentException $ex) {
                $erreurs[] = sprintf('Expérience %d : %s', $i + 1, $ex->getMessage());
            }
        }
        $formations = [];
        foreach ($this->elements($d['formations'] ?? [], 'Les formations', $erreurs) as $i => $f) {
            try {
                $formations[] = new CvFormation($cv, (string) ($f['diplome'] ?? ''), $this->texte($f['etablissement'] ?? null, 255, 'L\'établissement', $erreurs), $this->date($f['dateDebut'] ?? null, $erreurs), $this->date($f['dateFin'] ?? null, $erreurs), $this->texte($f['description'] ?? null, 2000, 'La description', $erreurs));
            } catch (\InvalidArgumentException $ex) {
                $erreurs[] = sprintf('Formation %d : %s', $i + 1, $ex->getMessage());
            }
        }

        if ($erreurs !== []) {
            throw new UnifolioValidationException(\array_slice(array_values(array_unique($erreurs)), 0, 15));
        }
        $cv->remplacerExperiences($experiences);
        $cv->remplacerFormations($formations);
        $this->em->persist($cv);
        $this->em->flush();

        return $cv;
    }

    /** @param list<string> $erreurs */
    private function texte(mixed $v, int $max, string $nom, array &$erreurs): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (!\is_string($v)) {
            $erreurs[] = sprintf('%s : texte attendu.', $nom);

            return null;
        }
        $v = trim($v);
        if (mb_strlen($v) > $max) {
            $erreurs[] = sprintf('%s : %d caractères maximum.', $nom, $max);

            return null;
        }

        return $v === '' ? null : $v;
    }

    /**
     * @param list<string> $erreurs
     *
     * @return list<array<string, mixed>>
     */
    private function elements(mixed $v, string $nom, array &$erreurs): array
    {
        if (!\is_array($v) || !array_is_list($v) || \count($v) > Cv::ELEMENTS_MAX) {
            $erreurs[] = sprintf('%s : liste de %d éléments maximum attendue.', $nom, Cv::ELEMENTS_MAX);

            return [];
        }

        return array_values(array_filter($v, '\is_array'));
    }

    /**
     * @param list<string> $erreurs
     *
     * @return list<string>
     */
    private function liste(mixed $v, string $nom, array &$erreurs): array
    {
        if (!\is_array($v) || \count($v) > Cv::ELEMENTS_MAX) {
            $erreurs[] = sprintf('%s : %d éléments maximum.', $nom, Cv::ELEMENTS_MAX);

            return [];
        }
        $liste = [];
        foreach ($v as $element) {
            $texte = $this->texte($element, 60, $nom, $erreurs);
            if ($texte !== null) {
                $liste[mb_strtolower($texte)] ??= $texte; // doublon insensible à la casse : la première saisie est conservée
            }
        }

        return array_values($liste);
    }

    /**
     * @param list<string> $erreurs
     *
     * @return list<array{langue: string, niveau: string}>
     */
    private function langues(mixed $v, array &$erreurs): array
    {
        $langues = [];
        foreach ($this->elements($v, 'Les langues', $erreurs) as $l) {
            $langue = $this->texte($l['langue'] ?? null, 60, 'La langue', $erreurs);
            if ($langue !== null) {
                $langues[] = ['langue' => $langue, 'niveau' => $this->texte($l['niveau'] ?? null, 60, 'Le niveau de langue', $erreurs) ?? ''];
            }
        }

        return $langues;
    }

    /**
     * @param list<string> $erreurs
     *
     * @return list<array{type: string, url: string}>
     */
    private function reseaux(mixed $v, array &$erreurs): array
    {
        $reseaux = [];
        foreach ($this->elements($v, 'Les liens', $erreurs) as $r) {
            $url = trim((string) ($r['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            if (mb_strlen($url) > 2048 || filter_var($url, \FILTER_VALIDATE_URL) === false || !\in_array(strtolower((string) parse_url($url, \PHP_URL_SCHEME)), ['http', 'https'], true)) {
                $erreurs[] = sprintf('Le lien « %s » doit être une adresse http(s) valide.', mb_strimwidth($url, 0, 40, '…'));
                continue;
            }
            $reseaux[] = ['type' => $this->texte($r['type'] ?? null, 40, 'Le type de lien', $erreurs) ?? 'site', 'url' => $url];
        }

        return $reseaux;
    }

    /** @param list<string> $erreurs */
    private function date(mixed $v, array &$erreurs): ?\DateTimeImmutable
    {
        if ($v === null || $v === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', substr((string) $v, 0, 10));
        if ($date === false) {
            $erreurs[] = 'Date invalide (format attendu : AAAA-MM-JJ).';

            return null;
        }

        return $date;
    }
}
