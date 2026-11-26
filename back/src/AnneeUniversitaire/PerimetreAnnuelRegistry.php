<?php

declare(strict_types=1);

namespace App\AnneeUniversitaire;

use App\Extension\PerimetreAnnuelProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Chemins ORM explicites : aucun filtre Doctrine global ni recherche heuristique de relations.
 * Le cœur déclare ses classes ; chaque bundle apporte les siennes par un PerimetreAnnuelProviderInterface.
 */
final class PerimetreAnnuelRegistry
{
    private const PATHS = [
        'Etudiant' => 'scolarites.anneeUniversitaire',
        'StructureDiplome' => 'pns.anneeUniversitaire',
        'StructurePn' => 'anneeUniversitaire',
        'StructureAnnee' => 'pn.anneeUniversitaire',
        'StructureSemestre' => 'annee.pn.anneeUniversitaire',
        'StructureUe' => 'semestre.annee.pn.anneeUniversitaire',
        'StructureGroupe' => 'semestres.annee.pn.anneeUniversitaire',
        'ScolEnseignement' => 'enseignementUes.ue.semestre.annee.pn.anneeUniversitaire',
        'ScolEnseignementUe' => 'ue.semestre.annee.pn.anneeUniversitaire',
        'ScolEvaluation' => 'anneeUniversitaire',
        'ScolEvaluationRattrapage' => 'evaluation.anneeUniversitaire',
        'EtudiantScolarite' => 'anneeUniversitaire',
        'EtudiantScolariteSemestre' => 'scolarite.anneeUniversitaire',
        'EtudiantNote' => 'scolariteSemestre.scolarite.anneeUniversitaire',
        'PersonnelEnseignantHrs' => 'annee_universitaire',
        'EdtEvent' => 'anneeUniversitaire',
    ];

    private const INDEPENDANTS = [
        'StructureAnneeUniversitaire', 'StructureDepartement', 'StructureTypeDiplome', 'StructureService', 'StructureDepartementPersonnel',
        'Etudiant', 'Personnel', 'Salle', 'ScolBac', 'PersonnelEnseignantTypeHrs', 'EdtProgression',
        'ApcReferentiel', 'ApcCompetence', 'ApcParcours', 'ApcNiveau', 'ApcApprentissageCritique',
        'Etablissement', 'Universite', 'ResetToken', 'RefreshToken', 'EmailTemplate', 'DepartementActualite', 'LienUtile',
        'Notification', 'PreferenceNotification', 'DashboardPreference', 'SchedulerTask',
    ];

    private const TOUTES_LES_ANNEES = ['Etudiant', 'StructureDiplome', 'EtudiantScolarite', 'EtudiantScolariteSemestre'];

    /** @var array<string, string> */
    private array $paths = self::PATHS;
    /** @var list<string> */
    private array $independants = self::INDEPENDANTS;
    /** @var array<string, string> */
    private array $dtoModes = [];
    /** @var list<string> */
    private array $toutes = self::TOUTES_LES_ANNEES;
    /** @var list<PerimetreAnnuelProviderInterface> */
    private array $providers = [];

    /** @param iterable<PerimetreAnnuelProviderInterface> $providers */
    public function __construct(
        #[AutowireIterator('app.perimetre_annuel')]
        iterable $providers = [],
    ) {
        foreach ($providers as $provider) {
            $this->providers[] = $provider;
            $this->paths = array_merge($this->paths, $provider->chemins());
            $this->independants = array_merge($this->independants, $provider->independants());
            $this->dtoModes = array_merge($this->dtoModes, $provider->modesDto());
            $this->toutes = array_merge($this->toutes, $provider->consultablesSurToutesLesAnnees());
        }
    }

    public function mode(string $class): string
    {
        return $this->dtoModes[$this->nom($class)] ?? ($this->path($class) === null ? 'independant' : 'annuel');
    }

    public function path(string $class): ?string
    {
        $name = $this->nom($class);
        if (str_starts_with($class, 'ApiPlatform\\') || isset($this->dtoModes[$name])) {
            return null;
        }
        // Une déclaration par nom complet l'emporte sur le nom court.
        if (isset($this->paths[$class]) || isset($this->paths[$name])) {
            return $this->paths[$class] ?? $this->paths[$name];
        }
        if (\in_array($class, $this->independants, true) || \in_array($name, $this->independants, true)) {
            return null;
        }
        throw new \LogicException('Politique annuelle non déclarée pour '.$class.'.');
    }

    public function autoriseToutes(string $class): bool
    {
        return \in_array($class, $this->toutes, true) || \in_array($this->nom($class), $this->toutes, true);
    }

    /** Les contrôleurs du cœur et des bundles concernés doivent déclarer leur politique annuelle. */
    public function politiqueObligatoire(string $classeControleur): bool
    {
        $racine = explode('\\', $classeControleur)[0];
        if ($racine === 'App') {
            return true;
        }
        foreach ($this->providers as $provider) {
            if ($provider->espaceDeNoms() === $racine) {
                return true;
            }
        }

        return false;
    }

    /** @param array<mixed> $corps */
    public function requeteHorsAnnee(string $classe, Request $request, array $corps = []): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->requeteHorsAnnee($classe, $request, $corps)) {
                return true;
            }
        }

        return false;
    }

    public function conditionHorsAnnee(string $classe, string $alias): ?string
    {
        foreach ($this->providers as $provider) {
            if (($condition = $provider->conditionHorsAnnee($classe, $alias)) !== null) {
                return $condition;
            }
        }

        return null;
    }

    public function entiteDeContexte(string $classe, Request $request): ?object
    {
        foreach ($this->providers as $provider) {
            if (($entite = $provider->entiteDeContexte($classe, $request)) !== null) {
                return $entite;
            }
        }

        return null;
    }

    private function nom(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
