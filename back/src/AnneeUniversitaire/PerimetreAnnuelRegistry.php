<?php

declare(strict_types=1);

namespace App\AnneeUniversitaire;

/** Chemins ORM explicites : aucun filtre Doctrine global ni recherche heuristique de relations. */
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
        'EdtContraintesSemestre' => 'anneeUniversitaire',
        'EdtAppel' => 'edtEvent.anneeUniversitaire',
        'Previsionnel' => 'anneeUniversitaire',
        'EtudiantAbsence' => 'scolariteSemestre.scolarite.anneeUniversitaire',
        'EtudiantAbsenceJustificatif' => 'scolariteSemestre.scolarite.anneeUniversitaire',
        'MoyenneUe' => 'etudiantScolariteSemestre.scolarite.anneeUniversitaire',
        'MoyenneEnseignement' => 'etudiantScolariteSemestre.scolarite.anneeUniversitaire',
        'PlanCours' => 'anneeUniversitaire', 'PlanCoursRealise' => 'planCours.anneeUniversitaire', 'PlanCoursSequence' => 'planCours.anneeUniversitaire',
        'AvancementEnseignement' => 'anneeUniversitaire',
        'SousCommission' => 'semestre.annee.pn.anneeUniversitaire',
        'SousCommissionRevision' => 'sousCommission.semestre.annee.pn.anneeUniversitaire',
        'ResultatSousCommission' => 'inscription.scolarite.anneeUniversitaire',
        'StagePeriode' => 'anneeUniversitaire',
        'StageEtudiant' => 'stagePeriode.anneeUniversitaire',
        'StageAvenant' => 'stageEtudiant.stagePeriode.anneeUniversitaire',
        'StageSoutenance' => 'stagePeriode.anneeUniversitaire',
        'StageEvaluationEntreprise' => 'stageEtudiant.stagePeriode.anneeUniversitaire',
        'StageContexte' => 'stageEtudiant.stagePeriode.anneeUniversitaire',
        'StageEvaluationCritere' => 'stagePeriode.anneeUniversitaire',
        'StagePeriodeInterruption' => 'stagePeriode.anneeUniversitaire',
        'StagePeriodeSoutenance' => 'stagePeriode.anneeUniversitaire',
        'Questionnaire' => 'anneeUniversitaire',
        'QuestionnaireSection' => 'questionnaire.anneeUniversitaire',
        'QuestionnaireQuestion' => 'section.questionnaire.anneeUniversitaire',
        'QuestionnaireSectionInstance' => 'questionnaire.anneeUniversitaire',
        'QuestionnaireInvitation' => 'questionnaire.anneeUniversitaire',
        'QuestionnaireAnswer' => 'invitation.questionnaire.anneeUniversitaire',
        'QuestionnaireRetour' => 'sectionInstance.questionnaire.anneeUniversitaire',
        'QuestionnaireDiffusion' => 'sectionInstance.questionnaire.anneeUniversitaire',
        'Campagne' => 'anneeUniversitaire', 'CampagneAffectation' => 'campagne.anneeUniversitaire',
        'Portfolio' => 'scolarite.anneeUniversitaire', 'PortfolioPage' => 'portfolio.scolarite.anneeUniversitaire',
        'PortfolioPublication' => 'portfolio.scolarite.anneeUniversitaire',
        'Trace' => 'scolarite.anneeUniversitaire', 'TraceMedia' => 'trace.scolarite.anneeUniversitaire',
        'TraceRattachement' => 'trace.scolarite.anneeUniversitaire', 'Commentaire' => 'trace.scolarite.anneeUniversitaire',
        'AutoEvaluation' => 'scolarite.anneeUniversitaire',
    ];

    private const INDEPENDANTS = [
        'StructureAnneeUniversitaire', 'StructureDepartement', 'StructureTypeDiplome', 'StructureService', 'StructureDepartementPersonnel',
        'Etudiant', 'Personnel', 'Salle', 'ScolBac', 'PersonnelEnseignantTypeHrs', 'EdtProgression',
        'ApcReferentiel', 'ApcCompetence', 'ApcParcours', 'ApcNiveau', 'ApcApprentissageCritique',
        'Etablissement', 'Universite', 'ResetToken', 'RefreshToken', 'EmailTemplate', 'DepartementActualite', 'LienUtile',
        'Notification', 'PreferenceNotification', 'DashboardPreference', 'SchedulerTask',
        'FinanceBonCommande', 'FinanceReception', 'FinanceReceptionDocument', 'FinanceFournisseur', 'FinanceTauxTva',
        'HelpdeskCategorie', 'HelpdeskTicket', 'HelpdeskMessage', 'Document', 'DocumentCategory',
        'StageEntreprise', 'StageContact', 'StageConventionTemplate',
        'Message', 'MessageDestinataire', 'MessagePieceJointe',
        'Cv', 'CvFormation', 'CvExperience', 'Grille', 'GrilleVersion', 'GrilleCritere', 'GrilleEchelon', 'PortfolioModele',
        'ParametrageFormation', 'MigrationV1Lien', 'ReponseCritere',
    ];

    private const DTO_MODES = [
        'QuestionnaireAnalyticsDto' => 'ressource', 'PublishOutputDto' => 'ressource',
        'PreviewIndexDto' => 'ressource', 'PreviewSectionDto' => 'ressource',
        'InvitationIndexDto' => 'ressource', 'InvitationSectionResource' => 'ressource',
        'SaveAnswersResource' => 'ressource', 'SubmitResource' => 'ressource',
        'DuplicateOutputDto' => 'annuel', 'MesInvitationDto' => 'transversal',
    ];

    public function mode(string $class): string
    {
        $name = substr($class, strrpos($class, '\\') + 1);
        return self::DTO_MODES[$name] ?? ($this->path($class) === null ? 'independant' : 'annuel');
    }

    public function path(string $class): ?string
    {
        $name = substr($class, strrpos($class, '\\') + 1);
        if (str_starts_with($class, 'ApiPlatform\\') || isset(self::DTO_MODES[$name])) return null;
        if ($name === 'Evaluation' && str_starts_with($class, 'UnifolioBundle\\')) return 'scolarite.anneeUniversitaire';
        if (array_key_exists($name, self::PATHS)) return self::PATHS[$name];
        if (in_array($name, self::INDEPENDANTS, true)) return null;
        throw new \LogicException('Politique annuelle non déclarée pour '.$class.'.');
    }

    public function autoriseToutes(string $class): bool
    {
        return in_array(substr($class, strrpos($class, '\\') + 1), ['Etudiant', 'StructureDiplome', 'EtudiantScolarite', 'EtudiantScolariteSemestre', 'Portfolio', 'Trace', 'Campagne'], true);
    }
}
