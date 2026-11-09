import api from '@helpers/axios';

/** Appels à l'API du package Finances (aucun appel direct dans les vues). */

const MERGE_PATCH = { headers: { 'Content-Type': 'application/merge-patch+json' } };
const JSON_LD = { headers: { 'Content-Type': 'application/ld+json' } };

/** Éléments d'une collection, quel que soit le format de réponse (tableau JSON, JSON-LD, Hydra). */
export const elements = (data) => (Array.isArray(data) ? data : (data?.member || data?.['hydra:member'] || []));

const PAR_PAGE = 100;
const PAGES_MAX = 50;

/** Tous les éléments d'une collection paginée, dans la limite de PAGES_MAX pages. */
async function toutesLesPages(url, params = {}) {
    const liste = [];
    for (let page = 1; page <= PAGES_MAX; page++) {
        const { data } = await api.get(url, { params: { ...params, page, itemsPerPage: PAR_PAGE } });
        const lot = elements(data);
        liste.push(...lot);
        const total = data?.totalItems ?? data?.['hydra:totalItems'];
        if (lot.length < PAR_PAGE || (typeof total === 'number' && liste.length >= total)) break;
    }
    return liste;
}

// ---------- Demandes d'achat ----------

/** Demandes visibles (l'API ne renvoie que celles que l'on a le droit de voir). */
export async function getFinanceBonsCommandeService(params = {}) {
    return toutesLesPages('/api/finance_bon_commandes', params);
}

/** Détail d'une demande, avec `droits` (actions possibles, champs modifiables) calculés par le serveur. */
export async function getFinanceBonCommandeDetailService(id) {
    const { data } = await api.get(`/api/finance_bon_commandes/${id}`);
    return data;
}

export async function createFinanceBonCommandeService(payload) {
    const { data } = await api.post('/api/finance_bon_commandes', payload, JSON_LD);
    return data;
}

export async function updateFinanceBonCommandeService(id, payload) {
    const { data } = await api.patch(`/api/finance_bon_commandes/${id}`, payload, MERGE_PATCH);
    return data;
}

export async function deleteFinanceBonCommandeService(id) {
    return api.delete(`/api/finance_bon_commandes/${id}`);
}

/** Action du circuit (soumettre, avis_favorable, verifier…) ; `donnees` : commentaire, date, réception… */
export async function transitionFinanceBonCommandeService(id, action, donnees = {}) {
    const { data } = await api.post(`/api/finance_bon_commandes/${id}/transitions/${action}`, donnees);
    return data;
}

// ---------- Réceptions ----------

export async function updateFinanceReceptionService(id, payload) {
    const { data } = await api.patch(`/api/finance_receptions/${id}`, payload, MERGE_PATCH);
    return data;
}

// ---------- Référentiels ----------

/** Fournisseurs du référentiel dont la raison sociale contient `recherche` (20 au plus). */
export async function searchFinanceFournisseursService(recherche = '') {
    const { data } = await api.get('/api/finance_fournisseurs', { params: { nomFournisseur: recherche, itemsPerPage: 20 } });
    return elements(data);
}

export async function createFinanceFournisseurService(payload) {
    const { data } = await api.post('/api/finance_fournisseurs', payload, JSON_LD);
    return data;
}

/** Taux de TVA du référentiel (actifs et inactifs, triés). */
export async function getFinanceTauxTvaService() {
    return toutesLesPages('/api/finance_taux_tvas');
}

export async function createFinanceTauxTvaService(payload) {
    const { data } = await api.post('/api/finance_taux_tvas', payload, JSON_LD);
    return data;
}

export async function updateFinanceTauxTvaService(id, payload) {
    const { data } = await api.patch(`/api/finance_taux_tvas/${id}`, payload, MERGE_PATCH);
    return data;
}

export async function deleteFinanceTauxTvaService(id) {
    return api.delete(`/api/finance_taux_tvas/${id}`);
}

// ---------- Documents ----------

/** Téléversement du PDF signé d'une demande. */
export async function uploadFinanceSignedPdfService(id, formData) {
    const { data } = await api.post(`/api/finance_bon_commandes/${id}/upload-signed-pdf`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
}

/** PDF du formulaire (étape 1 à 5) sous forme de Blob. */
export async function getFinancePdfBlobService(id, step = 5) {
    const { data } = await api.get(`/api/finance_bon_commandes/${id}/pdf`, { params: { step }, responseType: 'blob' });
    return new Blob([data], { type: 'application/pdf' });
}

/** PDF signé déposé, sous forme de Blob. */
export async function getFinanceSignedPdfBlobService(id) {
    const { data } = await api.get(`/api/finance_bon_commandes/${id}/download-signed-pdf`, { responseType: 'blob' });
    return new Blob([data], { type: 'application/pdf' });
}

/**
 * Export Excel (HtmlToSpreadsheet) des demandes `ids`, dans cet ordre : le serveur n'exporte que celles que l'on peut voir.
 * Renvoie le Blob et le nom de fichier annoncé par le serveur.
 */
export async function exportFinanceBonsCommandeExcelService(ids) {
    const response = await api.post('/api/finance_bon_commandes/export-excel', { ids }, { responseType: 'blob' });
    const entete = response.headers?.['content-disposition'] || '';
    const trouve = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(entete);
    return {
        blob: new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
        nom: trouve ? decodeURIComponent(trouve[1]) : 'demandes_achat.xlsx',
    };
}

/** Télécharge un Blob sous le nom donné. */
export function telechargerBlob(blob, nom) {
    const url = window.URL.createObjectURL(blob);
    const lien = document.createElement('a');
    lien.href = url;
    lien.setAttribute('download', nom);
    document.body.appendChild(lien);
    lien.click();
    lien.remove();
    setTimeout(() => window.URL.revokeObjectURL(url), 2000);
}
