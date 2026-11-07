<?php

namespace UnifolioBundle\Service\Campagne;

use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use Doctrine\ORM\EntityManagerInterface;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Repository\CampagneRepository;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

class CampagneManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CampagneRepository $campagnes,
    ) {
    }

    /**
     * Prépare une campagne (non persistée : le contrôleur contrôle les droits sur son diplôme avant d'enregistrer).
     *
     * @param array<string, mixed> $d
     */
    public function preparer(StructureDiplome $diplome, array $d): Campagne
    {
        $annee = $this->em->find(StructureAnneeUniversitaire::class, (int) ($d['anneeUniversitaire'] ?? 0))
            ?? throw UnifolioValidationException::un('Choisissez l\'année universitaire.');
        $campagne = $this->garder(fn () => new Campagne((string) ($d['libelle'] ?? ''), $diplome, $annee, $this->date($d['ouvertureLe'] ?? null) ?? new \DateTimeImmutable('today')));
        $this->appliquer($campagne, $d + ['anneeFormation' => null]);
        $this->verifierUnicite($campagne);

        return $campagne;
    }

    public function enregistrer(Campagne $campagne): Campagne
    {
        $this->em->persist($campagne);
        $this->em->flush();

        return $campagne;
    }

    /** @param array<string, mixed> $d */
    public function modifier(Campagne $campagne, array $d): Campagne
    {
        if ($campagne->isCloturee()) {
            throw UnifolioValidationException::un('Une campagne clôturée ne peut plus être modifiée.');
        }
        $this->appliquer($campagne, $d);
        $this->verifierUnicite($campagne);
        $this->em->flush();

        return $campagne;
    }

    public function cloturer(Campagne $campagne): Campagne
    {
        if ($campagne->isCloturee()) {
            throw UnifolioValidationException::un('Cette campagne est déjà clôturée.');
        }
        $campagne->cloturer();
        $this->em->flush();

        return $campagne;
    }

    public function supprimer(Campagne $campagne): void
    {
        if ($this->em->getRepository(Evaluation::class)->count(['campagne' => $campagne]) > 0) {
            throw UnifolioValidationException::un('Cette campagne contient des évaluations : clôturez-la plutôt que de la supprimer.');
        }
        $this->em->remove($campagne);
        $this->em->flush();
    }

    /** @param array<string, mixed> $d */
    private function appliquer(Campagne $campagne, array $d): void
    {
        $this->garder(function () use ($campagne, $d): void {
            if (\array_key_exists('libelle', $d)) {
                $campagne->setLibelle((string) $d['libelle']);
            }
            if (\array_key_exists('anneeFormation', $d)) {
                $af = $d['anneeFormation'] ? $this->em->find(StructureAnnee::class, (int) $d['anneeFormation']) : null;
                if ($d['anneeFormation'] && $af === null) {
                    throw new \InvalidArgumentException('Année de BUT inconnue.');
                }
                $campagne->setAnneeFormation($af);
            }
            if (\array_key_exists('autoEvaluationRequise', $d)) {
                $campagne->setAutoEvaluationRequise((bool) $d['autoEvaluationRequise']);
            }
            $campagne->definirCalendrier(
                \array_key_exists('ouvertureLe', $d) ? ($this->date($d['ouvertureLe']) ?? $campagne->getOuvertureLe()) : $campagne->getOuvertureLe(),
                \array_key_exists('depotLimite', $d) ? $this->date($d['depotLimite']) : $campagne->getDepotLimite(),
                \array_key_exists('evaluationLimite', $d) ? $this->date($d['evaluationLimite']) : $campagne->getEvaluationLimite(),
                \array_key_exists('clotureLe', $d) ? $this->date($d['clotureLe']) : $campagne->getClotureLe(),
            );
        });
    }

    /** Une seule campagne par formation, année universitaire et année de BUT visée. */
    private function verifierUnicite(Campagne $campagne): void
    {
        foreach ($this->campagnes->findPour($campagne->getDiplome(), $campagne->getAnneeUniversitaire()) as $autre) {
            if ($autre === $campagne || ($campagne->getId() !== null && $autre->getId() === $campagne->getId())) {
                continue;
            }
            if ($autre->getAnneeFormation()?->getId() === $campagne->getAnneeFormation()?->getId()) {
                throw UnifolioValidationException::un(sprintf('Une campagne existe déjà pour cette formation, cette année universitaire et cette année de BUT : « %s ».', $autre->getLibelle()));
            }
        }
    }

    private function date(mixed $valeur): ?\DateTimeImmutable
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable((string) $valeur);
        } catch (\Exception) {
            throw UnifolioValidationException::un('Date invalide.');
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
