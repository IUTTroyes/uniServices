<?php

declare(strict_types=1);

namespace StageBundle\Adapter;

use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\ValueObject\Adresse;
use Doctrine\Common\Collections\Collection;
use StageBundle\Entity\Stages\StageContact;
use StageBundle\Entity\Stages\StageEntreprise;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;

/**
 * Adaptateur de convention de stage pour le rendu PDF.
 * Permet d'assurer la compatibilité avec le template Twig de convention (intranetV3 / URCA / IUT de Troyes)
 * sans modifier les structures profondes des entités Doctrine uniServices.
 */
class StageConventionAdapter
{
    public function __construct(
        private readonly StageEtudiant $stage,
        private readonly ?StructureSemestre $semestre = null,
        private readonly ?StructureDiplome $diplome = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->stage->getId();
    }

    public function getEtudiant(): ?EtudiantConventionAdapter
    {
        $etu = $this->stage->getEtudiant();
        if ($etu === null) {
            return null;
        }

        return new EtudiantConventionAdapter($etu, $this->stage, $this->semestre, $this->diplome);
    }

    public function getTuteurUniversitaire(): ?PersonnelConventionAdapter
    {
        $tut = $this->stage->getTuteurUniversitaire();
        if ($tut === null) {
            return null;
        }

        return new PersonnelConventionAdapter($tut);
    }

    public function getTuteur(): ?StageContact
    {
        return $this->stage->getTuteur();
    }

    public function getEntreprise(): EntrepriseConventionAdapter
    {
        return new EntrepriseConventionAdapter($this->stage->getEntreprise());
    }

    public function getStagePeriode(): ?StagePeriodeConventionAdapter
    {
        $periode = $this->stage->getStagePeriode();
        if ($periode === null) {
            return null;
        }

        return new StagePeriodeConventionAdapter($periode);
    }

    public function getAdresseStage(): ?Adresse
    {
        return $this->stage->getAdresseStage();
    }

    public function getSujetStage(): ?string
    {
        return $this->stage->getSujetStage() ?? '';
    }

    public function getDateDebutStage(): ?\DateTimeInterface
    {
        return $this->stage->getDateDebutStage();
    }

    public function getDateFinStage(): ?\DateTimeInterface
    {
        return $this->stage->getDateFinStage();
    }

    public function getDureeJoursStage(): int
    {
        return $this->stage->getDureeJoursStage();
    }

    public function getDureeHebdomadaire(): float
    {
        return $this->stage->getDureeHebdomadaire();
    }

    public function getCommentaireDureeHebdomadaire(): ?string
    {
        return $this->stage->getCommentaireDureeHebdomadaire() ?? '';
    }

    public function getPeriodesInterruptions(): ?string
    {
        return $this->stage->getPeriodesInterruptions() ?? '';
    }

    public function getActivites(): ?string
    {
        return $this->stage->getActivites() ?? '';
    }

    public function getAmenagementStage(): ?string
    {
        return $this->stage->getAmenagementStage() ?? 'Aucun';
    }

    public function isGratification(): bool
    {
        return $this->stage->getGratification() || ($this->stage->getGratificationMontant() !== null && $this->stage->getGratificationMontant() > 0);
    }

    public function getGratification(): bool
    {
        return $this->isGratification();
    }

    public function getGratificationMontant(): ?float
    {
        return $this->stage->getGratificationMontant();
    }

    public function getGratificationPeriode(): ?string
    {
        return $this->stage->getGratificationPeriode() ?? 'H';
    }

    public function getAvantages(): ?string
    {
        return $this->stage->getAvantages() ?? 'Aucun';
    }

    public function getServiceStageEntreprise(): ?string
    {
        return $this->stage->getServiceStageEntreprise() ?? 'Service Technique';
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->stage->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this->stage, $getter)) {
            return $this->stage->$getter();
        }

        return $this->stage->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) || method_exists($this->stage, $getter) || isset($this->stage->$name);
    }
}

/**
 * Adaptateur étudiant pour le PDF de convention
 */
class EtudiantConventionAdapter
{
    public function __construct(
        private readonly Etudiant $etudiant,
        private readonly StageEtudiant $stage,
        private readonly ?StructureSemestre $semestre = null,
        private readonly ?StructureDiplome $diplome = null,
    ) {
    }

    public function getNom(): string
    {
        return mb_strtoupper((string) $this->etudiant->getNom());
    }

    public function getPrenom(): string
    {
        return ucfirst((string) $this->etudiant->getPrenom());
    }

    public function getDisplayPr(): string
    {
        return mb_strtoupper((string) $this->etudiant->getNom()) . ' ' . ucfirst((string) $this->etudiant->getPrenom());
    }

    public function getCivilite(): string
    {
        $civilite = $this->etudiant->getOpt()['civilite'] ?? null;
        return $civilite === 'Mme' ? 'Mme' : 'M.';
    }


    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->etudiant->getDateNaissance();
    }

    public function getAdresse(): ?Adresse
    {
        return $this->etudiant->getAdresseEtudiante() ?? $this->etudiant->getAdresseParentale();
    }

    public function getTel1(): ?string
    {
        return $this->etudiant->getTel1();
    }

    public function getTel2(): ?string
    {
        return $this->etudiant->getTel2();
    }

    public function getMailUniv(): ?string
    {
        return $this->etudiant->getMailUniv();
    }

    public function getMailPerso(): ?string
    {
        return $this->etudiant->getMailPerso();
    }

    public function getIntituleSecuriteSociale(): string
    {
        $compagnie = $this->stage->getAssuranceCompagnie();
        if (!empty($compagnie)) {
            return $compagnie;
        }

        $opt = $this->etudiant->getOpt();
        if (!empty($opt['intitule_securite_sociale'])) {
            return (string) $opt['intitule_securite_sociale'];
        }

        return 'CPAM de l\'Aube';
    }

    public function getAdresseSecuriteSociale(): string
    {
        $numero = $this->stage->getAssuranceNumero();
        if (!empty($numero)) {
            return $numero;
        }

        $opt = $this->etudiant->getOpt();
        if (!empty($opt['adresse_securite_sociale'])) {
            return (string) $opt['adresse_securite_sociale'];
        }

        return '';
    }

    public function getSemestre(): ?SemestreConventionAdapter
    {
        $sem = $this->semestre ?? $this->stage->getStagePeriode()?->getSemestreProgramme();
        if ($sem === null) {
            return null;
        }

        return new SemestreConventionAdapter($sem, $this->diplome);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->etudiant->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this->etudiant, $getter)) {
            return $this->etudiant->$getter();
        }

        return $this->etudiant->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) || method_exists($this->etudiant, $getter) || isset($this->etudiant->$name);
    }
}

/**
 * Adaptateur semestre pour résoudre la chaîne semestre.annee.diplome
 */
class SemestreConventionAdapter
{
    public function __construct(
        private readonly StructureSemestre $semestre,
        private readonly ?StructureDiplome $diplome = null,
    ) {
    }

    public function getLibelle(): string
    {
        return $this->semestre->getLibelle();
    }

    public function getAnnee(): ?AnneeConventionAdapter
    {
        $annee = $this->semestre->getAnnee();
        if ($annee === null) {
            return null;
        }

        return new AnneeConventionAdapter($annee, $this->diplome);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->semestre->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this->semestre, $getter)) {
            return $this->semestre->$getter();
        }

        return $this->semestre->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) || method_exists($this->semestre, $getter) || isset($this->semestre->$name);
    }
}

/**
 * Adaptateur année pour résoudre annee.diplome
 */
class AnneeConventionAdapter
{
    public function __construct(
        private readonly StructureAnnee $annee,
        private readonly ?StructureDiplome $diplome = null,
    ) {
    }

    public function getDiplome(): DiplomeConventionAdapter
    {
        $d = $this->diplome ?? $this->annee->getPn()?->getDiplome();
        return new DiplomeConventionAdapter($d);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->annee->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this->annee, $getter)) {
            return $this->annee->$getter();
        }

        return $this->annee->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) || method_exists($this->annee, $getter) || isset($this->annee->$name);
    }
}

/**
 * Adaptateur diplôme pour sécuriser les accès imbriqués
 */
class DiplomeConventionAdapter
{
    public function __construct(
        private readonly ?StructureDiplome $diplome,
    ) {
    }

    public function getAssistantDiplome(): ?Personnel
    {
        return $this->diplome?->getAssistantDiplome();
    }

    public function getSigle(): string
    {
        return $this->diplome?->getSigle() ?? '';
    }

    public function getLibelle(): string
    {
        return $this->diplome?->getLibelle() ?? '';
    }

    public function getVolumeHoraire(): int
    {
        return $this->diplome?->getVolumeHoraire() ?? 600;
    }

    public function getTypeDiplome(): ?StructureTypeDiplome
    {
        return $this->diplome?->getTypeDiplome();
    }

    public function __call(string $name, array $arguments): mixed
    {
        if ($this->diplome === null) {
            return null;
        }
        return $this->diplome->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        if ($this->diplome !== null && method_exists($this->diplome, $getter)) {
            return $this->diplome->$getter();
        }
        return $this->diplome->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) || ($this->diplome !== null && (method_exists($this->diplome, $getter) || isset($this->diplome->$name)));
    }
}

/**
 * Adaptateur entreprise pour sécuriser les accès au responsable et à l'adresse
 */
class EntrepriseConventionAdapter
{
    public function __construct(
        private readonly ?StageEntreprise $entreprise,
    ) {
    }

    public function getResponsable(): StageContact
    {
        $resp = $this->entreprise?->getResponsable();
        if ($resp !== null) {
            return $resp;
        }

        $blank = new StageContact();
        $blank->setNom('');
        $blank->setPrenom('');
        $blank->setFonction('');
        return $blank;
    }

    public function getAdresse(): ?Adresse
    {
        return $this->entreprise?->getAdresse();
    }

    public function getRaisonSociale(): string
    {
        return $this->entreprise?->getRaisonSociale() ?? '';
    }

    public function __call(string $name, array $arguments): mixed
    {
        if ($this->entreprise === null) {
            return null;
        }
        return $this->entreprise->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        if ($this->entreprise !== null && method_exists($this->entreprise, $getter)) {
            return $this->entreprise->$getter();
        }
        return $this->entreprise->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) || ($this->entreprise !== null && (method_exists($this->entreprise, $getter) || isset($this->entreprise->$name)));
    }
}

/**
 * Adaptateur tuteur universitaire / enseignant référent
 */
class PersonnelConventionAdapter
{
    public function __construct(
        private readonly Personnel $personnel,
    ) {
    }

    public function getDisplay(): string
    {
        return $this->personnel->getDisplay();
    }

    public function getDisplayPr(): string
    {
        return mb_strtoupper((string) $this->personnel->getNom()) . ' ' . ucfirst((string) $this->personnel->getPrenom());
    }

    public function getDomaines(): string
    {
        $domaines = $this->personnel->getDomaines();
        if (is_array($domaines)) {
            return implode(', ', array_filter($domaines));
        }

        return (string) ($domaines ?? '');
    }

    public function getResponsabilites(): string
    {
        return (string) ($this->personnel->getResponsabilites() ?? '');
    }


    public function getTelBureau(): ?string
    {
        return $this->personnel->getTelBureau();
    }

    public function getMailUniv(): ?string
    {
        return $this->personnel->getMailUniv();
    }

    public function getSignatureElectronique(): ?string
    {
        return null;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->personnel->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this->personnel, $getter)) {
            return $this->personnel->$getter();
        }

        return $this->personnel->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) || method_exists($this->personnel, $getter) || isset($this->personnel->$name);
    }
}

/**
 * Adaptateur période de stage pour alias documentsRendre, modalitesEncadrement, interruptions
 */
class StagePeriodeConventionAdapter
{
    public function __construct(
        private readonly StagePeriode $periode,
    ) {
    }

    public function getCompetencesVisees(): ?string
    {
        return $this->periode->getCompetencesVisees();
    }

    public function getModaliteEncadrement(): ?string
    {
        return $this->periode->getModalitesEncadrement();
    }

    public function getModalitesEncadrement(): ?string
    {
        return $this->periode->getModalitesEncadrement();
    }

    public function getDocumentRendre(): ?string
    {
        return $this->periode->getDocumentsRendre();
    }

    public function getDocumentsRendre(): ?string
    {
        return $this->periode->getDocumentsRendre();
    }

    public function getStagePeriodeInterruptions(): Collection
    {
        return $this->periode->getPeriodesInterruption();
    }

    public function getPeriodesInterruption(): Collection
    {
        return $this->periode->getPeriodesInterruption();
    }

    public function getNbEcts(): int
    {
        return 0;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->periode->$name(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this->periode, $getter)) {
            return $this->periode->$getter();
        }

        return $this->periode->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        $getter = 'get' . ucfirst($name);
        return method_exists($this, $getter) || method_exists($this->periode, $getter) || isset($this->periode->$name);
    }
}
