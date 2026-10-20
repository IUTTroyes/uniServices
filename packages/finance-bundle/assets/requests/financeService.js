import api from '@helpers/axios';

/**
 * Service pour la gestion des demandes d'achat et bons de commande
 */
export async function getFinanceBonsCommandeService(params = {}) {
    return api.get('/api/finance_bon_commandes', { params });
}

export async function getFinanceBonCommandeDetailService(id) {
    return api.get(`/api/finance_bon_commandes/${id}`);
}

export async function createFinanceBonCommandeService(data) {
    return api.post('/api/finance_bon_commandes', data);
}

export async function updateFinanceBonCommandeService(id, data) {
    return api.patch(`/api/finance_bon_commandes/${id}`, data, {
        headers: {
            'Content-Type': 'application/merge-patch+json',
        },
    });
}

export async function deleteFinanceBonCommandeService(id) {
    return api.delete(`/api/finance_bon_commandes/${id}`);
}

export async function getFinanceFournisseursService(params = {}) {
    return api.get('/api/finance_fournisseurs', { params });
}

export async function createFinanceReceptionService(data) {
    return api.post('/api/finance_receptions', data);
}

export async function updateFinanceReceptionService(id, data) {
    return api.patch(`/api/finance_receptions/${id}`, data, {
        headers: {
            'Content-Type': 'application/merge-patch+json',
        },
    });
}

/**
 * Téléversement du PDF signé extérieurement pour une demande
 */
export async function uploadFinanceSignedPdfService(id, formData) {
    return api.post(`/api/finance_bon_commandes/${id}/upload-signed-pdf`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
}

/**
 * Récupère le PDF d'une étape sous forme de Blob via l'API Axios
 */
export async function getFinancePdfBlobService(id, step = null) {
    const params = step ? { step } : {};
    return api.get(`/api/finance_bon_commandes/${id}/pdf`, {
        params,
        responseType: 'blob',
    });
}

/**
 * Télécharge le PDF d'une étape via un Blob (comme le package questionnaire)
 */
export async function downloadFinancePdfService(id, step = null, customFilename = null) {
    const response = await getFinancePdfBlobService(id, step);
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', customFilename || `bon_commande_${id}${step ? `_etape_${step}` : ''}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => window.URL.revokeObjectURL(url), 2000);
}

/**
 * Récupère le PDF signé sous forme de Blob via l'API Axios
 */
export async function getFinanceSignedPdfBlobService(id) {
    return api.get(`/api/finance_bon_commandes/${id}/download-signed-pdf`, {
        responseType: 'blob',
    });
}

/**
 * Télécharge le PDF signé via un Blob
 */
export async function downloadFinanceSignedPdfService(id) {
    const response = await getFinanceSignedPdfBlobService(id);
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `demande_${id}_signee.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => window.URL.revokeObjectURL(url), 2000);
}

/**
 * Ouvre le PDF signé dans un nouvel onglet via un Blob
 */
export async function openFinanceSignedPdfService(id) {
    const response = await getFinanceSignedPdfBlobService(id);
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    window.open(url, '_blank');
    setTimeout(() => window.URL.revokeObjectURL(url), 60000);
}

/**
 * URL directe API pour référence si besoin
 */
export function getFinancePdfUrl(id, step = null) {
    if (!id) return '';
    return `/api/finance_bon_commandes/${id}/pdf${step ? `?step=${step}` : ''}`;
}

export function getFinanceSignedPdfUrl(id) {
    if (!id) return '';
    return `/api/finance_bon_commandes/${id}/download-signed-pdf`;
}
