/**
 * Conversions communes aux écrans du package Stages (réponses de l'API ↔ affichage).
 * Fonctions pures, sans dépendance : testées dans assets/tests/stage.test.js.
 */

/** « 2027-04-05T00:00:00+02:00 » ou « 2027-04-05 » → « 05/04/2027 » (sans décalage de fuseau) ; '' si vide. */
export const dateFr = (iso) => {
  if (!iso) return '';
  const parts = String(iso).substring(0, 10).split('-');
  return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : String(iso);
};

/** Libellés des statuts de convention affichés dans les écrans, par état de l'API. */
export const STATUT_PAR_ETAT = {
  ETAT_STAGE_DEPOSE: 'En attente',
  ETAT_STAGE_VALIDE: 'Validée',
  ETAT_STAGE_CONVENTION_IMPRIMEE: 'En cours de signature',
  ETAT_STAGE_CONVENTION_ENVOYEE: 'En cours de signature',
  ETAT_STAGE_CONVENTION_RECUE: 'Signée',
  ETAT_STAGE_REFUS: 'Rejetée'
};

/** État de l'API → statut affiché (« Aucune » tant que l'étudiant n'a rien déposé). */
export const statutConvention = (etat) => STATUT_PAR_ETAT[etat] || 'Aucune';

/** Statut affiché → état de l'API ; « Aucune » dépend de l'ouverture de la saisie. */
export const etatDepuisStatut = (statut, saisieAutorisee) => {
  switch (statut) {
    case 'En attente': return 'ETAT_STAGE_DEPOSE';
    case 'Validée': return 'ETAT_STAGE_VALIDE';
    case 'En cours de signature': return 'ETAT_STAGE_CONVENTION_ENVOYEE';
    case 'Signée': return 'ETAT_STAGE_CONVENTION_RECUE';
    case 'Rejetée': return 'ETAT_STAGE_REFUS';
    default: return saisieAutorisee ? 'ETAT_STAGE_AUTORISE' : 'ETAT_STAGE_INCOMPLET';
  }
};

/** Le dossier contient-il une demande (au-delà de la simple autorisation de saisie) ? */
export const aUneDemande = (etat) => !!etat && etat !== 'ETAT_STAGE_AUTORISE' && etat !== 'ETAT_STAGE_INCOMPLET';

/** « M. Jean DUPONT » à partir d'un contact ({ civilite, prenom, nom }) ; `defaut` si pas de prénom. */
export const nomContact = (contact, defaut = '-') => (contact?.prenom
  ? `${contact.civilite || 'M.'} ${contact.prenom} ${contact.nom || ''}`.trim()
  : defaut);

/** Adresse sur une ligne : « 9 rue X, compl. - 10430 Ville (France) » ; `defaut` si pas de rue. */
export const adresseLigne = (adresse, defaut = '-') => {
  if (!adresse?.adresse) return defaut;
  const complements = [adresse.complement1, adresse.complement2].filter(Boolean).map(c => `, ${c}`).join('');
  return `${adresse.adresse}${complements} - ${adresse.codePostal || ''} ${adresse.ville || ''} (${adresse.pays || 'France'})`;
};

/** Identifiant d'une relation reçue en objet ({ id } / { '@id' }) ou en IRI (« /api/x/12 ») ; null sinon. */
export const idDepuisRelation = (rel) => {
  if (!rel) return null;
  if (typeof rel === 'object') {
    if (rel.id) return parseInt(rel.id, 10);
    rel = rel['@id'];
  }
  const id = parseInt(String(rel ?? '').split('/').pop(), 10);
  return Number.isNaN(id) ? null : id;
};
