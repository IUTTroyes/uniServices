/**
 * Catalogue d'aide : rôles et accès expliqués aux utilisateurs.
 * À tenir synchronisé avec docs/ROLES_ET_ACCES.md et les providers de rôles (*PermissionProvider.php).
 */

import { getApplicationMeta } from './applicationColors';

const nom = (application) => getApplicationMeta(application).name;
const icone = (application) => getApplicationMeta(application).icon;

export const PRINCIPES = [
    { icone: 'pi pi-building', titre: 'Un rôle vaut pour un département', texte: 'Vos rôles sont attribués par département. Le même rôle donné dans un autre département ne vous donne rien ici, sauf cas précisé (finances).' },
    { icone: 'pi pi-th-large', titre: 'Un module doit être actif', texte: `Un rôle n'a d'effet que si le module correspondant (${['finance', 'stage', 'helpdesk', 'questionnaire', 'unifolio'].map(nom).join(', ')}) est activé sur votre affectation.` },
    { icone: 'pi pi-sync', titre: 'Département actif', texte: 'Le portail travaille dans un département à la fois (sélecteur en haut de page). Changez-le pour voir ce que vous pouvez faire ailleurs.' },
    { icone: 'pi pi-lock', titre: 'Le serveur décide', texte: 'Un bouton absent signifie que l\'action vous est refusée. Pour obtenir un droit, demandez-le à l\'administrateur de votre département.' },
];

export const GROUPES = [
    { id: 'direction', titre: 'Direction et administration', icone: 'pi pi-shield' },
    { id: 'scolarite', titre: 'Scolarité et enseignement', icone: 'pi pi-book' },
    { id: 'services', titre: 'Services et référents', icone: 'pi pi-users' },
    { id: 'finance', titre: `${nom('finance')} (demandes d'achat)`, icone: icone('finance') },
    { id: 'stages', titre: nom('stage'), icone: icone('stage') },
    { id: 'helpdesk', titre: nom('helpdesk'), icone: icone('helpdesk') },
    { id: 'questionnaires', titre: nom('questionnaire'), icone: icone('questionnaire') },
    { id: 'portfolio', titre: nom('unifolio'), icone: icone('unifolio') },
];

/** role : identifiant technique ; portee : où il s'applique ; permet : ce qu'il permet. */
export const ROLES = [
    { groupe: 'direction', role: 'SUPER_ADMIN', libelle: 'Super administrateur', portee: 'Toute l\'application', permet: 'Tout : configuration du portail, services, référentiels globaux, imports, suppressions.' },
    { groupe: 'direction', role: 'ROLE_ADMIN', libelle: 'Administrateur', portee: 'Département', permet: 'Gère le département : structure, personnel et attribution des rôles, étudiants, enseignements, documents, actualités. Inclut les rôles de direction, scolarité, notes, assistant et permanent.' },
    { groupe: 'direction', role: 'ROLE_DIRECTEUR_IUT', libelle: 'Directeur de l\'IUT', portee: 'Établissement', permet: 'Rôle de direction, activé sur toutes les affectations de la personne.' },
    { groupe: 'direction', role: 'ROLE_DIRECTEUR_ADJOINT_IUT', libelle: 'Directeur adjoint de l\'IUT', portee: 'Établissement', permet: 'Rôle de direction, activé sur toutes les affectations de la personne.' },
    { groupe: 'direction', role: 'ROLE_CHEF_SERVICES_ADMINISTRATIFS', libelle: 'Chef des services administratifs', portee: 'Établissement', permet: 'Rôle de direction, activé sur toutes les affectations de la personne.' },
    { groupe: 'direction', role: 'ROLE_CHEF_DEPT', libelle: 'Chef de département', portee: 'Département', permet: 'Pilote son département : personnel, scolarité, prévisionnel, évaluations, stages, questionnaires, actualités, documents.' },
    { groupe: 'direction', role: 'ROLE_CHEF_DEPARTEMENT', libelle: 'Chef de département', portee: 'Département', permet: 'Identique à « Chef de département » (inclut ce rôle).' },
    { groupe: 'direction', role: 'ROLE_DIRECTEUR_ETUDES', libelle: 'Directeur des études', portee: 'Département', permet: 'Scolarité, absences, emplois du temps, prévisionnel, plans de cours.' },
    { groupe: 'direction', role: 'ROLE_RESP_PARCOURS', libelle: 'Responsable de parcours', portee: 'Diplômes désignés', permet: 'Scolarité, référentiels APC, notes, évaluations, prévisionnel et plans de cours, uniquement pour ses diplômes.' },
    { groupe: 'direction', role: 'ROLE_DIRECTION', libelle: 'Direction (historique)', portee: 'Département', permet: 'Conservé pour compatibilité.' },
    { groupe: 'direction', role: 'ROLE_EMAIL_ADMIN', libelle: 'Gestionnaire des emails', portee: 'Département', permet: 'Gère les emails du portail.' },

    { groupe: 'scolarite', role: 'ROLE_SCOLARITE', libelle: 'Scolarité', portee: 'Département', permet: 'Dossiers et scolarité des étudiants, absences et justificatifs.' },
    { groupe: 'scolarite', role: 'ROLE_ASSISTANT', libelle: 'Assistant de formation', portee: 'Département', permet: 'Consulte et complète la scolarité et les absences, consulte le prévisionnel.' },
    { groupe: 'scolarite', role: 'ROLE_NOTES_MANAGER', libelle: 'Responsable des notes', portee: 'Département', permet: 'Voit et saisit toutes les évaluations et notes du département.' },
    { groupe: 'scolarite', role: 'ROLE_ABSENCE', libelle: 'Responsable des absences', portee: 'Département', permet: 'Suivi des absences et justificatifs.' },
    { groupe: 'scolarite', role: 'ROLE_EDT', libelle: 'Responsable des emplois du temps', portee: 'Département', permet: 'Crée et modifie les cours, contraintes, créneaux et progression de l\'emploi du temps.' },
    { groupe: 'scolarite', role: 'ROLE_EDUSIGN', libelle: 'Référent Edusign', portee: 'Département', permet: 'Intégration Edusign.' },
    { groupe: 'scolarite', role: 'ROLE_PERMANENT', libelle: 'Personnel permanent', portee: 'Département', permet: 'Consultation élargie : scolarité, notes, absences, prévisionnel du département.' },
    { groupe: 'scolarite', role: 'ROLE_TEACHER', libelle: 'Enseignant', portee: 'Département', permet: 'Saisit les notes de ses évaluations, consulte son service et ses plans de cours.' },
    { groupe: 'scolarite', role: 'ROLE_BIATSS', libelle: 'BIATSS', portee: 'Département', permet: 'Personnel administratif et technique (repère de profil).' },

    { groupe: 'services', role: 'ROLE_RELAI_COMM', libelle: 'Référent communication', portee: 'Département', permet: 'Gère les actualités du département.' },
    { groupe: 'services', role: 'ROLE_REFERENT', libelle: `Référent ${nom('auth')}`, portee: 'Département', permet: 'Référent applicatif du département.' },
    { groupe: 'services', role: 'ROLE_REFERENT_INTRANET', libelle: `Référent ${nom('intranet')}`, portee: 'Département', permet: `Contenus de l'application ${nom('intranet')}.` },
    { groupe: 'services', role: 'ROLE_REFERENT_PROJET', libelle: 'Référent Projet', portee: 'Département', permet: 'Suivi des projets.' },
    { groupe: 'services', role: 'ROLE_INFORMATIQUE', libelle: 'Service informatique', portee: 'Département', permet: 'Repère de service.' },
    { groupe: 'services', role: 'ROLE_COMPTA', libelle: 'Service comptabilité', portee: 'Département', permet: `Consulte et corrige les heures du prévisionnel. À ne pas confondre avec le service financier du module ${nom('finance')}.` },
    { groupe: 'services', role: 'ROLE_QUALITE', libelle: 'Service qualité', portee: 'Département', permet: 'Crée, modifie, publie et supprime les questionnaires.' },

    { groupe: 'finance', role: 'ROLE_FINANCE_VIEW', libelle: 'Accès aux finances', portee: 'Département', permet: 'Ouvre le module et permet de suivre ses propres demandes.' },
    { groupe: 'finance', role: 'ROLE_FINANCE_DEMANDEUR', libelle: 'Demandeur', portee: 'Département', permet: 'Crée des demandes d\'achat dans son département (ou pour un service), dépose le PDF signé, constate le service fait avec le bon de livraison, supprime son brouillon.' },
    { groupe: 'finance', role: 'ROLE_FINANCE_VALIDEUR', libelle: 'Valideur', portee: 'Département', permet: 'Voit les demandes de son département et rend l\'avis de l\'étape 1.' },
    { groupe: 'finance', role: 'ROLE_FINANCE_MANAGER', libelle: 'Gestionnaire (service financier)', portee: 'Tout l\'établissement', permet: 'Voit et traite toutes les demandes, quel que soit le département ou le service : vérification, engagement, visa des réceptions (code MIGO), facture, paiement, clôture, suppression. Gère les fournisseurs et la TVA.' },

    { groupe: 'stages', role: 'ROLE_STAGE_VIEW', libelle: 'Voir les stages', portee: 'Département', permet: 'Consulte les stages du département.' },
    { groupe: 'stages', role: 'ROLE_STAGE_MANAGER', libelle: 'Responsable des stages', portee: 'Département', permet: 'Gère périodes, dossiers, avenants et soutenances de son département.' },

    { groupe: 'helpdesk', role: 'ROLE_HELPDESK_AGENT', libelle: 'Agent de support', portee: 'Module', permet: 'Traite les tickets. Tout compte connecté peut créer un ticket.' },
    { groupe: 'helpdesk', role: 'ROLE_HELPDESK_ADMIN', libelle: `Administrateur ${nom('helpdesk')}`, portee: 'Module', permet: `Administre ${nom('helpdesk')} (inclut le rôle d'agent).` },

    { groupe: 'questionnaires', role: 'ROLE_QUESTIONNAIRE_MANAGER', libelle: `Administrateur ${nom('questionnaire')}`, portee: 'Module', permet: 'Gère les questionnaires du module.' },

    { groupe: 'portfolio', role: 'ROLE_REFERENT_PORTFOLIO', libelle: 'Référent Portfolio', portee: 'Département', permet: 'Pilote les campagnes et les modèles de portfolio.' },
    { groupe: 'portfolio', role: 'ROLE_PORTFOLIO_CORRECTOR', libelle: 'Correcteur de portfolios', portee: 'Département', permet: 'Évalue les portfolios.' },
    { groupe: 'portfolio', role: 'ROLE_PORTFOLIO_PUBLISHER', libelle: 'Responsable publication', portee: 'Département', permet: 'Publie les portfolios.' },
];

/** Circuit des demandes d'achat : qui peut faire quoi. */
export const FINANCE_ACTIONS = [
    { action: 'Créer une demande', demandeur: 'Oui, dans ses départements', valideur: 'Non', financier: 'S\'il est aussi demandeur du département' },
    { action: 'Voir une demande', demandeur: 'Les siennes', valideur: 'Celles de ses départements', financier: 'Toutes' },
    { action: 'Déposer le PDF signé', demandeur: 'Brouillon, soumise', valideur: 'Non', financier: 'Jusqu\'à la clôture' },
    { action: 'Avis de l\'étape 1', demandeur: 'Non', valideur: 'Son département', financier: 'Demandes de service' },
    { action: 'Constater le service fait', demandeur: 'Oui, avec le bon de livraison', valideur: 'Non', financier: 'Oui' },
    { action: 'Viser les réceptions, code MIGO', demandeur: 'Non', valideur: 'Non', financier: 'Oui' },
    { action: 'Vérifier, engager, transmettre, facturer, payer, clôturer', demandeur: 'Non', valideur: 'Non', financier: 'Oui' },
    { action: 'Supprimer', demandeur: 'Son brouillon', valideur: 'Non', financier: 'À tout statut' },
];

export const libelleRole = (role) => ROLES.find(r => r.role === role)?.libelle ?? role;

/** Rôles du catalogue, filtrés par texte (libellé, identifiant, description). */
export function filtrerRoles(texte) {
    const q = String(texte ?? '').trim().toLowerCase();
    if (!q) return ROLES;
    return ROLES.filter(r => [r.libelle, r.role, r.permet, r.portee].some(v => v.toLowerCase().includes(q)));
}

/** Vue d'ensemble (super administrateur) : domaines x rôles. ● écriture, ○ lecture, — aucun accès. */
export const MATRICE_COLONNES = ['Admin', 'Chef dépt', 'Dir. études', 'Resp. parcours', 'Scolarité', 'Assistant', 'Notes', 'Permanent', 'Enseignant'];
export const MATRICE = [
    { domaine: 'Structure du département', valeurs: ['●', '●', '○', '○', '○', '○', '—', '—', '—'] },
    { domaine: 'Personnel et affectations', valeurs: ['●', '● (sauf rôles)', '—', '—', '—', '—', '—', '—', '—'] },
    { domaine: 'Étudiants (fiche)', valeurs: ['●', '●', '○', '○', '●', '○', '—', '○', '—'] },
    { domaine: 'Scolarité', valeurs: ['●', '●', '●', '●', '●', '●', '—', '○', '—'] },
    { domaine: 'Évaluations', valeurs: ['●', '●', '○', '●', '—', '—', '●', '○', 'les siennes'] },
    { domaine: 'Notes', valeurs: ['●', '●', '—', '●', '—', '—', '●', '○', 'les siennes'] },
    { domaine: 'Absences et justificatifs', valeurs: ['●', '●', '●', '—', '●', '○', '—', '○', '—'] },
    { domaine: 'Enseignements, UE', valeurs: ['●', '●', '●', '●', '—', '—', '—', '—', '—'] },
    { domaine: 'APC (référentiels)', valeurs: ['●', '●', '—', '●', '—', '—', '—', '—', '—'] },
    { domaine: 'Prévisionnel', valeurs: ['●', '●', '●', '●', '—', '○', '—', '○', 'ses heures'] },
    { domaine: 'Plans de cours', valeurs: ['●', '●', '●', '●', '—', '—', '—', '—', 'les siens'] },
    { domaine: nom('questionnaire'), valeurs: ['●', '●', '○', '●', '—', '—', '—', '○', '—'] },
    { domaine: 'Documents et actualités', valeurs: ['●', '●', '—', '—', '—', '—', '—', '—', '—'] },
];

export const RESERVE_SUPER_ADMIN = [
    'Configuration du portail : services de l\'établissement, référentiels globaux (départements, bacs, types de diplôme, calendriers, types d\'heures).',
    'Import CSV d\'étudiants et opérations globales.',
    'Suppression des ressources institutionnelles sans département.',
    'Simulation de rôles (aide à la vérification : le serveur applique toujours les droits réels).',
    'Attribution des rôles : Configuration → Gestion des accès (avec les administrateurs autorisés sur le département).',
];

export const POINTS_VIGILANCE = [
    `Le rôle « Gestionnaire » du module ${nom('finance')}, même donné dans un seul département, ouvre toutes les demandes de l'établissement : à réserver à l'équipe financière.`,
    'Un rôle n\'agit que si le module est actif sur l\'affectation : vérifier les packages en cas de « droit manquant ».',
    '« Service comptabilité » (prévisionnel) et « service financier » (demandes d\'achat) sont deux notions distinctes.',
    'Le super administrateur donné par une affectation vaut pour toute l\'application, quel que soit le département actif.',
];
