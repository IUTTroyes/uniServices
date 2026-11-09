/**
 * Outils communs du package Finances (sans dépendance à Vue) : étapes du circuit, montants, formats, IRI.
 * Les règles de droits ne sont PAS ici : le serveur les fournit avec chaque demande (`droits`).
 */

/** Ordre des statuts dans le circuit (REJETE hors circuit). */
export const ORDRE_STATUTS = [
    'BROUILLON', 'SOUMIS', 'AVIS_DIRECTION', 'VERIFICATION_FINANCIERE', 'VISA_ENGAGEMENT', 'COMMANDE_VALIDEE',
    'COMMANDE_TRANSMISE', 'SERVICE_FAIT_CONSTATE', 'SERVICE_FAIT_CERTIFIE', 'FACTURE_RECUE', 'PAYE', 'CLOTURE',
];

/** Premier statut atteint une fois chaque étape (1 à 5) terminée. */
const STATUT_ETAPE_TERMINEE = {
    1: 'VERIFICATION_FINANCIERE',
    2: 'VISA_ENGAGEMENT',
    3: 'COMMANDE_VALIDEE',
    4: 'SERVICE_FAIT_CERTIFIE',
    5: 'PAYE',
};

/** Étape (1 à 5) en cours pour ce statut ; 0 pour une demande rejetée. */
export function etapeCourante(statut) {
    if (statut === 'REJETE') return 0;
    for (let etape = 1; etape <= 5; etape++) {
        if (!etapeTerminee(statut, etape)) return etape;
    }
    return 5;
}

/** L'étape (1 à 5) est-elle terminée au statut donné ? */
export function etapeTerminee(statut, etape) {
    const rang = ORDRE_STATUTS.indexOf(statut);
    return rang >= 0 && rang >= ORDRE_STATUTS.indexOf(STATUT_ETAPE_TERMINEE[etape]);
}

/**
 * Les 5 étapes, avec leur couleur : repère visuel commun aux cartes (bordure gauche), à la frise et aux onglets.
 * Classes Tailwind écrites en entier (détectées à la compilation).
 */
export const ETAPES = [
    { id: 1, titre: 'Demande et avis', icone: 'pi pi-file-edit', couleur: 'blue', iconeClasse: 'text-blue-600 dark:text-blue-400', tag: 'bg-blue-100! text-blue-800! dark:bg-blue-950! dark:text-blue-200!', bordure: 'border-l-4 border-l-blue-600', fond: 'bg-blue-600', anneau: 'ring-blue-100 dark:ring-blue-950', texte: 'text-blue-700 dark:text-blue-400' },
    { id: 2, titre: 'Vérification', icone: 'pi pi-search', couleur: 'amber', iconeClasse: 'text-amber-600 dark:text-amber-400', tag: 'bg-amber-100! text-amber-800! dark:bg-amber-950! dark:text-amber-200!', bordure: 'border-l-4 border-l-amber-500', fond: 'bg-amber-500', anneau: 'ring-amber-100 dark:ring-amber-950', texte: 'text-amber-700 dark:text-amber-400' },
    { id: 3, titre: 'Engagement', icone: 'pi pi-check-circle', couleur: 'purple', iconeClasse: 'text-purple-600 dark:text-purple-400', tag: 'bg-purple-100! text-purple-800! dark:bg-purple-950! dark:text-purple-200!', bordure: 'border-l-4 border-l-purple-600', fond: 'bg-purple-600', anneau: 'ring-purple-100 dark:ring-purple-950', texte: 'text-purple-700 dark:text-purple-400' },
    { id: 4, titre: 'Service fait', icone: 'pi pi-box', couleur: 'teal', iconeClasse: 'text-teal-600 dark:text-teal-400', tag: 'bg-teal-100! text-teal-800! dark:bg-teal-950! dark:text-teal-200!', bordure: 'border-l-4 border-l-teal-600', fond: 'bg-teal-600', anneau: 'ring-teal-100 dark:ring-teal-950', texte: 'text-teal-700 dark:text-teal-400' },
    { id: 5, titre: 'Facture et paiement', icone: 'pi pi-wallet', couleur: 'pink', iconeClasse: 'text-pink-600 dark:text-pink-400', tag: 'bg-pink-100! text-pink-800! dark:bg-pink-950! dark:text-pink-200!', bordure: 'border-l-4 border-l-pink-600', fond: 'bg-pink-600', anneau: 'ring-pink-100 dark:ring-pink-950', texte: 'text-pink-700 dark:text-pink-400' },
];

export const etapeParId = (id) => ETAPES.find(e => e.id === id);

const TAG_VERT = 'bg-emerald-100! text-emerald-800! dark:bg-emerald-950! dark:text-emerald-200!';
const TAG_GRIS = 'bg-gray-100! text-gray-700! dark:bg-gray-800! dark:text-gray-200!';
const TAG_ROUGE = 'bg-red-100! text-red-800! dark:bg-red-950! dark:text-red-200!';

/** Demande finalisée en entier : clôturée, ou payée et soldée (facture finale). */
export const estFinalisee = (commande) => commande?.statut === 'CLOTURE' || (commande?.statut === 'PAYE' && Boolean(commande?.factureFinale));

/**
 * Classes d'un tag de statut : couleur de l'étape en cours ; vert quand la demande est finalisée en entier, gris pour un
 * brouillon, rouge pour un rejet.
 */
export function styleStatut(statut, finalisee = false) {
    if (finalisee || statut === 'CLOTURE') return TAG_VERT;
    if (statut === 'REJETE') return TAG_ROUGE;
    if (statut === 'BROUILLON') return TAG_GRIS;
    return etapeParId(Math.max(1, etapeCourante(statut))).tag;
}

/** Étape dans laquelle une action du circuit s'affiche. */
export const ETAPE_DES_ACTIONS = {
    soumettre: 1, avis_favorable: 1, avis_defavorable: 1, demander_information: 1, reouvrir: 1,
    verifier: 2, rejeter: 2,
    viser: 3, transmettre: 3,
    constater_service_fait: 4, certifier_service_fait: 4,
    recevoir_facture: 5, rejeter_facture: 5, payer: 5, cloturer: 5,
};

// ---------- Montants ----------

const arrondi = (n) => Math.round(n * 100) / 100;

/** Nombre ou null (accepte « 12,5 »). */
export function nombre(valeur) {
    if (valeur === null || valeur === undefined || valeur === '') return null;
    const n = Number(String(valeur).replace(',', '.'));
    return Number.isFinite(n) ? n : null;
}

export function ttcDepuisHt(ht, taux) {
    const h = nombre(ht);
    const t = nombre(taux);
    return h === null || t === null ? null : arrondi(h * (1 + t / 100));
}

export function htDepuisTtc(ttc, taux) {
    const m = nombre(ttc);
    const t = nombre(taux);
    return m === null || t === null ? null : arrondi(m / (1 + t / 100));
}

/** HT et TTC saisis tous les deux mais incohérents avec le taux (écart de plus d'un centime). */
export function montantsIncoherents(ht, ttc, taux) {
    const attendu = ttcDepuisHt(ht, taux);
    const m = nombre(ttc);
    return attendu !== null && m !== null && Math.round(Math.abs(attendu - m) * 100) > 1;
}

/** Montant pour l'API (chaîne décimale) ou null. */
export function montantApi(valeur) {
    const n = nombre(valeur);
    return n === null ? null : n.toFixed(2);
}

// ---------- Formats ----------

export function formatMontant(valeur) {
    const n = nombre(valeur);
    return n === null ? '—' : new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(n);
}

export function formatDate(valeur) {
    if (!valeur) return '—';
    const d = new Date(valeur);
    return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('fr-FR');
}

/** Date « AAAA-MM-JJ » pour un champ <input type="date">, ou ''. */
export function dateSaisie(valeur) {
    if (!valeur) return '';
    return String(valeur).slice(0, 10);
}

export function nomPersonne(personne) {
    if (!personne || typeof personne !== 'object') return '—';
    return `${personne.prenom || ''} ${personne.nom || ''}`.trim() || '—';
}

// ---------- IRI ----------

/** Identifiant numérique d'un objet API, d'une IRI ou d'un nombre. */
export function idDe(valeur) {
    if (valeur === null || valeur === undefined || valeur === '') return null;
    if (typeof valeur === 'object') return idDe(valeur.id ?? valeur['@id']);
    const id = Number(String(valeur).split('/').pop());
    return Number.isInteger(id) && id > 0 ? id : null;
}

export function iri(ressource, valeur) {
    const id = idDe(valeur);
    return id === null ? null : `/api/${ressource}/${id}`;
}

// ---------- Erreurs ----------

/** Message lisible d'une erreur d'appel à l'API (violations 422, message du serveur, sinon défaut). */
export function messageErreurApi(error, defaut = 'Une erreur est survenue.') {
    const data = error?.response?.data;
    if (!data || typeof data !== 'object') return defaut;
    if (Array.isArray(data.violations) && data.violations.length > 0) {
        return [...new Set(data.violations.map(v => v.message).filter(Boolean))].join('\n');
    }
    return data.detail || data['hydra:description'] || data.error || data.message || defaut;
}
