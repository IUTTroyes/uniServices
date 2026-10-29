// Catalogue partagé par les contrôles et la simulation de rôles.
export const  AVAILABLE_ROLES = [
  { property: 'isEnseignant', label: 'Enseignant', role: 'ROLE_TEACHER', scope: 'departement' },
  { property: 'isBiatss', label: 'BIATSS', role: 'ROLE_BIATSS', scope: 'departement' },
  { property: 'isDirecteurIut', label: 'Directeur de l’IUT', role: 'ROLE_DIRECTEUR_IUT', scope: 'departement' },
  { property: 'isDirecteurAdjointIut', label: 'Directeur adjoint de l’IUT', role: 'ROLE_DIRECTEUR_ADJOINT_IUT', scope: 'departement' },
  { property: 'isChefServicesAdministratifs', label: 'Chef des services administratifs', role: 'ROLE_CHEF_SERVICES_ADMINISTRATIFS', scope: 'departement' },
  { property: 'isChefDepartement', label: 'Chef de département', role: 'ROLE_CHEF_DEPARTEMENT', scope: 'departement' },
  { property: 'isDirecteurEtudes', label: 'Directeur des études', role: 'ROLE_DIRECTEUR_ETUDES', scope: 'departement' },
  { property: 'isRespParcours', label: 'Responsable de parcours', role: 'ROLE_RESP_PARCOURS', scope: 'diplome' },
  { property: 'isAssistant', label: 'Assistant de formation', role: 'ROLE_ASSISTANT', scope: 'departement' },
  { property: 'isReferent', label: 'Référent UniServices', role: 'ROLE_REFERENT', scope: 'departement' },
  { property: 'isEdusign', label: 'Référent Edusign', role: 'ROLE_EDUSIGN', scope: 'departement' },
  { property: 'isAbsence', label: 'Responsable des absences', role: 'ROLE_ABSENCE', scope: 'departement' },
  { property: 'isStage', label: 'Responsable des stages', role: 'ROLE_STAGE_MANAGER', scope: 'departement' },
  { property: 'isEdt', label: 'Responsable des emplois du temps', role: 'ROLE_EDT', scope: 'departement' },
  { property: 'isNote', label: 'Responsable des notes', role: 'ROLE_NOTES_MANAGER', scope: 'departement' },
  { property: 'isRelaiComm', label: 'Référent communication', role: 'ROLE_RELAI_COMM', scope: 'departement' },
  { property: 'isReferentIntranet', label: 'Référent Intranet', role: 'ROLE_REFERENT_INTRANET', scope: 'departement' },
  { property: 'isReferentPortfolio', label: 'Référent Portfolio', role: 'ROLE_REFERENT_PORTFOLIO', scope: 'departement' },
  { property: 'isReferentProjet', label: 'Référent Projet', role: 'ROLE_REFERENT_PROJET', scope: 'departement' },
  { property: 'isInformatique', label: 'Service informatique', role: 'ROLE_INFORMATIQUE', scope: 'departement' },
  { property: 'isCompta', label: 'Service comptabilité', role: 'ROLE_COMPTA', scope: 'departement' },
  { property: 'isQualite', label: 'Service qualité', role: 'ROLE_QUALITE', scope: 'departement' },
  { property: 'isScolarite', label: 'Service scolarité', role: 'ROLE_SCOLARITE', scope: 'departement' },
  { property: 'isDirection', label: 'Direction (historique)', role: 'ROLE_DIRECTION', scope: 'departement' },
  { property: 'isSuperAdmin', label: 'Super administrateur', role: 'SUPER_ADMIN', scope: 'departement' },
];

export const ROLE_MAP = Object.fromEntries(AVAILABLE_ROLES.map(({ property, role }) => [property, role]));

export const IUT_DIRECTION_ROLES = ['ROLE_DIRECTEUR_IUT', 'ROLE_DIRECTEUR_ADJOINT_IUT', 'ROLE_CHEF_SERVICES_ADMINISTRATIFS'];

/** Direction of the IUT is activated on every department assigned to the person. */
export function setIutDirectionRole(departments, role, enabled) {
  if (!IUT_DIRECTION_ROLES.includes(role)) return;
  for (const department of departments) {
    const roles = new Set(department.permissions ?? []);
    if (enabled) roles.add(role);
    else roles.delete(role);
    department.permissions = [...roles];
  }
}

export function normalizeIutDirectionRoles(departments) {
  const selected = new Set(departments.flatMap(department => department.permissions ?? []).filter(role => IUT_DIRECTION_ROLES.includes(role)));
  for (const role of selected) setIutDirectionRole(departments, role, true);
}
